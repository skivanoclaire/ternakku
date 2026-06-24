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
            'csv'       => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'model_ver' => ['required', 'string'],
            'col_ld'    => ['required', 'string'],
            'col_bobot' => ['required', 'string'],
            'col_pb'    => ['nullable', 'string'],
            'col_gumba' => ['nullable', 'string'],
        ]);

        $fh = fopen($request->file('csv')->getRealPath(), 'r');
        $header = fgetcsv($fh);
        if (! $header) {
            return back()->withErrors(['csv' => 'CSV kosong / tidak terbaca.']);
        }
        $idx = [];
        foreach ($header as $i => $h) {
            $idx[strtolower(trim($h))] = $i;
        }
        $col = fn ($n) => $n && isset($idx[strtolower(trim($n))]) ? $idx[strtolower(trim($n))] : null;
        $iLd = $col($request->col_ld); $iBobot = $col($request->col_bobot);
        $iPb = $col($request->col_pb); $iGumba = $col($request->col_gumba);
        if ($iLd === null || $iBobot === null) {
            fclose($fh);
            return back()->withErrors(['csv' => 'Kolom lingkar dada / bobot tidak ditemukan di header CSV.']);
        }

        $num = fn ($r, $i) => ($i !== null && isset($r[$i]) && is_numeric(trim($r[$i]))) ? (float) $r[$i] : null;
        $rows = []; $skip = 0;
        while (($r = fgetcsv($fh)) !== false) {
            $ld = $num($r, $iLd); $bobot = $num($r, $iBobot);
            if ($ld === null || $bobot === null || $ld <= 0 || $bobot <= 0) { $skip++; continue; }
            $rows[] = [
                'lingkar_dada_cm'  => $ld,
                'panjang_badan_cm' => $num($r, $iPb),
                'tinggi_gumba_cm'  => $num($r, $iGumba),
                'bobot_timbang_kg' => $bobot,
            ];
        }
        fclose($fh);

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
