<?php

/*
 * Bagian C acceptance criteria — Perbaikan Data saat Perlu Revisi (SPEC.md §5.3).
 * Checkpoint 4: AC-30–34, AC-60 (form perbaikan), NF-20 (satu entri riwayat per perbaikan).
 */

use App\Enums\PtkStatus;
use App\Models\PtkSubmission;
use Database\Seeders\SchoolSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    isiWilayahUji();
    $this->seed(SchoolSeeder::class);

    Storage::disk('local')->put('ptk/ktp/lama.jpg', 'ktp-lama');
    Storage::disk('local')->put('ptk/sk/lama.pdf', 'sk-lama');

    $this->submission = PtkSubmission::factory()->create([
        'submit_id' => 'PTK-20261002-083045-1234',
        'nik' => '6171010101900001',
        'nama' => 'Budi Santoso',
        'tanggal_lahir' => '1990-01-15',
        'foto_ktp_path' => 'ptk/ktp/lama.jpg',
        'sk_penugasan_path' => 'ptk/sk/lama.pdf',
    ]);
    $this->submission->forceFill([
        'status' => PtkStatus::PerluRevisi,
        'catatan_revisi' => 'SK Penugasan belum ditandatangani.',
    ])->save();
});

function verifikasi(PtkSubmission $submission): void
{
    test()->post('/cek-status', [
        'submit_id' => $submission->submit_id,
        'tanggal_lahir' => $submission->tanggal_lahir->format('Y-m-d'),
    ])->assertRedirect(route('cek-status.hasil'));
}

/**
 * Data perbaikan: data valid baru, NIK tetap, tanpa mengganti berkas.
 */
function perbaikanValid(array $override = []): array
{
    return pengajuanValid(array_merge([
        'nik' => '6171010101900001',
        'tanggal_lahir' => '1990-01-15',
        'foto_ktp' => null,
        'sk_penugasan' => null,
    ], $override));
}

it('AC-30: menyimpan perubahan nama dan SK baru, status kembali Menunggu, Submit ID dan Foto KTP tetap', function () {
    verifikasi($this->submission);

    $this->put('/perbaikan', perbaikanValid([
        'nama' => 'Budi Santoso, S.Kom.',
        'sk_penugasan' => UploadedFile::fake()->create('sk-baru.pdf', 200, 'application/pdf'),
    ]))->assertSessionHasNoErrors()->assertRedirect(route('cek-status.hasil'));

    $baru = $this->submission->fresh();

    expect($baru->nama)->toBe('Budi Santoso, S.Kom.')
        ->and($baru->status)->toBe(PtkStatus::Menunggu)
        ->and($baru->submit_id)->toBe('PTK-20261002-083045-1234')
        ->and($baru->foto_ktp_path)->toBe('ptk/ktp/lama.jpg')
        ->and($baru->sk_penugasan_path)->not->toBe('ptk/sk/lama.pdf')
        ->toStartWith('ptk/sk/');

    Storage::disk('local')->assertExists('ptk/ktp/lama.jpg');
    Storage::disk('local')->assertExists($baru->sk_penugasan_path);
    Storage::disk('local')->assertMissing('ptk/sk/lama.pdf');
});

it('AC-30: mengabaikan submit_id dan status yang disisipkan di request', function () {
    verifikasi($this->submission);

    $this->put('/perbaikan', perbaikanValid(['submit_id' => 'PTK-HACK', 'status' => 'Valid']))
        ->assertSessionHasNoErrors();

    expect($this->submission->fresh())
        ->submit_id->toBe('PTK-20261002-083045-1234')
        ->status->toBe(PtkStatus::Menunggu);
});

it('menampilkan pesan berhasil di halaman status setelah perbaikan', function () {
    verifikasi($this->submission);

    $this->followingRedirects()->put('/perbaikan', perbaikanValid())
        ->assertOk()
        ->assertSee('Perbaikan data tersimpan')
        ->assertSee('data-status="Menunggu"', escape: false)
        ->assertDontSee('Perbaiki Data');
});

it('AC-31: menolak membuka dan mengirim perbaikan saat status bukan Perlu Revisi', function (PtkStatus $status) {
    $this->submission->forceFill(['status' => $status])->save();
    verifikasi($this->submission);

    $this->get('/perbaikan')->assertRedirect(route('cek-status.hasil'));
    expect(session('pesanUmum'))->toBe('Data hanya dapat diperbaiki saat status pengajuan Perlu Revisi.');

    $this->put('/perbaikan', perbaikanValid(['nama' => 'Diubah']))->assertRedirect(route('cek-status.hasil'));

    expect($this->submission->fresh()->nama)->toBe('Budi Santoso')
        ->and($this->submission->statusHistories()->count())->toBe(0);
})->with([PtkStatus::Valid, PtkStatus::Menunggu, PtkStatus::Diproses]);

