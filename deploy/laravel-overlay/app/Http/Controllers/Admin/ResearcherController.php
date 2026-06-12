<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Experiment;
use App\Services\MlClient;
use Illuminate\Http\Request;

/**
 * Workbench researcher (Modul 1): EDA, latih model via web, evaluasi+grafik,
 * leaderboard pembanding metode, promosi model aktif.
 * Semua training didelegasikan ke FastAPI (MlClient). Web tidak melatih sendiri.
 */
class ResearcherController extends Controller
{
    public function __construct(protected MlClient $ml) {}

    /** 6.2 — Eksplorasi & Kualitas Data. */
    public function eda()
    {
        $eda = $this->ml->eda();
        return view('admin.eda', ['eda' => $eda]);
    }

    /** 6.5 — Form pelatihan. */
    public function latihForm()
    {
        return view('admin.latih', [
            'methods' => [
                'schoorl' => 'Baseline klasik Schoorl (LD)',
                'loglog'  => 'Power-law / log-log (LD)',
                'linear'  => 'Regresi linear berganda',
                'rf'      => 'Random Forest',
                'xgb'     => 'XGBoost',
            ],
            'allFeatures' => [
                'lingkar_dada_cm'  => 'Lingkar dada',
                'panjang_badan_cm' => 'Panjang badan',
                'tinggi_gumba_cm'  => 'Tinggi gumba',
            ],
        ]);
    }

    /** 6.5 — Jalankan pelatihan -> simpan eksperimen + metrik. */
    public function latihStore(Request $request)
    {
        $data = $request->validate([
            'method'    => ['required', 'in:schoorl,loglog,linear,rf,xgb'],
            'features'  => ['required', 'array', 'min:1'],
            'features.*'=> ['in:lingkar_dada_cm,panjang_badan_cm,tinggi_gumba_cm'],
            'eval_mode' => ['required', 'in:acak,lintas'],
        ]);

        // pastikan dataset siap, lalu buat baris eksperimen (dapat id stabil utk model_ver)
        $this->ml->prep();
        $exp = Experiment::create([
            'user_id'   => $request->user()->id,
            'method'    => $data['method'],
            'features'  => $data['features'],
            'eval_mode' => $data['eval_mode'],
        ]);

        $res = $this->ml->trainExperiment($data['method'], $data['features'], $data['eval_mode'], $exp->id);

        if (isset($res['error'])) {
            $exp->delete();
            return back()->withErrors(['ml' => $res['error']]);
        }

        $m = $res['metrics'] ?? [];
        $exp->update([
            'method_label' => $res['method_label'] ?? null,
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
