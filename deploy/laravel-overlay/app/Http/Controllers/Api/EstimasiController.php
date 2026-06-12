<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MlClient;
use Illuminate\Http\Request;

class EstimasiController extends Controller
{
    public function __construct(protected MlClient $ml) {}

    /** POST /api/estimasi/bobot — estimasi bobot dari ukuran tubuh (Modul 1). */
    public function bobot(Request $request)
    {
        $data = $request->validate([
            'lingkar_dada_cm'  => ['required', 'numeric', 'min:50', 'max:300'],
            'panjang_badan_cm' => ['nullable', 'numeric', 'min:30', 'max:300'],
            'tinggi_gumba_cm'  => ['nullable', 'numeric', 'min:30', 'max:250'],
        ]);

        $hasil = $this->ml->predictBobot(
            (float) $data['lingkar_dada_cm'],
            isset($data['panjang_badan_cm']) ? (float) $data['panjang_badan_cm'] : null,
            isset($data['tinggi_gumba_cm']) ? (float) $data['tinggi_gumba_cm'] : null,
        );

        if (isset($hasil['error'])) {
            return response()->json($hasil, 503);
        }

        return response()->json($hasil);
    }

    /** GET /api/ml/health — cek service ML hidup & model sudah dilatih. */
    public function health()
    {
        return response()->json($this->ml->health());
    }
}
