<?php
/**
 * Tambahkan baris-baris ini ke routes/api.php proyek Laravel.
 * (di Laravel 11, aktifkan dulu routing api: `php artisan install:api`)
 */

use App\Http\Controllers\Api\EstimasiController;
use Illuminate\Support\Facades\Route;

Route::post('/estimasi/bobot', [EstimasiController::class, 'bobot']);
Route::get('/ml/health', [EstimasiController::class, 'health']);

// Endpoint di atas tersedia sebagai:
//   POST https://ternakku.kaltaraprov.web.id/api/estimasi/bobot
//   GET  https://ternakku.kaltaraprov.web.id/api/ml/health
