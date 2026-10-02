<?php

/*
 * Checkpoint 1 — setup dasar: locale & WIB, data tetap (sekolah, jabatan), seeder admin,
 * dan riwayat status yang tidak bisa diubah/dihapus (NFR-20).
 */

use App\Enums\PtkStatus;
use App\Models\PtkStatusHistory;
use App\Models\PtkSubmission;
use App\Models\School;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\SchoolSeeder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

it('memakai locale id dan zona waktu Asia/Jakarta', function () {
    expect(app()->getLocale())->toBe('id')
        ->and(config('app.timezone'))->toBe('Asia/Jakarta')
        ->and(now()->getTimezone()->getName())->toBe('Asia/Jakarta')
        ->and(now()->setDate(2026, 10, 2)->translatedFormat('d F Y'))->toBe('02 Oktober 2026');
});

it('menampilkan pesan validasi dalam Bahasa Indonesia (NF-03)', function () {
    $errors = Validator::make(
        ['nik' => '', 'email' => 'bukan-email'],
        ['nik' => 'required', 'email' => 'email'],
    )->errors();

    expect($errors->first('nik'))->toBe('NIK wajib diisi.')
        ->and($errors->first('email'))->toBe('Email harus berupa alamat email yang valid.');
});

it('menanam tepat 6 unit sekolah sesuai SPEC §4.2 dan aman dijalankan ulang', function () {
    $this->seed(SchoolSeeder::class);
    $this->seed(SchoolSeeder::class);

    expect(School::count())->toBe(6)
        ->and(School::find('70004213')->name)->toBe('SMK KARYA BANGSA')
        ->and(School::orderBy('npsn')->pluck('npsn')->all())->toBe([
            '70004213', '70011201', '70011827', '70027214', '70027216', '70050352',
        ]);
});

it('memuat daftar Jenis → Jabatan PTK sesuai SPEC §4.3', function () {
    $jabatan = config('ptk.jabatan');

    expect(array_keys($jabatan))->toBe(['Kepala Sekolah', 'Guru', 'Tenaga Kependidikan'])
        ->and($jabatan['Kepala Sekolah'])->toBe(['Kepala Sekolah'])
        ->and($jabatan['Guru'])->toHaveCount(88)
        ->toContain('Guru Kelas', 'Pamong Belajar', 'Tutor')
        ->not->toContain('Kepala Sekolah')
        ->and($jabatan['Tenaga Kependidikan'])->toHaveCount(10)
        ->and($jabatan['Guru'])->toBe(array_values(array_unique($jabatan['Guru'])));
});

it('memuat jumlah pilihan sesuai form lama', function () {
    expect(config('ptk.agama'))->toHaveCount(8)
        ->and(config('ptk.kebutuhan_khusus'))->toHaveCount(17)
        ->and(config('ptk.lembaga_pengangkat'))->toHaveCount(6)
        ->and(config('ptk.status_kepegawaian'))->toHaveCount(3)
        ->and(config('ptk.status_perkawinan'))->toHaveCount(3)
        ->and(config('ptk.sumber_gaji'))->toHaveCount(3);
});

it('membuat akun admin dari .env dengan password ter-hash', function () {
    config()->set('ptk.admin', [
        'name' => 'Admin Yayasan',
        'email' => 'admin@ptk-yhkb.test',
        'password' => 'rahasia-awal',
    ]);

    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@ptk-yhkb.test')->sole();

    expect($admin->password)->not->toBe('rahasia-awal')
        ->and(Hash::check('rahasia-awal', $admin->password))->toBeTrue()
        ->and(School::count())->toBe(6);
});

it('tidak menimpa password admin yang sudah ada saat seeder dijalankan ulang', function () {
    config()->set('ptk.admin', ['name' => 'Admin', 'email' => 'admin@ptk-yhkb.test', 'password' => 'lama']);
    $this->seed(AdminUserSeeder::class);

    config()->set('ptk.admin.password', 'baru');
    $this->seed(AdminUserSeeder::class);

    expect(User::count())->toBe(1)
        ->and(Hash::check('lama', User::sole()->password))->toBeTrue();
});

it('menolak menjalankan seeder admin bila ADMIN_EMAIL / ADMIN_PASSWORD kosong', function () {
    config()->set('ptk.admin', ['name' => null, 'email' => null, 'password' => null]);

    expect(fn () => $this->seed(AdminUserSeeder::class))
        ->toThrow(RuntimeException::class, 'ADMIN_EMAIL dan ADMIN_PASSWORD wajib diisi');

    expect(User::count())->toBe(0);
});

it('menolak NIK ganda di level database', function () {
    $this->seed(SchoolSeeder::class);

    PtkSubmission::factory()->create(['nik' => '6171010101900001']);

    expect(fn () => PtkSubmission::factory()->create(['nik' => '6171010101900001']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('memberi status awal Menunggu pada pengajuan baru', function () {
    $this->seed(SchoolSeeder::class);

    $submission = PtkSubmission::factory()->create()->refresh();

    expect($submission->status)->toBe(PtkStatus::Menunggu)
        ->and($submission->school->name)->not->toBeEmpty();
});

it('menolak mengubah atau menghapus riwayat status (NFR-20)', function () {
    $this->seed(SchoolSeeder::class);

    $history = PtkSubmission::factory()->create()->statusHistories()->create([
        'actor' => 'PTK',
        'from_status' => null,
        'to_status' => PtkStatus::Menunggu,
    ]);

    expect(fn () => $history->update(['note' => 'diubah']))->toThrow(LogicException::class)
        ->and(fn () => $history->delete())->toThrow(LogicException::class)
        ->and(PtkStatusHistory::count())->toBe(1)
        ->and(PtkStatusHistory::first()->note)->toBeNull();
});

it('menyimpan tanggal tanpa jam sebagai Y-m-d dan tidak bergeser saat diserialisasi (NFR-04)', function (string $timezone) {
    config()->set('app.timezone', $timezone);
    date_default_timezone_set($timezone);
    $this->seed(SchoolSeeder::class);

    $submission = PtkSubmission::factory()->create(['tanggal_lahir' => '1992-05-17', 'tmt_tugas' => '2024-07-01']);

    expect(DB::table('ptk_submissions')->value('tanggal_lahir'))->toBe('1992-05-17')
        ->and($submission->refresh()->toArray()['tanggal_lahir'])->toBe('1992-05-17')
        ->and($submission->tanggal_lahir->translatedFormat('d F Y'))->toBe('17 Mei 1992')
        ->and($submission->tmt_tugas->format('Y-m-d'))->toBe('2024-07-01');

    date_default_timezone_set('Asia/Jakarta');
})->with(['Asia/Jakarta', 'UTC']);
