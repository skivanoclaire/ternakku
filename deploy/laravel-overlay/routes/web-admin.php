<?php
/**
 * Tambahkan ke routes/web.php. Login admin = route auth bawaan Laravel
 * (Breeze/Fortify) + pembatasan peran admin di grup ini.
 */

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        // tambahkan route admin lain di sini (kelola user/peran, audit, dll.)
    });
