<?php

namespace App\Support;

use App\Models\PtkSubmission;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Pembuat Submit ID pengajuan: PTK-YYYYMMDD-HHmmss-XXXX (waktu WIB, XXXX = 4 digit acak) — REQ-06.
 */
class SubmitId
{
    public const PATTERN = '/^PTK-\d{8}-\d{6}-\d{4}$/';

    private const MAX_ATTEMPTS = 20;

    public static function generate(?Carbon $at = null): string
    {
        $timestamp = ($at ?? now())->copy()->setTimezone('Asia/Jakarta')->format('Ymd-His');

        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $id = sprintf('PTK-%s-%04d', $timestamp, random_int(0, 9999));

            if (! PtkSubmission::where('submit_id', $id)->exists()) {
                return $id;
            }
        }

        throw new RuntimeException('Gagal membuat Submit ID yang unik.');
    }
}
