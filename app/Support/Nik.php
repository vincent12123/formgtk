<?php

namespace App\Support;

/**
 * Penyamaran NIK untuk halaman publik (REQ-07, NFR-21): hanya 4 digit awal dan akhir yang tampil.
 * Contoh: 6171010101900001 → 6171********0001.
 */
class Nik
{
    public static function mask(?string $nik): string
    {
        $nik = (string) $nik;

        if (strlen($nik) <= 8) {
            return str_repeat('*', strlen($nik));
        }

        return substr($nik, 0, 4).str_repeat('*', strlen($nik) - 8).substr($nik, -4);
    }
}
