<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Experiment;
use App\Services\DatasetService;
use App\Services\MlClient;
use Illuminate\Http\Request;

/**
 * Workbench researcher (Modul 1): EDA, latih model via web, evaluasi+grafik,
 * leaderboard pembanding metode, promosi model aktif.
 * Semua training didelegasikan ke FastAPI (MlClient). Web tidak melatih sendiri.
 */
class ResearcherController extends Controller
{
    public function __construct(protected MlClient $ml, protected DatasetService $dataset) {}

    /** 6.2 — Eksplorasi & Kualitas Data (dari data latih DB terbaru). */
    public function eda()
    {
        $this->dataset->exportTrainingCsv();
        $eda = $this->ml->eda();
        return view('admin.eda', ['eda' => $eda]);
    }

    /** 6.5 — Form pelatihan. */
    public function latihForm()
    {
        return view('admin.latih', [
            // tiap metode: [label, penjelasan detail]
            'methods' => [
                'schoorl' => ['Baseline klasik Schoorl (LD)', 'Rumus tetap BB=(LD+22)²/100, dikalibrasi untuk sapi Eropa. Tanpa pelatihan — jadi pembanding wajib: model lain harus mengalahkannya agar bermakna.'],
                'loglog'  => ['Power-law / log-log (LD)', 'ln(BB)=a+b·ln(LD). Mengikuti hukum allometrik (bobot ∝ dimensi pangkat ~2,5–3). Jujur secara biologis, hanya pakai lingkar dada.'],
                'linear'  => ['Regresi linear berganda', 'BB = kombinasi linear fitur. Sederhana & transparan; sangat terbantu oleh fitur rekayasa (LD²·PB).'],
                'rf'      => ['Random Forest', 'Ensembel pohon — menangkap pola non-linear & interaksi. Kelemahan: tak bisa ekstrapolasi di luar rentang bobot latih.'],
                'xgb'     => ['XGBoost', 'Gradient boosting — biasanya paling akurat pada data tabular. Sama seperti RF: lemah ekstrapolasi.'],
                'hybrid'  => ['Hibrida allometrik + ML', 'Basis allometrik (a·LD^b) menangani biologi & ekstrapolasi; ML (Random Forest) hanya mengoreksi RESIDUAL-nya. Gabungan kekuatan keduanya — kandidat kontribusi orisinal artikel.'],
            ],
            // tiap fitur: [label, penjelasan]
            'allFeatures' => [
                'lingkar_dada_cm'  => ['Lingkar dada (LD)', 'Penanda bobot terkuat — melingkari rongga dada. Wajib.'],
                'panjang_badan_cm' => ['Panjang badan (PB)', 'Bahu ke tulang duduk. Bersama LD membentuk proksi volume.'],
                'tinggi_gumba_cm'  => ['Tinggi gumba (TG)', 'Tinggi punuk bahu. Indikator kerangka.'],
                'ld2'    => ['LD² (rekayasa)', 'Lingkar dada dikuadratkan — luas penampang dada ∝ LD².'],
                'ld2_pb' => ['LD²·PB — proksi VOLUME (rekayasa)', 'Inti rumus klasik: volume ≈ luas penampang × panjang. Fitur tunggal paling kuat; sering melonjakkan regresi linear.'],
                'pb_ld'  => ['PB/LD — rasio bentuk (rekayasa)', 'Sapi panjang-ramping vs pendek-gemuk.'],
                'tg_ld'  => ['TG/LD — rasio bentuk (rekayasa)', 'Proporsi tinggi terhadap lingkar.'],
            ],
        ]);
    }

    const VALID_FEATURES = 'lingkar_dada_cm,panjang_badan_cm,tinggi_gumba_cm,ld2,ld2_pb,pb_ld,tg_ld';

