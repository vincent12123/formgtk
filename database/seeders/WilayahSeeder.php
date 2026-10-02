<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;

/**
 * Impor data wilayah dari CSV emsifa/api-wilayah-indonesia v1 (SPEC.md §4.4).
 *
 * Berkas di database/data/ diambil dari commit ebc5151 — commit terakhir sebelum data v1
 * dihapus dari repositori (2026-09-01) — sehingga datanya sama dengan API yang dipakai form lama.
 * Tabel yang sudah terisi dilewati, jadi seeder aman dijalankan ulang.
 */
class WilayahSeeder extends Seeder
{
    /**
     * Urutan impor mengikuti jenjang induk → anak. Format CSV:
     * provinces: id,name — lainnya: id,parent_id,name (tanpa baris judul).
     */
    private const FILES = [
        'provinces' => false,
        'regencies' => true,
        'districts' => true,
        'villages' => true,
    ];

    private const CHUNK = 500;

    public function run(): void
    {
        foreach (self::FILES as $table => $hasParent) {
            if (DB::table($table)->exists()) {
                $this->command?->info("{$table}: sudah terisi, dilewati.");

                continue;
            }

            $count = $this->import($table, $hasParent);
            $this->command?->info("{$table}: {$count} baris diimpor.");
        }
    }

    private function import(string $table, bool $hasParent): int
    {
        $path = database_path("data/{$table}.csv");

        if (! is_file($path)) {
            throw new RuntimeException("Berkas data wilayah tidak ditemukan: {$path}");
        }

        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD | SplFileObject::DROP_NEW_LINE);

        $rows = [];
        $seen = [];
        $count = 0;

        DB::transaction(function () use ($file, $hasParent, $table, &$rows, &$seen, &$count) {
            foreach ($file as $columns) {
                if ($columns === [null] || $columns === false) {
                    continue;
                }

                // Sumber berisi kode ganda (mis. 2 kode desa di Papua Barat Daya); kode adalah
                // kunci, jadi baris pertama dipakai dan sisanya dilaporkan.
                $id = trim($columns[0]);

                if (isset($seen[$id])) {
                    $this->command?->warn("{$table}: kode {$id} ganda, baris \"".implode(',', $columns).'" dilewati.');

                    continue;
                }

                $seen[$id] = true;

                $rows[] = $hasParent
                    ? ['id' => trim($columns[0]), 'parent_id' => trim($columns[1]), 'name' => trim($columns[2])]
                    : ['id' => trim($columns[0]), 'name' => trim($columns[1])];

                if (count($rows) === self::CHUNK) {
                    DB::table($table)->insert($rows);
                    $count += count($rows);
                    $rows = [];
                }
            }

            if ($rows !== []) {
                DB::table($table)->insert($rows);
                $count += count($rows);
            }
        });

        return $count;
    }
}
