<?php

namespace App\Actions;

use App\Enums\PtkStatus;
use App\Models\PtkSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Menyimpan perbaikan data oleh PTK (REQ-08, REQ-15): semua field boleh berubah, berkas yang
 * tidak diganti tetap dipakai, Submit ID tetap, status kembali Menunggu, dan riwayat tercatat —
 * dalam satu transaction. Berkas lama yang diganti baru dihapus setelah transaction berhasil.
 */
class UpdatePtkSubmission
{
    /**
     * @param  array<string, mixed>  $data  data tervalidasi dari PerbaikanPtkRequest
     */
    public function handle(PtkSubmission $submission, array $data, ?UploadedFile $fotoKtp, ?UploadedFile $skPenugasan): PtkSubmission
    {
        $disk = Storage::disk(StorePtkSubmission::DISK);
        $baru = array_filter([
            'foto_ktp_path' => $fotoKtp?->store('ptk/ktp', StorePtkSubmission::DISK),
            'sk_penugasan_path' => $skPenugasan?->store('ptk/sk', StorePtkSubmission::DISK),
        ]);
        $lama = Arr::only($submission->getAttributes(), array_keys($baru));

        try {
            DB::transaction(function () use ($submission, $data, $baru) {
                $statusLama = $submission->status;

                $submission->fill([
                    ...Arr::except($data, ['foto_ktp', 'sk_penugasan', 'persetujuan']),
                    ...StorePtkSubmission::namaWilayah($data),
                    ...$baru,
                ]);
                $submission->status = PtkStatus::Menunggu;
                $submission->save();

                $submission->statusHistories()->create([
                    'actor' => 'PTK',
                    'from_status' => $statusLama,
                    'to_status' => PtkStatus::Menunggu,
                    'note' => 'Perbaikan data oleh PTK',
                ]);
            });
        } catch (Throwable $e) {
            $disk->delete(array_values($baru));

            throw $e;
        }

        $disk->delete(array_values($lama));

        return $submission;
    }
}