    /** 6.5 — Jalankan pelatihan -> simpan eksperimen + metrik. */
    public function latihStore(Request $request)
    {
        $data = $request->validate([
            'method'    => ['required', 'in:schoorl,loglog,linear,rf,xgb,hybrid'],
            'features'  => ['required', 'array', 'min:1'],
            'features.*'=> ['in:' . self::VALID_FEATURES],
            'eval_mode' => ['required', 'in:acak,lintas'],
            'scenario'  => ['required', 'in:A,B,C'],
            'log_target'=> ['nullable', 'boolean'],
        ]);
        $logTarget = (bool) ($data['log_target'] ?? false);

        // ekspor data latih terbaru dari DB lalu buat baris eksperimen (id stabil utk model_ver)
        $this->dataset->exportTrainingCsv();
        $exp = Experiment::create([
            'user_id'   => $request->user()->id,
            'method'    => $data['method'],
            'features'  => $data['features'],
            'eval_mode' => $data['eval_mode'],
            'scenario'  => $data['scenario'],
            'log_target'=> $logTarget,
        ]);

        $res = $this->ml->trainExperiment($data['method'], $data['features'], $data['eval_mode'], $exp->id, $data['scenario'], $logTarget);

        if (isset($res['error'])) {
            $exp->delete();
            return back()->withErrors(['ml' => $res['error']]);
        }

        $m = $res['metrics'] ?? [];
        $exp->update([
            'method_label' => $res['method_label'] ?? null,
            'scenario'     => $res['scenario'] ?? $exp->scenario,
            'n_rows'       => $res['n_rows'] ?? 0,
            'mape' => $m['mape'] ?? null, 'mae' => $m['mae'] ?? null,
            'rmse' => $m['rmse'] ?? null, 'r2' => $m['r2'] ?? null,
            'bias' => $m['bias'] ?? null, 'coverage' => $m['coverage'] ?? null,
            'interval_kg'  => $m['interval_kg'] ?? null,
            'model_ver'    => $res['model_ver'] ?? null,
            'importance'   => $res['importance'] ?? null,
            'diagnostics'  => $res['diagnostics'] ?? null,
        ]);

        return redirect()->route('admin.eksperimen', $exp)->with('ok', 'Pelatihan selesai.');
    }

    /** 6.6 + 6.7 — Detail eksperimen: metrik, grafik diagnostik, interpretabilitas. */
    public function eksperimen(Experiment $experiment)
    {
        return view('admin.eksperimen', ['exp' => $experiment]);
    }

    /** 6.8 — Leaderboard pembanding metode. */
    public function leaderboard()
    {
        $experiments = Experiment::with('user')
            ->whereNotNull('mape')
            ->orderBy('mape')
            ->get();

        return view('admin.leaderboard', ['experiments' => $experiments]);
    }

    /** 6.9 — Daftar versi & promosi model aktif. */
    public function modelIndex()
    {
        $experiments = Experiment::with('user')->whereNotNull('model_ver')->latest()->get();
        return view('admin.model', ['experiments' => $experiments]);
    }

    /** 6.13 — Ekspor leaderboard sebagai CSV (bahan artikel/SLR). */
    public function exportLeaderboard()
    {
        $exps = Experiment::with('user')->whereNotNull('mape')->orderBy('mape')->get();
        $out = fopen('php://temp', 'r+');
        fputcsv($out, ['rank', 'method', 'features', 'eval_mode', 'mape', 'mae', 'rmse', 'r2', 'coverage', 'model_ver', 'user', 'date']);
        foreach ($exps as $i => $e) {
            fputcsv($out, [$i + 1, $e->method_label ?? $e->method, implode('|', $e->features ?? []),
                $e->eval_mode, $e->mape, $e->mae, $e->rmse, $e->r2, $e->coverage, $e->model_ver,
                $e->user?->name, $e->created_at?->format('Y-m-d H:i')]);
        }
        rewind($out);
        return response(stream_get_contents($out), 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leaderboard_ternakku.csv"',
        ]);
    }

    public function promote(Request $request, Experiment $experiment)
    {
        if (! $experiment->model_ver) {
            return back()->withErrors(['ml' => 'Eksperimen ini belum punya artefak model.']);
        }
        $res = $this->ml->promote($experiment->model_ver);
        if (isset($res['error'])) {
            return back()->withErrors(['ml' => $res['error']]);
        }
        Experiment::where('is_active', true)->update(['is_active' => false]);
        $experiment->update(['is_active' => true]);

        return back()->with('ok', "Model {$experiment->model_ver} kini aktif melayani estimasi.");
    }
}
