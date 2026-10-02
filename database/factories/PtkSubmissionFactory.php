<?php

namespace Database\Factories;

use App\Models\PtkSubmission;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Data pengajuan PTK palsu untuk test. Pilihan diambil dari config/ptk.php agar selalu valid.
 * Butuh data sekolah (SchoolSeeder) sudah ada.
 *
 * @extends Factory<PtkSubmission>
 */
class PtkSubmissionFactory extends Factory
{
    public function definition(): array
    {
        $jenis = fake()->randomElement(['Guru', 'Tenaga Kependidikan']);

        return [
            'submit_id' => 'PTK-'.now()->format('Ymd-His').'-'.fake()->unique()->numerify('####'),
            'nik' => fake()->unique()->numerify('################'),
            'nama' => fake()->name(),
            'tempat_lahir' => fake()->city(),
            'tanggal_lahir' => fake()->date('Y-m-d', '-25 years'),
            'jenis_kelamin' => fake()->randomElement(config('ptk.jenis_kelamin')),
            'nama_ibu_kandung' => fake()->name('female'),
            'agama' => fake()->randomElement(config('ptk.agama')),
            'kebutuhan_khusus' => 'Tidak ada',
            'status_perkawinan' => fake()->randomElement(config('ptk.status_perkawinan')),
            'email' => fake()->unique()->safeEmail(),
            'foto_ktp_path' => 'ptk/ktp/contoh.jpg',
            'provinsi_kode' => '61',
            'provinsi' => 'KALIMANTAN BARAT',
            'kabupaten_kode' => '6171',
            'kabupaten' => 'KOTA PONTIANAK',
            'kecamatan_kode' => '6171010',
            'kecamatan' => 'PONTIANAK SELATAN',
            'desa_kode' => '6171010001',
            'desa' => 'BENUA MELAYU DARAT',
            'alamat' => fake()->streetAddress(),
            'kode_pos' => fake()->numerify('78###'),
            'jenis_ptk' => $jenis,
            'jabatan_ptk' => fake()->randomElement(config("ptk.jabatan.{$jenis}")),
            'status_kepegawaian' => fake()->randomElement(config('ptk.status_kepegawaian')),
            'lembaga_pengangkat' => fake()->randomElement(config('ptk.lembaga_pengangkat')),
            'sk_pengangkatan' => fake()->bothify('SK/###/YHKB/####'),
            'tmt_pengangkatan' => fake()->date('Y-m-d', '-1 year'),
            'sumber_gaji' => fake()->randomElement(config('ptk.sumber_gaji')),
            'lisensi_kepsek' => null,
            'school_npsn' => fn () => School::query()->inRandomOrder()->value('npsn'),
            'nomor_surat_tugas' => fake()->bothify('ST/###/YHKB/####'),
            'tanggal_surat_tugas' => fake()->date('Y-m-d', '-1 year'),
            'tmt_tugas' => fake()->date('Y-m-d', '-1 year'),
            'sk_penugasan_path' => 'ptk/sk/contoh.pdf',
            'persetujuan' => true,
        ];
    }

    public function kepalaSekolah(): static
    {
        return $this->state([
            'jenis_ptk' => 'Kepala Sekolah',
            'jabatan_ptk' => 'Kepala Sekolah',
            'lisensi_kepsek' => 'Sudah',
        ]);
    }
}
