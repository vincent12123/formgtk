<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Data awal aplikasi (SPEC.md §8A.1).
     */
    public function run(): void
    {
        $this->call([
            SchoolSeeder::class,
            WilayahSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
