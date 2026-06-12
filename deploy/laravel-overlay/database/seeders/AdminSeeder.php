<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Buat akun admin awal & beri peran admin.
 * GANTI password setelah login pertama!
 * Default:  email admin@ternakku.test  /  password admin12345
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@ternakku.test')],
            [
                'name'      => 'Administrator',
                'password'  => Hash::make(env('ADMIN_PASSWORD', 'admin12345')),
                'is_active' => true,
            ],
        );

        $role = Role::where('name', 'admin')->first();
        if ($role) {
            $admin->roles()->syncWithoutDetaching([$role->id]);
        }
    }
}
