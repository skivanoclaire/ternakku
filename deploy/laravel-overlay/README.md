# Laravel overlay — integrasi ke service ML

File di folder ini **bukan** proyek Laravel utuh. Laravel digenerate di VM lewat
`composer create-project`, lalu file-file ini **disalin masuk** ke proyek tersebut.
Dipisah begini supaya `composer create-project` (butuh folder `web/` kosong) tidak bentrok.

## Isi & tujuan tiap file
| File overlay | Disalin ke | Fungsi |
|---|---|---|
| `Dockerfile` | `web/Dockerfile` | image PHP-FPM 8.3 + pdo_pgsql + redis |
| `app/Services/MlClient.php` | `web/app/Services/MlClient.php` | klien HTTP ke `http://ml:8000` |
| `app/Http/Controllers/Api/EstimasiController.php` | `web/app/Http/Controllers/Api/` | endpoint estimasi bobot |
| `routes/api-ternakku.php` | gabung ke `web/routes/api.php` | daftarkan route `/api/estimasi/bobot` |
| `config-services-snippet.php` | gabung ke `web/config/services.php` | tambah `services.ml.url` |
| `database/migrations/2026_..._create_ternakku_tables.php` | `web/database/migrations/` | tabel inti (ternak, pengukuran, dll.) |

## Urutan menerapkan (di VM, dijalankan otomatis lewat skrip — lihat ../DEPLOY_VM.md §6)
1. Generate Laravel ke `web/`.
2. `php artisan install:api`  (Laravel 11: aktifkan routing `routes/api.php`).
3. Salin file overlay ke posisinya masing-masing.
4. Tambahkan isi `routes/api-ternakku.php` ke akhir `routes/api.php`.
5. Tambahkan blok `'ml' => [...]` dari `config-services-snippet.php` ke `config/services.php`.
6. Set `.env` DB ke service `db`, dan `ML_URL=http://ml:8000`.
7. `php artisan migrate --force`.

## Uji
```bash
curl -X POST https://ternakku.kaltaraprov.web.id/api/estimasi/bobot \
  -H "Content-Type: application/json" \
  -d '{"lingkar_dada_cm":195,"panjang_badan_cm":165,"tinggi_gumba_cm":128}'
# -> {"bobot_estimasi_kg":..., "p10":..., "p90":..., "model_ver":"..."}
```
