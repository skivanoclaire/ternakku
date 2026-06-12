<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Api\EstimasiController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// --- Publik ---
Route::view('/', 'welcome')->name('beranda');
Route::post('/estimasi/bobot', [EstimasiController::class, 'bobot'])->name('estimasi.bobot');

// --- Auth (login admin) ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// --- Area admin (RBAC: auth + role:admin) ---
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    });
