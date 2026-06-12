<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DataController;
use App\Http\Controllers\Admin\ResearcherController;
use App\Http\Controllers\Api\EstimasiController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Peternak\PengukuranController;
use App\Http\Controllers\Peternak\TernakController;
use Illuminate\Support\Facades\Route;

// --- Publik ---
Route::view('/', 'welcome')->name('beranda');
Route::post('/estimasi/bobot', [EstimasiController::class, 'bobot'])->name('estimasi.bobot');

// --- Auth ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// --- Area peternak (RBAC: auth + role:peternak) ---
Route::middleware(['auth', 'role:peternak'])->group(function () {
    Route::get('/ternak', [TernakController::class, 'index'])->name('ternak.index');
    Route::get('/ternak/tambah', [TernakController::class, 'create'])->name('ternak.create');
    Route::post('/ternak', [TernakController::class, 'store'])->name('ternak.store');
    Route::get('/ternak/{ternak}', [TernakController::class, 'show'])->name('ternak.show');
    Route::post('/ternak/{ternak}/ukur', [PengukuranController::class, 'store'])->name('pengukuran.store');
});

// --- Area admin (RBAC: auth + role:admin) ---
Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')->name('admin.')
    ->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Researcher workbench (Modul 1)
        Route::get('/data', [DataController::class, 'index'])->name('data');
        Route::post('/data/import', [DataController::class, 'import'])->name('data.import');
        Route::get('/eda', [ResearcherController::class, 'eda'])->name('eda');
        Route::get('/latih', [ResearcherController::class, 'latihForm'])->name('latih');
        Route::post('/latih', [ResearcherController::class, 'latihStore'])->name('latih.store');
        Route::get('/eksperimen/{experiment}', [ResearcherController::class, 'eksperimen'])->name('eksperimen');
        Route::get('/leaderboard', [ResearcherController::class, 'leaderboard'])->name('leaderboard');
        Route::get('/model', [ResearcherController::class, 'modelIndex'])->name('model');
        Route::post('/model/{experiment}/promote', [ResearcherController::class, 'promote'])->name('model.promote');
    });
