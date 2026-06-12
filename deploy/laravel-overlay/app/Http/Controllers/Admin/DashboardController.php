<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use Illuminate\Support\Facades\DB;

/**
 * Area admin. Route-nya dilindungi middleware 'auth' + 'role:admin'
 * (lihat routes/web.php). Ini "menu login admin": hanya peran admin
 * yang bisa masuk; pengguna lain dapat 403.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $count = fn (string $t) => DB::getSchemaBuilder()->hasTable($t) ? DB::table($t)->count() : 0;

        $stats = [
            'users'      => $count('users'),
            'ternak'     => $count('ternak'),
            'pengukuran' => $count('pengukuran'),
            'audits'     => $count('audits'),
        ];

        $auditTerbaru = Audit::with('user')->latest()->limit(50)->get();

        return view('admin.dashboard', compact('stats', 'auditTerbaru'));
    }
}
