<?php

use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Data wilayah uji kecil: Kalbar (Pontianak, Sambas, Sintang), Sumut (Medan), Aceh.
 */
function isiWilayahUji(): void
{
    Province::insert([
        ['id' => '11', 'name' => 'ACEH'],
        ['id' => '61', 'name' => 'KALIMANTAN BARAT'],
        ['id' => '12', 'name' => 'SUMATERA UTARA'],
    ]);
    Regency::insert([
        ['id' => '6171', 'parent_id' => '61', 'name' => 'KOTA PONTIANAK'],
        ['id' => '6101', 'parent_id' => '61', 'name' => 'KABUPATEN SAMBAS'],
        ['id' => '6107', 'parent_id' => '61', 'name' => 'KABUPATEN SINTANG'],
        ['id' => '1271', 'parent_id' => '12', 'name' => 'KOTA MEDAN'],
    ]);
    District::insert([
        ['id' => '6171010', 'parent_id' => '6171', 'name' => 'PONTIANAK SELATAN'],
        ['id' => '1271010', 'parent_id' => '1271', 'name' => 'MEDAN TUNTUNGAN'],
    ]);
    Village::insert([
        ['id' => '6171010001', 'parent_id' => '6171010', 'name' => 'BENUA MELAYU DARAT'],
        ['id' => '1271010001', 'parent_id' => '1271010', 'name' => 'SIMPANG SELAYANG'],
    ]);
}

/**
 * Data pengajuan valid lengkap (wilayah memakai fixture Kota Pontianak di beforeEach).
 *
 * @return array<string, mixed>
 */
function pengajuanValid(array $override = []): array
{
    return array_merge([
        'nik' => '6171010101900001',
        'nama' => 'Budi Santoso',
        'tempat_lahir' => 'Sintang',
        'tanggal_lahir' => '1990-01-01',
        'jenis_kelamin' => 'Laki-laki',
        'nama_ibu_kandung' => 'Siti Aminah',
        'agama' => 'Islam',
        'kebutuhan_khusus' => 'Tidak ada',
        'status_perkawinan' => 'Menikah',
        'email' => 'budi@example.com',
        'foto_ktp' => UploadedFile::fake()->image('ktp.jpg')->size(300),
        'provinsi_kode' => '61',
        'kabupaten_kode' => '6171',
        'kecamatan_kode' => '6171010',
        'desa_kode' => '6171010001',
        'alamat' => 'Jl. Merdeka No. 1',
        'kode_pos' => '78611',
        'jenis_ptk' => 'Guru',
        'jabatan_ptk' => 'Guru TIK',
        'status_kepegawaian' => 'GTY/PTY',
        'lembaga_pengangkat' => 'Ketua Yayasan',
        'sk_pengangkatan' => 'SK/01/YHKB/2024',
        'tmt_pengangkatan' => '2024-07-15',
        'sumber_gaji' => 'Yayasan',
        'lisensi_kepsek' => '',
        'school_npsn' => '70004213',
        'nomor_surat_tugas' => 'ST/02/YHKB/2024',
        'tanggal_surat_tugas' => '2024-07-01',
        'tmt_tugas' => '2024-07-15',
        'sk_penugasan' => UploadedFile::fake()->create('sk.pdf', 300, 'application/pdf'),
        'persetujuan' => '1',
    ], $override);
}

/**
 * Ambil konfigurasi Alpine form PTK yang dikirim sebagai JSON.parse('...') hasil @js.
 */
function ptkFormConfig(string $html): array
{
    preg_match("/ptkForm\(JSON\.parse\('(.*?)'\)\)/s", html_entity_decode($html), $match);

    return json_decode(json_decode('"'.$match[1].'"'), true);
}
