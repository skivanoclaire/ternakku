<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Audit;

/**
 * Area admin. Route-nya dilindungi middleware 'auth' + 'role:admin'
 * (lihat routes/web-admin.php). Ini "menu login admin": hanya peran admin
 * yang bisa masuk; pengguna lain dapat 403.
 */
class DashboardController extends Controller
{
    public function index()
    {
        // ringkasan + audit trail terbaru untuk ditampilkan di dashboard admin
        $auditTerbaru = Audit::with('user')->latest()->limit(50)->get();

        return view('admin.dashboard', [
            'auditTerbaru' => $auditTerbaru,
        ]);
    }
}
