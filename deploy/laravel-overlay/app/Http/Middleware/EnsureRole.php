<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware RBAC. Pakai di route:  ->middleware('role:admin')
 * atau beberapa peran:               ->middleware('role:admin,peternak')
 * Daftarkan alias 'role' di bootstrap/app.php (lihat README).
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // ABAC dasar: akun nonaktif langsung ditolak apa pun perannya
        if (! $user || ! $user->is_active) {
            abort(403, 'Akun tidak aktif atau belum login.');
        }

        if (! $user->hasRole($roles)) {
            abort(403, 'Peran tidak mencukupi.');
        }

        return $next($request);
    }
}
