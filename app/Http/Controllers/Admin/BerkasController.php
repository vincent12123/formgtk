<?php

namespace App\Http\Controllers\Admin;

use App\Actions\StorePtkSubmission;
use App\Http\Controllers\Controller;
use App\Models\PtkSubmission;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Membuka berkas Foto KTP / SK Penugasan dari disk privat — hanya untuk admin yang login (REQ-04, NFR-21).
 */
class BerkasController extends Controller
{
    private const KOLOM = [
        'ktp' => 'foto_ktp_path',
        'sk' => 'sk_penugasan_path',
    ];

    public function show(PtkSubmission $submission, string $jenis): StreamedResponse
    {
        $path = $submission->{self::KOLOM[$jenis]};
        $disk = Storage::disk(StorePtkSubmission::DISK);

        abort_unless($path && $disk->exists($path), 404);

        $nama = sprintf('%s-%s.%s', $jenis, $submission->submit_id, pathinfo($path, PATHINFO_EXTENSION));

        return $disk->response($path, $nama, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
