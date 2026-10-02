<?php

namespace App\Models;

use App\Enums\PtkStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Riwayat status pengajuan (SPEC.md §4.5, NFR-20).
 * Hanya boleh ditambah; mengubah atau menghapus entri ditolak.
 */
#[Fillable(['ptk_submission_id', 'actor', 'from_status', 'to_status', 'note'])]
class PtkStatusHistory extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => PtkStatus::class,
            'to_status' => PtkStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Riwayat status tidak boleh diubah.'));
        static::deleting(fn () => throw new LogicException('Riwayat status tidak boleh dihapus.'));
    }

    public function ptkSubmission(): BelongsTo
    {
        return $this->belongsTo(PtkSubmission::class);
    }
}
