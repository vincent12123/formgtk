<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

/**
 * Akun admin pertama dari .env (ADMIN_NAME, ADMIN_EMAIL, ADMIN_PASSWORD).
 * Bila akun sudah ada, kata sandinya tidak ditimpa.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = config('ptk.admin');

        if (blank($admin['email']) || blank($admin['password'])) {
            throw new RuntimeException('ADMIN_EMAIL dan ADMIN_PASSWORD wajib diisi di .env sebelum menjalankan seeder.');
        }

        User::firstOrCreate(
            ['email' => $admin['email']],
            [
                'name' => filled($admin['name']) ? $admin['name'] : 'Admin Yayasan',
                'password' => $admin['password'],
            ],
        );
    }
}
