<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatasetService;
use App\Services\MlClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Kelola data latih (researcher). Impor dataset sapi nyata (CSV) -> pengukuran
 * (source=public, ber-bobot timbang). Ini sumber ground truth untuk pelatihan;
 * peternak TIDAK menyumbang bobot.
 */
class DataController extends Controller
{
    public function __construct(protected DatasetService $dataset, protected MlClient $ml) {}

    /** Bangkitkan data sintetis (source=synthetic) untuk skenario validasi A/C. */
    public function generateSynthetic(Request $request)
    {
        $n = (int) $request->validate(['n' => ['nullable', 'integer', 'min:100', 'max:5000']])['n'] ?: 800;

        // hapus sintetis lama agar tidak menumpuk, lalu sisipkan yang baru
        DB::table('pengukuran')->where('source', 'synthetic')->delete();
        $res = $this->ml->generateSynthetic($n);
        if (isset($res['error']) || empty($res['rows'])) {
            return back()->withErrors(['ml' => $res['error'] ?? 'gagal generate']);
        }
        $batch = [];
        foreach ($res['rows'] as $r) {
            $batch[] = [
                'lingkar_dada_cm'  => $r['lingkar_dada_cm'] ?? null,
                'panjang_badan_cm' => $r['panjang_badan_cm'] ?? null,
                'tinggi_gumba_cm'  => $r['tinggi_gumba_cm'] ?? null,
                'bobot_timbang_kg' => $r['bobot_timbang_kg'] ?? null,
                'source'           => 'synthetic',
                'status'           => 'approved',
                'src_dataset'      => 'synthetic',
                'src_animal_id'    => $r['src_animal_id'] ?? null,
                'created_at'       => now(), 'updated_at' => now(),
            ];
            if (count($batch) >= 500) { DB::table('pengukuran')->insert($batch); $batch = []; }
        }
        if ($batch) { DB::table('pengukuran')->insert($batch); }
        $this->dataset->exportTrainingCsv();

        return back()->with('ok', count($res['rows']) . ' baris data sintetis dibuat (untuk skenario A/C).');
    }

    public function index()
    {
        return view('admin.data', ['stats' => $this->dataset->stats()]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv'          => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
            'dataset_name' => ['required', 'string', 'max:50'],
            'col_ld'       => ['required', 'string'],
            'col_bobot'    => ['required', 'string'],
            'col_pb'       => ['nullable', 'string'],
            'col_gumba'    => ['nullable', 'string'],
            'col_ras'      => ['nullable', 'string'],
        ]);

        $path = $request->file('csv')->getRealPath();
        $fh = fopen($path, 'r');
        $header = fgetcsv($fh);
        if (! $header) {
            return back()->withErrors(['csv' => 'CSV kosong / tidak terbaca.']);
        }
        // peta nama kolom -> indeks (case-insensitive)
        $idx = [];
        foreach ($header as $i => $h) {
            $idx[strtolower(trim($h))] = $i;
        }
        $col = fn ($name) => $name && isset($idx[strtolower(trim($name))]) ? $idx[strtolower(trim($name))] : null;
        $iLd = $col($request->col_ld); $iBobot = $col($request->col_bobot);
        $iPb = $col($request->col_pb); $iGumba = $col($request->col_gumba); $iRas = $col($request->col_ras);

        if ($iLd === null || $iBobot === null) {
            fclose($fh);
            return back()->withErrors(['csv' => 'Kolom lingkar dada / bobot tidak ditemukan di header CSV.']);
        }

        $num = fn ($row, $i) => ($i !== null && isset($row[$i]) && is_numeric(trim($row[$i]))) ? (float) $row[$i] : null;
        $rows = []; $n = 0; $skip = 0; $line = 0;
        while (($r = fgetcsv($fh)) !== false) {
            $line++;
            $ld = $num($r, $iLd); $bobot = $num($r, $iBobot);
            if ($ld === null || $bobot === null || $ld < 50 || $ld > 300 || $bobot < 20 || $bobot > 2000) { $skip++; continue; }
            $rows[] = [
                'lingkar_dada_cm'  => $ld,
                'panjang_badan_cm' => $num($r, $iPb),
                'tinggi_gumba_cm'  => $num($r, $iGumba),
                'bobot_timbang_kg' => $bobot,
                'source'           => 'public',
                'status'           => 'approved',
                'src_dataset'      => $request->dataset_name,
                'src_animal_id'    => (string) $line,
                'created_at'       => now(), 'updated_at' => now(),
            ];
            $n++;
            if (count($rows) >= 500) { DB::table('pengukuran')->insert($rows); $rows = []; }
        }
        if ($rows) { DB::table('pengukuran')->insert($rows); }
        fclose($fh);

        // segarkan CSV latih agar training memakai data terbaru
        $this->dataset->exportTrainingCsv();

        return back()->with('ok', "Impor selesai: $n baris masuk, $skip dilewati (tak valid).");
    }
}
