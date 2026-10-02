<?php

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Satu-satunya helper format tanggal tampilan: `d F Y` Indonesia dalam WIB (REQ-14, NFR-04).
 * Contoh: 02 Oktober 2026.
 */
class Tanggal
{
    public const FORMAT = 'd F Y';

    public const ZONA = 'Asia/Jakarta';

    public static function format(DateTimeInterface|string|null $tanggal): string
    {
        if ($tanggal === null || $tanggal === '') {
            return '-';
        }

        return Carbon::parse($tanggal)
            ->setTimezone(self::ZONA)
            ->locale('id')
            ->translatedFormat(self::FORMAT);
    }

    /**
     * Tanggal + jam WIB untuk jejak waktu (riwayat status). Contoh: 02 Oktober 2026, 08:30 WIB.
     */
    public static function formatWaktu(DateTimeInterface|string|null $waktu): string
    {
        if ($waktu === null || $waktu === '') {
            return '-';
        }

        return self::format($waktu).', '.Carbon::parse($waktu)->setTimezone(self::ZONA)->format('H:i').' WIB';
    }
}
