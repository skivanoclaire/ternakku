<?php
/**
 * Cuplikan untuk app/Providers/AppServiceProvider.php (Laravel 11) atau
 * AuthServiceProvider. Letakkan di method boot().
 *
 * - Gate::before -> admin di-override penuh (RBAC menang untuk admin).
 * - login dicatat ke audit trail.
 * Policy (TernakPolicy) auto-discovery oleh Laravel; tak perlu didaftarkan manual.
 */

use App\Models\Audit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Events\Login;

public function boot(): void
{
    // RBAC: admin boleh segalanya (mendahului semua policy ABAC)
    Gate::before(fn ($user, $ability) => $user->isAdmin() ? true : null);

    // Audit trail: catat setiap login
    Event::listen(Login::class, function (Login $event) {
        Audit::create([
            'user_id'    => $event->user->id,
            'event'      => 'login',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'url'        => request()?->fullUrl(),
        ]);
    });
}
