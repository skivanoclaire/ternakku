<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Seed peran & permission dasar. Jalankan: php artisan db:seed --class=RoleSeeder
 * (atau panggil dari DatabaseSeeder).
 */
class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'admin'    => 'Administrator',
            'student'  => 'Student (researcher, tanpa kelola pengguna)',
            'peternak' => 'Peternak',
            'pembeli'  => 'Pembeli',
        ];
        foreach ($roles as $name => $label) {
            Role::firstOrCreate(['name' => $name], ['label' => $label]);
        }

        $perms = [
            'ternak.update'  => 'Ubah data ternak',
            'harga.update'   => 'Ubah harga',
            'listing.verify' => 'Verifikasi listing',
            'admin.access'   => 'Akses panel admin',
        ];
        foreach ($perms as $name => $label) {
            Permission::firstOrCreate(['name' => $name], ['label' => $label]);
        }

        // admin mendapat semua permission
        Role::where('name', 'admin')->first()
            ?->permissions()->sync(Permission::pluck('id'));
    }
}
