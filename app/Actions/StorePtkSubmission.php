<?php

namespace App\Actions;

use App\Enums\PtkStatus;
use App\Models\District;
use App\Models\Province;
use App\Models\PtkSubmission;
use App\Models\Regency;
use App\Models\Village;
use App\Support\SubmitId;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Menyimpan pengajuan PTK baru (REQ-04, REQ-06, REQ-15):
 * berkas ke disk privat, nama wilayah dari kode, Submit ID WIB, dan riwayat awal — dalam satu transaction.
 */
class StorePtkSubmission
{
    public const DISK = 'local';

    /**
     * @param  array<string, mixed>  $data  data tervalidasi dari PtkSubmissionRequest
     */
    public function handle(array $data, UploadedFile $fotoKtp, UploadedFile $skPenugasan): PtkSubmission
    {
        $paths = [
            'foto_ktp_path' => $fotoKtp->store('ptk/ktp', self::DISK),
            'sk_penugasan_path' => $skPenugasan->store('ptk/sk', self::DISK),
        ];

        try {
            return DB::transaction(function () use ($data, $paths) {
                $submission = new PtkSubmission([
                    ...Arr::except($data, ['foto_ktp', 'sk_penugasan']),
                    ...self::namaWilayah($data),
                    ...$paths,
                    'persetujuan' => true,
                ]);
                $submission->submit_id = SubmitId::generate();
                $submission->status = PtkStatus::Menunggu;
                $submission->save();

                $submission->statusHistories()->create([
                    'actor' => 'PTK',
                    'from_status' => null,
                    'to_status' => PtkStatus::Menunggu,
                    'note' => 'Pengajuan baru',
                ]);

                return $submission;
            });
        } catch (Throwable $e) {
            // Data gagal tersimpan → jangan tinggalkan berkas yatim di disk
            Storage::disk(self::DISK)->delete(array_values($paths));

            throw $e;
        }
    }

    /**
     * Nama wilayah disimpan bersama kodenya (SPEC.md §4.1).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string>
     */
    public static function namaWilayah(array $data): array
    {
        return [
            'provinsi' => Province::findOrFail($data['provinsi_kode'])->name,
            'kabupaten' => Regency::findOrFail($data['kabupaten_kode'])->name,
            'kecamatan' => District::findOrFail($data['kecamatan_kode'])->name,
            'desa' => Village::findOrFail($data['desa_kode'])->name,
        ];
    }
}
