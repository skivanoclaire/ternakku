# Overlay keamanan web — Login admin, RBAC, ABAC, Audit trail

File-file ini melengkapi aplikasi web Laravel dengan: menu **login admin**,
**RBAC** (peran), **ABAC** (atribut/kepemilikan), dan **audit trail**.
Disalin ke proyek Laravel setelah `composer create-project` (lihat README.md utama).

> Catatan: ini lapisan **Laravel + PostgreSQL** (service `app` + `db`), bukan
> service ML. Untuk mengaktifkannya, jalankan stack penuh (`docker-compose.yml`),
> bukan `docker-compose.modul1.yml` yang hanya ML.

## Isi & tujuan
| File overlay | Disalin ke | Peran |
|---|---|---|
| `database/migrations/..._create_rbac_audit_tables.php` | `web/database/migrations/` | tabel roles/permissions/pivot, kolom ABAC di users, tabel audits |
| `database/seeders/RoleSeeder.php` | `web/database/seeders/` | peran admin/peternak/pembeli + permission |
| `app/Models/{Role,Permission,Audit}.php` | `web/app/Models/` | model RBAC + audit |
| `app/Models/Concerns/HasRoles.php` | `web/app/Models/Concerns/` | trait untuk User (RBAC) |
| `app/Models/Concerns/Auditable.php` | `web/app/Models/Concerns/` | trait audit otomatis untuk model |
| `app/Http/Middleware/EnsureRole.php` | `web/app/Http/Middleware/` | middleware RBAC `role:admin` |
| `app/Policies/TernakPolicy.php` | `web/app/Policies/` | ABAC (kepemilikan/wilayah) |
| `app/Http/Controllers/Admin/DashboardController.php` | `web/app/Http/Controllers/Admin/` | area admin |
| `routes/web-admin.php` | gabung ke `web/routes/web.php` | route admin terlindungi |
| `AuthServiceProvider-snippet.php` | isi `boot()` `AppServiceProvider` | Gate::before admin + audit login |

## Langkah merangkai
1. **Login** — pasang scaffolding auth: `php artisan breeze:install blade` (atau Fortify/Sanctum untuk API).
2. **User model** — tambahkan ke `app/Models/User.php`:
   ```php
   use App\Models\Concerns\HasRoles;
   class User extends Authenticatable { use HasRoles; /* ... */ }
   ```
   dan masukkan `is_active`, `wilayah_id` ke `$fillable`.
3. **Model yang diaudit** — di `Ternak`, `Listing`, `HargaSnapshot` dst.:
   `use App\Models\Concerns\Auditable;` lalu `use Auditable;` di body kelas.
4. **Alias middleware** — di `bootstrap/app.php` (Laravel 11), dalam `withMiddleware`:
   ```php
   $middleware->alias(['role' => \App\Http\Middleware\EnsureRole::class]);
   ```
5. **Provider** — tempel isi `AuthServiceProvider-snippet.php` ke `boot()` `AppServiceProvider`.
6. **Migrasi & seed** — `php artisan migrate && php artisan db:seed --class=RoleSeeder`.
7. **Jadikan seseorang admin** — `tinker`: `User::find(1)->roles()->sync([Role::where('name','admin')->first()->id]);`

## Cara kerja RBAC vs ABAC (ringkas)
- **RBAC** menjawab "peran apa boleh masuk modul mana" → middleware `role:admin`, `hasRole()`, `hasPermission()`.
- **ABAC** menjawab "boleh atas objek/atribut yang mana" → `TernakPolicy` (kepemilikan `user_id`, kesamaan `wilayah_id`, `is_active`).
- **Admin override**: `Gate::before` memberi admin akses penuh, mendahului policy.

## Audit trail
- Perubahan model (create/update/delete) tercatat otomatis lewat trait `Auditable`.
- Login tercatat lewat listener di provider.
- Fokuskan pada **transaksi & perubahan harga** (sesuai DESIGN §12).
- Lihat 50 entri terbaru di dashboard admin (`/admin`).

## Alternatif paket matang (opsional)
Bisa diganti paket siap pakai: `spatie/laravel-permission` (RBAC) dan
`owen-it/laravel-auditing` / `spatie/laravel-activitylog` (audit). Overlay ini
sengaja tanpa dependensi eksternal agar ringan & transparan.
