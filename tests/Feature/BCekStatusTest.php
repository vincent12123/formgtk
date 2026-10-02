<?php

/*
 * Bagian B acceptance criteria — Cek Status (SPEC.md §5.2).
 * Checkpoint 4: AC-20–23, NF-04 (tanggal kirim WIB), NF-21 (NIK tersamar).
 */

use App\Enums\PtkStatus;
use App\Http\Controllers\Public\CekStatusController;
use App\Models\PtkSubmission;
use App\Support\Nik;
use App\Support\Tanggal;
use Database\Seeders\SchoolSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->seed(SchoolSeeder::class);

    $this->submission = PtkSubmission::factory()->create([
        'submit_id' => 'PTK-20261002-083045-1234',
        'nik' => '6171010101900001',
        'nama' => 'Budi Santoso',
        'tanggal_lahir' => '1990-01-15',
        'school_npsn' => '70004213',
    ]);
});

function cekStatus(array $data = []): TestResponse
{
    return test()->post('/cek-status', array_merge([
        'submit_id' => 'PTK-20261002-083045-1234',
        'tanggal_lahir' => '1990-01-15',
    ], $data));
}

it('menampilkan form cek status tanpa input tanggal bawaan browser', function () {
    $html = $this->get('/cek-status')->assertOk()->assertSee('Cek Sekarang')->getContent();

    expect($html)->not->toMatch('/<input[^>]*type\s*=\s*["\']?date["\'\s>]/i');
});

it('menampilkan menu Cek Status di beranda', function () {
    $this->get('/')->assertOk()->assertSee(route('cek-status.create'), escape: false);
});

it('AC-20: menampilkan nama, sekolah, status, dan tanggal kirim berformat Indonesia', function () {
    $this->submission->forceFill(['created_at' => Carbon::parse('2026-10-02 08:30:45', 'Asia/Jakarta')])->save();

    cekStatus()->assertSessionHasNoErrors()->assertRedirect(route('cek-status.hasil'));

    $this->get(route('cek-status.hasil'))
        ->assertOk()
        ->assertSee('Budi Santoso')
        ->assertSee('SMK KARYA BANGSA')
        ->assertSee('PTK-20261002-083045-1234')
        ->assertSee('data-status="Menunggu"', escape: false)
        ->assertSee('02 Oktober 2026');
});

it('AC-20: menerima Submit ID dengan huruf kecil atau spasi di tepi', function () {
    cekStatus(['submit_id' => '  ptk-20261002-083045-1234 '])->assertRedirect(route('cek-status.hasil'));
});

it('AC-21: menampilkan pesan identik untuk tanggal lahir salah dan Submit ID yang tidak ada', function () {
    cekStatus(['tanggal_lahir' => '1990-01-16'])->assertRedirect();
    $pesanTanggalSalah = session('pesanUmum');

    $this->flushSession();

    cekStatus(['submit_id' => 'PTK-20991231-000000-0000'])->assertRedirect();
    $pesanIdSalah = session('pesanUmum');

    expect($pesanTanggalSalah)->toBe(CekStatusController::PESAN_TIDAK_DITEMUKAN)
        ->and($pesanIdSalah)->toBe($pesanTanggalSalah)
        ->and(session(CekStatusController::SESSION_TERVERIFIKASI))->toBeNull();

    $this->get(route('cek-status.hasil'))->assertRedirect(route('cek-status.create'));
});

it('AC-21: menampilkan "Data tidak ditemukan" di halaman cek status', function () {
    $this->from('/cek-status')->followingRedirects()
        ->post('/cek-status', ['submit_id' => 'PTK-20261002-083045-1234', 'tanggal_lahir' => '2000-01-01'])
        ->assertOk()
        ->assertSee('Data tidak ditemukan')
        ->assertDontSee('Budi Santoso');
});

it('menolak cek status dengan isian kosong atau format tanggal salah', function () {
    $this->post('/cek-status', ['submit_id' => '', 'tanggal_lahir' => ''])
        ->assertSessionHasErrors(['submit_id' => 'Submit ID wajib diisi.', 'tanggal_lahir' => 'Tanggal Lahir wajib diisi.']);

    $this->post('/cek-status', ['submit_id' => 'PTK-1', 'tanggal_lahir' => '15/01/1990'])
        ->assertSessionHasErrors('tanggal_lahir');
});

it('AC-22: menolak percobaan ke-6 dalam satu menit dari IP yang sama (429)', function () {
    foreach (range(1, 5) as $percobaan) {
        cekStatus(['tanggal_lahir' => '2000-01-01'])->assertStatus(302);
    }

    cekStatus()
        ->assertStatus(429)
        ->assertSee('Terlalu banyak percobaan, coba lagi sebentar lagi.');

    // IP lain tetap dapat mencoba
    $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.2']);
    cekStatus()->assertRedirect(route('cek-status.hasil'));
});

it('AC-23: menampilkan catatan revisi dan tombol Perbaiki Data saat Perlu Revisi', function () {
    $this->submission->forceFill([
        'status' => PtkStatus::PerluRevisi,
        'catatan_revisi' => 'Foto KTP buram, mohon unggah ulang.',
    ])->save();

    cekStatus();

    $this->get(route('cek-status.hasil'))
        ->assertOk()
        ->assertSee('data-status="Perlu Revisi"', escape: false)
        ->assertSee('Foto KTP buram, mohon unggah ulang.')
        ->assertSee('Perbaiki Data')
        ->assertSee(route('perbaikan.edit'), escape: false);
});

it('AC-23: tidak menampilkan tombol Perbaiki Data untuk status selain Perlu Revisi', function (PtkStatus $status) {
    $this->submission->forceFill(['status' => $status, 'catatan_revisi' => 'Catatan lama'])->save();

    cekStatus();

    $this->get(route('cek-status.hasil'))
        ->assertOk()
        ->assertSee('data-status="'.$status->value.'"', escape: false)
        ->assertDontSee('Perbaiki Data')
        ->assertDontSee('Catatan lama');
})->with([PtkStatus::Menunggu, PtkStatus::Diproses, PtkStatus::Valid]);

it('NF-21: menampilkan NIK tersamar, bukan NIK lengkap', function () {
    cekStatus();

    $this->get(route('cek-status.hasil'))
        ->assertOk()
        ->assertSee('6171********0001')
        ->assertDontSee('6171010101900001');
});

it('NF-04: menampilkan tanggal kirim dalam WIB walau aplikasi berjalan di UTC', function () {
    config()->set('app.timezone', 'UTC');
    date_default_timezone_set('UTC');

    // 1 Okt 20:00 UTC = 2 Okt 03:00 WIB
    $this->submission->forceFill(['created_at' => Carbon::parse('2026-10-01 20:00:00', 'UTC')])->save();

    cekStatus();

    $this->get(route('cek-status.hasil'))->assertSee('02 Oktober 2026')->assertDontSee('01 Oktober 2026');

    date_default_timezone_set('Asia/Jakarta');
});

it('helper Tanggal dan Nik memberi format yang benar', function () {
    expect(Tanggal::format('2026-10-02'))->toBe('02 Oktober 2026')
        ->and(Tanggal::format(null))->toBe('-')
        ->and(Nik::mask('6171010101900001'))->toBe('6171********0001')
        ->and(Nik::mask('1234'))->toBe('****');
});
