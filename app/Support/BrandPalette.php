<?php

namespace App\Support;

/**
 * Membentuk palet warna Filament (shade 50–950) yang tertambat pada token design system.
 *
 * Color::hex() bawaan Filament hanya mengambil hue sehingga shade 600 (tombol, link)
 * tidak sama dengan token. Di sini shade 600 = token, shade lebih terang dicampur ke arah
 * $lightest dan shade lebih gelap ke arah $darkest (docs/design-system.md §2).
 */
class BrandPalette
{
    /**
     * Bobot campuran tiap shade: shade < 600 menuju $lightest, shade > 600 menuju $darkest.
     */
    private const WEIGHTS = [
        50 => 1.0,
        100 => 0.86,
        200 => 0.68,
        300 => 0.5,
        400 => 0.32,
        500 => 0.16,
        600 => 0.0,
        700 => 0.25,
        800 => 0.5,
        900 => 0.75,
        950 => 1.0,
    ];

    /**
     * @return array<int, string>
     */
    public static function make(string $base, string $lightest = '#FFFFFF', string $darkest = '#000000'): array
    {
        $palette = [];

        foreach (self::WEIGHTS as $shade => $weight) {
            $target = $shade <= 600 ? $lightest : $darkest;

            // Shade gelap tanpa token tujuan dibatasi agar tidak menjadi hitam pekat
            if ($shade > 600 && strcasecmp($darkest, '#000000') === 0) {
                $weight *= 0.6;
            }

            $palette[$shade] = self::mix($base, $target, $weight);
        }

        return $palette;
    }

    private static function mix(string $from, string $to, float $weight): string
    {
        [$r1, $g1, $b1] = sscanf(ltrim($from, '#'), '%02x%02x%02x');
        [$r2, $g2, $b2] = sscanf(ltrim($to, '#'), '%02x%02x%02x');

        return sprintf(
            '#%02X%02X%02X',
            (int) round($r1 + ($r2 - $r1) * $weight),
            (int) round($g1 + ($g2 - $g1) * $weight),
            (int) round($b1 + ($b2 - $b1) * $weight),
        );
    }
}
