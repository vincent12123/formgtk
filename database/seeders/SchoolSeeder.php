<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

/**
 * Enam unit sekolah yayasan (SPEC.md §4.2). Aman dijalankan berulang.
 */
class SchoolSeeder extends Seeder
{
    public const SCHOOLS = [
        '70027214' => 'KB GOLDEN BEE',
        '70011827' => 'SD KARYA BANGSA',
        '70050352' => 'SMA KARYA BANGSA',
        '70004213' => 'SMK KARYA BANGSA',
        '70011201' => 'SMP KARYA BANGSA',
        '70027216' => 'TK GOLDEN BEE',
    ];

    public function run(): void
    {
        foreach (self::SCHOOLS as $npsn => $name) {
            School::updateOrCreate(['npsn' => $npsn], ['name' => $name]);
        }
    }
}
