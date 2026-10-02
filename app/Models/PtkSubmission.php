<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Enums\PtkStatus;
use Database\Factories\PtkSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pengajuan data PTK (SPEC.md §4.1).
 */
#[Guarded(['id', 'submit_id', 'status', 'catatan_revisi'])]
class PtkSubmission extends Model
{
    /** @use HasFactory<PtkSubmissionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_lahir' => DateOnly::class,
            'tmt_pengangkatan' => DateOnly::class,
            'tanggal_surat_tugas' => DateOnly::class,
            'tmt_tugas' => DateOnly::class,
            'persetujuan' => 'boolean',
            'status' => PtkStatus::class,
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_npsn', 'npsn');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(PtkStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }
}
