<?php

namespace App\Actions;

use App\Enums\PtkStatus;
use App\Models\PtkStatusHistory;
use App\Models\PtkSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Perubahan status oleh admin (REQ-12, REQ-15): perpindahan bebas antar 4 status,
 * Perlu Revisi wajib catatan, dan setiap perubahan mencatat tepat satu riwayat dalam satu transaction.
 */
class ChangePtkStatus
{
    public const PESAN_CATATAN_WAJIB = 'Catatan revisi wajib diisi.';

    /**
     * @return PtkStatusHistory|null riwayat baru, atau null bila tidak ada yang berubah
     */
    public function handle(PtkSubmission $submission, PtkStatus $status, ?string $catatan, User $admin): ?PtkStatusHistory
    {
        $catatan = filled($catatan) ? trim($catatan) : null;

        if ($status === PtkStatus::PerluRevisi && $catatan === null) {
            throw ValidationException::withMessages(['catatan' => self::PESAN_CATATAN_WAJIB]);
        }

        // Catatan revisi hanya berlaku selama status Perlu Revisi; status lain mengosongkannya
        $catatanRevisi = $status === PtkStatus::PerluRevisi ? $catatan : null;

        if ($submission->status === $status && $submission->catatan_revisi === $catatanRevisi) {
            return null;
        }

        return DB::transaction(function () use ($submission, $status, $catatan, $catatanRevisi, $admin) {
            $statusLama = $submission->status;

            $submission->status = $status;
            $submission->catatan_revisi = $catatanRevisi;
            $submission->save();

            return $submission->statusHistories()->create([
                'actor' => $admin->name,
                'from_status' => $statusLama,
                'to_status' => $status,
                'note' => $catatan,
            ]);
        });
    }
}
