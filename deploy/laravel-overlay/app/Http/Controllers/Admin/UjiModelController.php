<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Experiment;
use App\Services\MlClient;
use Illuminate\Http\Request;

/**
 * Uji Model — evaluasi model pada data uji EKSTERNAL yang diunggah researcher.
 * Data uji TIDAK dilatihkan (tidak masuk DB pengukuran) — murni untuk menilai
 * akurasi pada populasi nyata/baru (mis. sapi lokal). Validasi eksternal.
 */
class UjiModelController extends Controller
{
    public function __construct(protected MlClient $ml) {}

    public function index()
    {
        return view('admin.uji', [
            'models' => Experiment::whereNotNull('model_ver')->latest()->get(),
            'hasil'  => null,
        ]);
    }

    public function run(Request $request)
    {
        $request->validate([
            'model_ver' => ['required', 'string'],
            'csv'       => ['nullable', 'file', 'mimes:csv,txt', 'max:10240'],
            'manual'    => ['nullable', 'string'],
            'col_ld'    => ['nullable', 'string'],
            'col_bobot' => ['nullable', 'string'],
            'col_pb'    => ['nullable', 'string'],
            'col_gumba' => ['nullable', 'string'],
        ]);

        $num = fn ($v) => (trim((string) $v) !== '' && is_numeric(trim((string) $v))) ? (float) $v : null;
        $rows = []; $skip = 0;

        if ($request->hasFile('csv')) {
            // sumber: unggah CSV (butuh nama kolom)
            if (! $request->filled('col_ld') || ! $request->filled('col_bobot')) {
                return back()->withErrors(['csv' => 'Untuk CSV, sebutkan kolom lingkar dada & bobot.']);
            }
            $fh = fopen($request->file('csv')->getRealPath(), 'r');
            $header = fgetcsv($fh);
            if (! $header) { fclose($fh); return back()->withErrors(['csv' => 'CSV kosong / tidak terbaca.']); }
            $idx = [];
            foreach ($header as $i => $h) { $idx[strtolower(trim($h))] = $i; }
            $col = fn ($n) => $n && isset($idx[strtolower(trim($n))]) ? $idx[strtolower(trim($n))] : null;
            $iLd = $col($request->col_ld); $iBobot = $col($request->col_bobot);
            $iPb = $col($request->col_pb); $iGumba = $col($request->col_gumba);
            if ($iLd === null || $iBobot === null) {
                fclose($fh);
                return back()->withErrors(['csv' => 'Kolom lingkar dada / bobot tidak ditemukan di header CSV.']);
            }
            $cell = fn ($r, $i) => $i !== null ? ($r[$i] ?? null) : null;
            while (($r = fgetcsv($fh)) !== false) {
                $ld = $num($cell($r, $iLd)); $bobot = $num($cell($r, $iBobot));
                if ($ld === null || $bobot === null || $ld <= 0 || $bobot <= 0) { $skip++; continue; }
                $rows[] = ['lingkar_dada_cm' => $ld, 'panjang_badan_cm' => $num($cell($r, $iPb)),
                          'tinggi_gumba_cm' => $num($cell($r, $iGumba)), 'bobot_timbang_kg' => $bobot];
            }
            fclose($fh);
        } elseif (trim((string) $request->manual) !== '') {
            // sumber: input manual, satu baris = "LD,PB,TG,bobot" (PB/TG boleh kosong)
            foreach (preg_split('/\r\n|\r|\n/', trim($request->manual)) as $line) {
                if (trim($line) === '') { continue; }
                $p = array_map('trim', explode(',', $line));
                $ld = $num($p[0] ?? null); $bobot = $num($p[3] ?? null);
                if ($ld === null || $bobot === null || $ld <= 0 || $bobot <= 0) { $skip++; continue; }
                $rows[] = ['lingkar_dada_cm' => $ld, 'panjang_badan_cm' => $num($p[1] ?? null),
                          'tinggi_gumba_cm' => $num($p[2] ?? null), 'bobot_timbang_kg' => $bobot];
            }
        } else {
            return back()->withErrors(['csv' => 'Isi data manual atau unggah CSV (minimal salah satu).']);
        }

        if (! $rows) {
            return back()->withErrors(['csv' => 'Tidak ada baris valid (butuh lingkar dada & bobot).']);
        }

        $hasil = $this->ml->evaluate($request->model_ver, $rows);
        if (isset($hasil['error'])) {
            return back()->withErrors(['ml' => $hasil['error']]);
        }
        $hasil['model_ver'] = $request->model_ver;
        $hasil['dilewati'] = $skip;

        return view('admin.uji', [
            'models' => Experiment::whereNotNull('model_ver')->latest()->get(),
            'hasil'  => $hasil,
        ]);
    }
}
