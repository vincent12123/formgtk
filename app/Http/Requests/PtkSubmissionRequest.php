<?php

namespace App\Http\Requests;

use App\Models\District;
use App\Models\PtkSubmission;
use App\Models\Regency;
use App\Models\Village;
use App\Models\Wilayah;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Satu sumber aturan validasi data PTK (SPEC.md §4.1, §5.1) untuk pengajuan baru.
 * Form perbaikan (CP4) memakai turunan kelas ini dengan submission() terisi:
 * NIK miliknya sendiri diabaikan dan berkas boleh tidak diganti.
 */
class PtkSubmissionRequest extends FormRequest
{
    public const MAX_FILE_KB = 1024;

    // Pesan NIK ganda mengarahkan ke menu Cek Status (REQ-05)
    public const NIK_TERDAFTAR = 'NIK ini sudah pernah mengirim data. Silakan gunakan menu Cek Status untuk melihat pengajuan Bapak/Ibu.';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Pengajuan yang sedang diperbaiki; null untuk pengajuan baru.
     */
    protected function submission(): ?PtkSubmission
    {
        return null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nik' => is_string($this->nik) ? trim($this->nik) : $this->nik,
            'email' => is_string($this->email) ? trim($this->email) : $this->email,
            // Lisensi hanya berlaku untuk Kepala Sekolah; selain itu selalu kosong (AC-08)
            'lisensi_kepsek' => $this->input('jenis_ptk') === 'Kepala Sekolah' ? $this->input('lisensi_kepsek') : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $existing = $this->submission();
        $berkas = $existing ? 'nullable' : 'required';
        $tanggal = ['required', 'date_format:Y-m-d', 'after:1900-01-01'];
        $pilihan = fn (string $key) => ['required', 'string', Rule::in(config("ptk.{$key}"))];

        return [
            // Identitas
            'nik' => ['required', 'digits:16', Rule::unique('ptk_submissions', 'nik')->ignore($existing?->id)],
            'nama' => ['required', 'string', 'max:255'],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => [...$tanggal, 'before:today'],
            'jenis_kelamin' => $pilihan('jenis_kelamin'),
            'nama_ibu_kandung' => ['required', 'string', 'max:255'],
            'agama' => $pilihan('agama'),
            'kebutuhan_khusus' => $pilihan('kebutuhan_khusus'),
            'status_perkawinan' => $pilihan('status_perkawinan'),
            'email' => ['required', 'email:rfc', 'max:255'],
            'foto_ktp' => [$berkas, 'file', 'mimes:jpg,jpeg,png', 'mimetypes:image/jpeg,image/png', 'max:'.self::MAX_FILE_KB],

            // Domisili — setiap kode harus anak dari kode induk yang dipilih (AC-10)
            'provinsi_kode' => ['required', 'string', Rule::exists('provinces', 'id')],
            'kabupaten_kode' => ['required', 'string', $this->childOf(Regency::class, 'provinsi_kode', 'Kabupaten/Kota', 'Provinsi')],
            'kecamatan_kode' => ['required', 'string', $this->childOf(District::class, 'kabupaten_kode', 'Kecamatan', 'Kabupaten/Kota')],
            'desa_kode' => ['required', 'string', $this->childOf(Village::class, 'kecamatan_kode', 'Desa/Kelurahan', 'Kecamatan')],
            'alamat' => ['required', 'string', 'max:1000'],
            'kode_pos' => ['nullable', 'string', 'max:10'],

            // Kepegawaian — jabatan selalu dicek terhadap config/ptk.php (AC-07)
            'jenis_ptk' => ['required', 'string', Rule::in(array_keys(config('ptk.jabatan')))],
            'jabatan_ptk' => ['required', 'string', $this->jabatanSesuaiJenis()],
            'status_kepegawaian' => $pilihan('status_kepegawaian'),
            'lembaga_pengangkat' => $pilihan('lembaga_pengangkat'),
            'sk_pengangkatan' => ['required', 'string', 'max:255'],
            'tmt_pengangkatan' => $tanggal,
            'sumber_gaji' => $pilihan('sumber_gaji'),
            'lisensi_kepsek' => ['nullable', 'required_if:jenis_ptk,Kepala Sekolah', Rule::in(config('ptk.lisensi_kepsek'))],

            // Penugasan
            'school_npsn' => ['required', 'string', Rule::exists('schools', 'npsn')],
            'nomor_surat_tugas' => ['required', 'string', 'max:255'],
            'tanggal_surat_tugas' => $tanggal,
            'tmt_tugas' => $tanggal,
            'sk_penugasan' => [$berkas, 'file', 'mimes:pdf', 'mimetypes:application/pdf', 'max:'.self::MAX_FILE_KB],
            'persetujuan' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nik.digits' => 'NIK harus tepat 16 digit angka.',
            'nik.unique' => self::NIK_TERDAFTAR,
            'foto_ktp.mimes' => 'Foto KTP harus berupa berkas JPG atau PNG.',
            'foto_ktp.mimetypes' => 'Foto KTP harus berupa berkas JPG atau PNG.',
            'foto_ktp.max' => 'Ukuran Foto KTP maksimal 1 MB.',
            'sk_penugasan.mimes' => 'SK Penugasan harus berupa berkas PDF.',
            'sk_penugasan.mimetypes' => 'SK Penugasan harus berupa berkas PDF.',
            'sk_penugasan.max' => 'Ukuran SK Penugasan maksimal 1 MB.',
            'foto_ktp.required' => 'Foto KTP wajib diunggah.',
            'sk_penugasan.required' => 'SK Penugasan wajib diunggah.',
            'lisensi_kepsek.required_if' => 'Lisensi Kepala Sekolah wajib dipilih untuk Jenis PTK Kepala Sekolah.',
            'persetujuan.accepted' => 'Bapak/Ibu wajib menyetujui pernyataan.',
            'tanggal_lahir.before' => 'Tanggal Lahir harus sebelum hari ini.',
            '*.date_format' => ':Attribute harus berupa tanggal yang valid.',
            '*.after' => ':Attribute harus berupa tanggal yang valid.',
        ];
    }

    private function jabatanSesuaiJenis(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            $daftar = config('ptk.jabatan.'.$this->input('jenis_ptk'), []);

            if (! in_array($value, $daftar, true)) {
                $fail('Jabatan PTK tidak sesuai dengan Jenis PTK.');
            }
        };
    }

    /**
     * Kode wilayah harus ada dan induknya sama dengan kode yang dipilih di tingkat atas.
     *
     * @param  class-string<Wilayah>  $model
     */
    private function childOf(string $model, string $parentField, string $label, string $parentLabel): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($model, $parentField, $label, $parentLabel) {
            $cocok = $model::query()
                ->whereKey($value)
                ->where('parent_id', (string) $this->input($parentField))
                ->exists();

            if (! $cocok) {
                $fail("{$label} tidak sesuai dengan {$parentLabel} yang dipilih.");
            }
        };
    }
}
