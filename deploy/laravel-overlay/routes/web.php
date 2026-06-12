<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ResearcherController;
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

        // Researcher workbench (Modul 1)
        Route::get('/eda', [ResearcherController::class, 'eda'])->name('eda');
        Route::get('/latih', [ResearcherController::class, 'latihForm'])->name('latih');
        Route::post('/latih', [ResearcherController::class, 'latihStore'])->name('latih.store');
        Route::get('/eksperimen/{experiment}', [ResearcherController::class, 'eksperimen'])->name('eksperimen');
        Route::get('/leaderboard', [ResearcherController::class, 'leaderboard'])->name('leaderboard');
        Route::get('/model', [ResearcherController::class, 'modelIndex'])->name('model');
        Route::post('/model/{experiment}/promote', [ResearcherController::class, 'promote'])->name('model.promote');
    });
