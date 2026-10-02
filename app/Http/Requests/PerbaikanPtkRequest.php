<?php

namespace App\Http\Requests;

use App\Models\PtkSubmission;

/**
 * Validasi perbaikan data saat Perlu Revisi (SPEC.md §5.3): aturan sama dengan pengajuan baru,
 * NIK milik pengajuan ini sendiri diabaikan saat cek unik, dan berkas boleh tidak diganti.
 * Pengajuan diambil dari middleware EnsurePerbaikanDiizinkan.
 */
class PerbaikanPtkRequest extends PtkSubmissionRequest
{
    protected function submission(): ?PtkSubmission
    {
        return $this->attributes->get('ptk_submission');
    }
}
