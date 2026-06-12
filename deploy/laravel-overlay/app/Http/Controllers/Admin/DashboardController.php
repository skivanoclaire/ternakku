<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\Experiment;
use App\Services\MlClient;
use Illuminate\Support\Facades\DB;

/**
 * Dashboard admin: ringkasan data/model, tren akurasi antar eksperimen,
 * status service ML, dan audit trail terbaru.
 */
class DashboardController extends Controller
{
    public function __construct(protected MlClient $ml) {}

    public function index()
    {
        $count = fn (string $t) => DB::getSchemaBuilder()->hasTable($t) ? DB::table($t)->count() : 0;

        $stats = [
            'users'      => $count('users'),
            'ternak'     => $count('ternak'),
            'pengukuran' => $count('pengukuran'),
            'audits'     => $count('audits'),
        ];

        $aktif = Experiment::where('is_active', true)->first();

        // tren akurasi: MAPE tiap eksperimen menurut urutan waktu
        $tren = Experiment::whereNotNull('mape')->orderBy('id')
            ->get(['id', 'method', 'mape'])
            ->map(fn ($e) => ['label' => '#'.$e->id.' '.$e->method, 'mape' => $e->mape]);

        $mlStatus = $this->ml->health();

        return view('admin.dashboard', [
            'stats'        => $stats,
            'auditTerbaru' => Audit::with('user')->latest()->limit(50)->get(),
            'modelAktif'   => $aktif,
            'tren'         => $tren,
            'mlStatus'     => $mlStatus,
        ]);
    }
}
