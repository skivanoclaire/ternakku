<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Klien HTTP ke service ML (FastAPI) di jaringan internal Docker.
 * URL diambil dari config services.ml.url (env ML_URL, default http://ml:8000).
 * Service ml TIDAK terekspos publik — hanya Laravel yang memanggilnya.
 */
class MlClient
{
    protected string $base;

    public function __construct()
    {
        $this->base = rtrim(config('services.ml.url', 'http://ml:8000'), '/');
    }

    /** Estimasi bobot dari ukuran tubuh (Modul 1). */
    public function predictBobot(float $lingkarDada, ?float $panjangBadan = null, ?float $tinggiGumba = null): array
    {
        $res = Http::timeout(10)->post($this->base . '/predict/bobot', [
            'lingkar_dada_cm'  => $lingkarDada,
            'panjang_badan_cm' => $panjangBadan,
            'tinggi_gumba_cm'  => $tinggiGumba,
        ]);

        return $res->json() ?? ['error' => 'ml service tidak merespons'];
    }

    /** Latih ulang model di VM (opsional dipakai admin/scheduler). */
    public function train(?string $best = null): array
    {
        $url = $this->base . '/train' . ($best ? '?best=' . urlencode($best) : '');
        return Http::timeout(120)->post($url)->json() ?? ['error' => 'gagal train'];
    }

    public function health(): array
    {
        return Http::timeout(5)->get($this->base . '/health')->json() ?? ['status' => 'down'];
    }
}
