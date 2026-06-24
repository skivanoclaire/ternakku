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

    /** Pastikan dataset publik tersedia (idempotent). */
    public function prep(): array
    {
        return Http::timeout(120)->post($this->base . '/prep')->json() ?? ['error' => 'gagal prep'];
    }

    /** Statistik & korelasi dataset (halaman EDA). */
    public function eda(): array
    {
        return Http::timeout(60)->post($this->base . '/eda')->json() ?? ['error' => 'gagal eda'];
    }

    /** Latih satu eksperimen (metode + fitur + mode evaluasi + skenario + target log). */
    public function trainExperiment(string $method, array $features, string $evalMode, int $expId, string $scenario = 'B', bool $logTarget = false): array
    {
        $res = Http::timeout(300)->post($this->base . '/experiment/train', [
            'method'     => $method,
            'features'   => array_values($features),
            'eval_mode'  => $evalMode,
            'scenario'   => $scenario,
            'log_target' => $logTarget,
            'exp_id'     => $expId,
        ]);
        return $res->json() ?? ['error' => 'ml tidak merespons saat training'];
    }

    /** Bangkitkan data sintetis (n baris) dari service ML. */
    public function generateSynthetic(int $n = 800): array
    {
        return Http::timeout(60)->post($this->base . '/synth/generate', ['n' => $n])->json() ?? ['error' => 'gagal generate'];
    }

    /** Uji model pada data uji eksternal (tidak dilatihkan). */
    public function evaluate(string $modelVer, array $rows): array
    {
        $res = Http::timeout(120)->post($this->base . '/evaluate', [
            'model_ver' => $modelVer,
            'rows'      => array_values($rows),
        ]);
        return $res->json() ?? ['error' => 'ml tidak merespons saat evaluasi'];
    }

    /** Jadikan artefak eksperimen sebagai model aktif (melayani peternak). */
    public function promote(string $modelVer): array
    {
        return Http::timeout(30)->post($this->base . '/experiment/promote', [
            'model_ver' => $modelVer,
        ])->json() ?? ['error' => 'gagal promosi'];
    }
}
