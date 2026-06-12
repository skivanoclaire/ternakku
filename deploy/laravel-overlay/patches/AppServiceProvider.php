<?php

namespace App\Providers;

use App\Models\Audit;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // RBAC: admin di-override penuh, mendahului semua policy ABAC.
        Gate::before(fn ($user, $ability) => $user->isAdmin() ? true : null);

        // Audit trail: catat setiap login.
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
}