it('AC-32: menolak perbaikan tanpa verifikasi Submit ID + tanggal lahir di sesi ini', function () {
    $this->get('/perbaikan')->assertRedirect(route('cek-status.create'));

    $this->put('/perbaikan', perbaikanValid(['nama' => 'Diubah']))->assertRedirect(route('cek-status.create'));

    expect($this->submission->fresh()->nama)->toBe('Budi Santoso')
        ->and($this->submission->fresh()->status)->toBe(PtkStatus::PerluRevisi);
});

it('AC-32: verifikasi yang gagal tidak membuka akses perbaikan', function () {
    $this->post('/cek-status', ['submit_id' => $this->submission->submit_id, 'tanggal_lahir' => '2000-01-01']);

    $this->get('/perbaikan')->assertRedirect(route('cek-status.create'));
});

it('AC-33: menolak NIK yang sama dengan NIK pengajuan lain', function () {
    PtkSubmission::factory()->create(['nik' => '6171010101900099']);
    verifikasi($this->submission);

    $this->put('/perbaikan', perbaikanValid(['nik' => '6171010101900099']))
        ->assertSessionHasErrors('nik');

    expect($this->submission->fresh()->nik)->toBe('6171010101900001')
        ->and($this->submission->fresh()->status)->toBe(PtkStatus::PerluRevisi);
});

it('AC-33: menerima NIK baru yang belum dipakai pengajuan lain', function () {
    verifikasi($this->submission);

    $this->put('/perbaikan', perbaikanValid(['nik' => '6171010101900077']))->assertSessionHasNoErrors();

    expect($this->submission->fresh()->nik)->toBe('6171010101900077');
});

it('AC-34 / NF-20: mencatat tepat satu riwayat PTK, Perlu Revisi → Menunggu', function () {
    verifikasi($this->submission);

    $this->put('/perbaikan', perbaikanValid());

    $riwayat = $this->submission->statusHistories()->sole();

    expect($riwayat->actor)->toBe('PTK')
        ->and($riwayat->from_status)->toBe(PtkStatus::PerluRevisi)
        ->and($riwayat->to_status)->toBe(PtkStatus::Menunggu);
});

it('tetap memakai aturan validasi yang sama dengan pengajuan baru', function (array $override, string $field) {
    verifikasi($this->submission);

    $this->put('/perbaikan', perbaikanValid($override))->assertSessionHasErrors($field);

    expect($this->submission->fresh()->status)->toBe(PtkStatus::PerluRevisi)
        ->and($this->submission->statusHistories()->count())->toBe(0);
})->with([
    'jabatan tidak sesuai jenis' => [['jenis_ptk' => 'Guru', 'jabatan_ptk' => 'Kepala Sekolah'], 'jabatan_ptk'],
    'wilayah salah induk' => [['kecamatan_kode' => '1271010'], 'kecamatan_kode'],
    'SK pengganti berformat JPG' => [['sk_penugasan' => UploadedFile::fake()->image('sk.jpg')], 'sk_penugasan'],
    'Foto KTP pengganti > 1 MB' => [['foto_ktp' => UploadedFile::fake()->image('ktp.jpg')->size(1229)], 'foto_ktp'],
    'NIK 15 digit' => [['nik' => '617101010190000'], 'nik'],
]);

it('menolak perbaikan kedua setelah status kembali Menunggu', function () {
    verifikasi($this->submission);

    $this->put('/perbaikan', perbaikanValid())->assertSessionHasNoErrors();
    $this->put('/perbaikan', perbaikanValid(['nama' => 'Kedua']))->assertRedirect(route('cek-status.hasil'));

    expect($this->submission->fresh()->nama)->toBe('Budi Santoso')
        ->and($this->submission->statusHistories()->count())->toBe(1);
});

it('AC-60: form perbaikan tanpa input tanggal bawaan browser, terisi data lama dan catatan revisi', function () {
    verifikasi($this->submission);

    $html = $this->get('/perbaikan')
        ->assertOk()
        ->assertSee('SK Penugasan belum ditandatangani.')
        ->assertSee('PTK-20261002-083045-1234')
        ->assertSee('Simpan Perbaikan')
        ->assertSee('name="_method" value="PUT"', escape: false)
        ->getContent();

    $config = ptkFormConfig($html);

    expect($html)->not->toMatch('/<input[^>]*type\s*=\s*["\']?date["\'\s>]/i')
        ->and($config['values']['nama'])->toBe('Budi Santoso')
        ->and($config['values']['tanggal_lahir'])->toBe('1990-01-15')
        ->and($config['values']['desa_kode'])->toBe($this->submission->desa_kode)
        ->and($config['existingFiles'])->toBe(['foto_ktp' => true, 'sk_penugasan' => true])
        ->and($config['action'])->toBe(route('perbaikan.update'));
});
