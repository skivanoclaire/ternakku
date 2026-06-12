<?php

namespace App\Http\Controllers\Peternak;

use App\Http\Controllers\Controller;
use App\Models\Pengukuran;
use App\Models\Ternak;
use App\Services\MlClient;
use Illuminate\Http\Request;

/**
 * Input pengukuran oleh peternak — HANYA ukuran tubuh (tanpa bobot timbang).
 * Sistem langsung mengembalikan estimasi bobot dari model aktif (p10-p90).
 * Baris disimpan source=farmer, bobot_timbang_kg = null (bukan data latih).
 */
class PengukuranController extends Controller
{
    public function __construct(protected MlClient $ml) {}

    public function store(Request $request, Ternak $ternak)
    {
        abort_unless($ternak->user_id === $request->user()->id, 403);

        $data = $request->validate([
            'lingkar_dada_cm'  => ['required', 'numeric', 'min:50', 'max:300'],
            'panjang_badan_cm' => ['nullable', 'numeric', 'min:30', 'max:300'],
            'tinggi_gumba_cm'  => ['nullable', 'numeric', 'min:30', 'max:250'],
        ]);

        // estimasi dari model aktif (tanpa bobot timbang dari peternak)
        $est = $this->ml->predictBobot(
            (float) $data['lingkar_dada_cm'],
            isset($data['panjang_badan_cm']) ? (float) $data['panjang_badan_cm'] : null,
            isset($data['tinggi_gumba_cm']) ? (float) $data['tinggi_gumba_cm'] : null,
        );

        Pengukuran::create([
            'ternak_id'         => $ternak->id,
            'tanggal'           => now()->toDateString(),
            'lingkar_dada_cm'   => $data['lingkar_dada_cm'],
            'panjang_badan_cm'  => $data['panjang_badan_cm'] ?? null,
            'tinggi_gumba_cm'   => $data['tinggi_gumba_cm'] ?? null,
            'bobot_timbang_kg'  => null,                       // peternak tidak menimbang
            'bobot_estimasi_kg' => $est['bobot_estimasi_kg'] ?? null,
            'model_ver'         => $est['model_ver'] ?? null,
            'source'            => 'farmer',
            'status'            => 'approved',
        ]);

        if (isset($est['error'])) {
            return back()->withErrors(['ml' => $est['error']]);
        }

        return redirect()->route('ternak.show', $ternak)->with('estimasi', $est);
    }
}
