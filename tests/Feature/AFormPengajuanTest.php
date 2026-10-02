<?php

/*
 * Bagian A acceptance criteria — Form Pengajuan PTK (SPEC.md §5.1).
 * Checkpoint 2: data wilayah lokal (AC-09, AC-18) dan halaman form (AC-60).
 * Checkpoint 3: simpan pengajuan, validasi server, berkas privat, Submit ID (AC-04, 07, 08, 10–17).
 * Perilaku wizard di browser (AC-01–03, 05, 06, 61) diverifikasi manual.
 */

use App\Enums\PtkStatus;
use App\Models\District;
use App\Models\Province;
use App\Models\PtkSubmission;
use App\Models\Regency;
use App\Models\User;
use App\Models\Village;
use Database\Seeders\SchoolSeeder;
use Database\Seeders\WilayahSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Form publik tidak boleh memanggil API wilayah luar saat berjalan
    Http::preventStrayRequests();

    isiWilayahUji();
});

it('AC-09: hanya mengembalikan kabupaten/kota milik provinsi Kalimantan Barat', function () {
    expect($this->getJson('/wilayah/kabupaten/61')->assertOk()->json())->toBe([
        ['id' => '6107', 'name' => 'KABUPATEN SINTANG'],
        ['id' => '6101', 'name' => 'KABUPATEN SAMBAS'],
        ['id' => '6171', 'name' => 'KOTA PONTIANAK'],
    ]);
});

it('AC-18: menaruh Kalimantan Barat dan Kabupaten Sintang paling atas, sisanya urut nama', function () {
    expect($this->getJson('/wilayah/provinsi')->json('*.name'))
        ->toBe(['KALIMANTAN BARAT', 'ACEH', 'SUMATERA UTARA'])
        ->and($this->getJson('/wilayah/kabupaten/61')->json('0.name'))->toBe('KABUPATEN SINTANG')
        // Prioritas kabupaten tidak berpengaruh di provinsi lain
        ->and($this->getJson('/wilayah/kabupaten/12')->json('*.name'))->toBe(['KOTA MEDAN']);
});

it('AC-09: mengembalikan daftar provinsi, kecamatan, dan desa sesuai induknya', function () {
    $this->getJson('/wilayah/provinsi')->assertOk()->assertJsonCount(3)
        ->assertJsonPath('0.name', 'KALIMANTAN BARAT');

    $this->getJson('/wilayah/kecamatan/6171')->assertOk()
        ->assertExactJson([['id' => '6171010', 'name' => 'PONTIANAK SELATAN']]);

    $this->getJson('/wilayah/desa/6171010')->assertOk()
        ->assertExactJson([['id' => '6171010001', 'name' => 'BENUA MELAYU DARAT']]);
});

it('mengembalikan daftar kosong untuk kode induk yang tidak dikenal', function () {
    $this->getJson('/wilayah/kabupaten/99')->assertOk()->assertExactJson([]);
    $this->getJson('/wilayah/desa/9999999')->assertOk()->assertExactJson([]);
});

it('menolak kode induk berformat salah', function (string $url) {
    $this->getJson($url)->assertNotFound();
})->with([
    'provinsi berisi huruf' => '/wilayah/kabupaten/6a',
    'kabupaten terlalu pendek' => '/wilayah/kecamatan/617',
    'kecamatan berisi injeksi' => "/wilayah/desa/6171010' OR 1=1",
]);

it('menampilkan halaman awal dengan pilihan Isi Form', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Isi Form')
        ->assertSee(route('pengajuan.create'), escape: false);
});

it('AC-60: form pengajuan tidak memakai input tanggal bawaan browser', function () {
    $html = $this->get('/pengajuan')->assertOk()->getContent();

    expect($html)->not->toMatch('/<input[^>]*type\s*=\s*["\']?date["\'\s>]/i')
        ->and(substr_count($html, 'data-datepicker'))->toBe(4);
});

it('menampilkan 4 tab, 6 sekolah, dan daftar Jenis → Jabatan PTK dari konfigurasi', function () {
    $this->seed(SchoolSeeder::class);

    $response = $this->get('/pengajuan')->assertOk();

    foreach (['Identitas PTK', 'Domisili', 'Kepegawaian', 'Penugasan'] as $judul) {
        $response->assertSee($judul);
    }

    $response->assertSee('(70004213) SMK KARYA BANGSA')
        ->assertSee('(70027216) TK GOLDEN BEE')
        ->assertSee('Guru Pengembangan Perangkat Lunak Dan Gim')
        ->assertSee('Tukang Kebun')
        ->assertSee('name="_token"', escape: false)
        ->assertSee('enctype="multipart/form-data"', escape: false);
});

it('mengisi ulang nilai lama (old input) ke dalam form', function () {
    $html = $this->withSession(['_old_input' => ['nama' => 'Budi Santoso', 'provinsi_kode' => '61']])
        ->get('/pengajuan')
        ->assertOk()
        ->getContent();

    $config = ptkFormConfig($html);

    expect($config['values']['nama'])->toBe('Budi Santoso')
        ->and($config['values']['provinsi_kode'])->toBe('61')
        ->and($config['values']['persetujuan'])->toBeFalse()
        ->and($config['jabatan']['Guru'])->toHaveCount(88)
        ->and($config['action'])->toBe(route('pengajuan.store'));
});

it('AC-18: form baru langsung memilih Kalimantan Barat dan Kabupaten Sintang', function () {
    $config = ptkFormConfig($this->get('/pengajuan')->assertOk()->getContent());

    expect($config['values']['provinsi_kode'])->toBe('61')
        ->and($config['values']['kabupaten_kode'])->toBe('6107')
        ->and($config['values']['kecamatan_kode'])->toBe('');
});

it('AC-18: tidak menimpa pilihan PTK saat form kembali dengan old input', function () {
    $html = $this->withSession(['_old_input' => ['provinsi_kode' => '12', 'nama' => 'Budi']])
        ->get('/pengajuan')
        ->getContent();

    $config = ptkFormConfig($html);

    expect($config['values']['provinsi_kode'])->toBe('12')
        ->and($config['values']['kabupaten_kode'])->toBe('');
});

it('mengimpor seluruh data wilayah dari CSV dan melewati kode desa ganda', function () {
    Village::query()->delete();
    District::query()->delete();
    Regency::query()->delete();
    Province::query()->delete();

    $this->seed(WilayahSeeder::class);

    expect(Province::count())->toBe(34)
        ->and(Regency::count())->toBe(514)
        ->and(District::count())->toBe(7215)
        ->and(Village::count())->toBe(80532)
        ->and(Village::find('9107182005')->name)->toBe('KAMLIN')
        ->and(Village::find('1402041010')->name)->toBe('LAMBANG SARI I, II, III')
        ->and(Regency::where('parent_id', '61')->count())->toBe(14);

    // Dijalankan ulang tidak menggandakan data
    $this->seed(WilayahSeeder::class);

    expect(Village::count())->toBe(80532);
});

// ===================================================================== Checkpoint 3: simpan pengajuan

describe('simpan pengajuan', function () {
    beforeEach(function () {
        Storage::fake('local');
        Storage::fake('public');
        $this->seed(SchoolSeeder::class);
    });

    it('menyimpan pengajuan valid lalu mengarahkan ke halaman terkirim', function () {
        $this->post('/pengajuan', pengajuanValid())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('pengajuan.terkirim'));

        $submission = PtkSubmission::sole();

        expect($submission->nama)->toBe('Budi Santoso')
            ->and($submission->status)->toBe(PtkStatus::Menunggu)
            ->and($submission->tanggal_lahir->format('Y-m-d'))->toBe('1990-01-01')
            ->and($submission->persetujuan)->toBeTrue()
            ->and($submission->catatan_revisi)->toBeNull();
    });

    it('mencatat riwayat pengajuan pertama (actor PTK, kosong → Menunggu)', function () {
        $this->post('/pengajuan', pengajuanValid());

        $history = PtkSubmission::sole()->statusHistories()->sole();

        expect($history->actor)->toBe('PTK')
            ->and($history->from_status)->toBeNull()
            ->and($history->to_status)->toBe(PtkStatus::Menunggu);
    });

    it('AC-04: menolak kiriman tanpa field wajib dan tidak menambah data', function (string $field) {
        $this->post('/pengajuan', pengajuanValid([$field => '']))
            ->assertSessionHasErrors($field);

        expect(PtkSubmission::count())->toBe(0);
    })->with(['nama', 'tanggal_lahir', 'agama', 'alamat', 'jenis_ptk', 'school_npsn', 'tmt_tugas']);

    it('AC-04: menolak kiriman tanpa persetujuan', function () {
        $this->post('/pengajuan', pengajuanValid(['persetujuan' => '']))
            ->assertSessionHasErrors(['persetujuan' => 'Bapak/Ibu wajib menyetujui pernyataan.']);

        expect(PtkSubmission::count())->toBe(0);
    });

    it('menolak pilihan di luar daftar konfigurasi', function (string $field, string $nilai) {
        $this->post('/pengajuan', pengajuanValid([$field => $nilai]))->assertSessionHasErrors($field);
    })->with([
        ['agama', 'Atheis'],
        ['kebutuhan_khusus', 'Z - Tidak terdaftar'],
        ['status_kepegawaian', 'PNS'],
        ['jenis_ptk', 'Satpam'],
        ['school_npsn', '99999999'],
        ['tanggal_lahir', '01/02/1990'],
        ['email', 'bukan-email'],
    ]);

    it('AC-07: menolak Jabatan PTK yang tidak sesuai Jenis PTK', function () {
        $this->post('/pengajuan', pengajuanValid(['jenis_ptk' => 'Guru', 'jabatan_ptk' => 'Kepala Sekolah']))
            ->assertSessionHasErrors(['jabatan_ptk' => 'Jabatan PTK tidak sesuai dengan Jenis PTK.']);

        expect(PtkSubmission::count())->toBe(0);
    });

    it('AC-08: menolak Kepala Sekolah tanpa Lisensi', function () {
        $this->post('/pengajuan', pengajuanValid([
            'jenis_ptk' => 'Kepala Sekolah', 'jabatan_ptk' => 'Kepala Sekolah', 'lisensi_kepsek' => '',
        ]))->assertSessionHasErrors('lisensi_kepsek');

        expect(PtkSubmission::count())->toBe(0);
    });

    it('AC-08: menyimpan Lisensi untuk Kepala Sekolah dan mengosongkannya untuk Guru', function () {
        $this->post('/pengajuan', pengajuanValid([
            'jenis_ptk' => 'Kepala Sekolah', 'jabatan_ptk' => 'Kepala Sekolah', 'lisensi_kepsek' => 'Sudah',
        ]))->assertSessionHasNoErrors();

        $this->post('/pengajuan', pengajuanValid([
            'nik' => '6171010101900002', 'lisensi_kepsek' => 'Sudah',
        ]))->assertSessionHasNoErrors();

        expect(PtkSubmission::where('jenis_ptk', 'Kepala Sekolah')->sole()->lisensi_kepsek)->toBe('Sudah')
            ->and(PtkSubmission::where('jenis_ptk', 'Guru')->sole()->lisensi_kepsek)->toBeNull();
    });

    it('AC-10: menolak kode wilayah yang bukan bagian dari induk terpilih', function (array $wilayah, string $field) {
        $this->post('/pengajuan', pengajuanValid($wilayah))->assertSessionHasErrors($field);

        expect(PtkSubmission::count())->toBe(0);
    })->with([
        'kecamatan Medan di Kota Pontianak' => [['kecamatan_kode' => '1271010'], 'kecamatan_kode'],
        'kabupaten Medan di Kalimantan Barat' => [['kabupaten_kode' => '1271'], 'kabupaten_kode'],
        'desa Medan di Pontianak Selatan' => [['desa_kode' => '1271010001'], 'desa_kode'],
        'kode desa tidak ada' => [['desa_kode' => '6171010999'], 'desa_kode'],
        'provinsi tidak ada' => [['provinsi_kode' => '99'], 'provinsi_kode'],
    ]);

    it('AC-10: menampilkan pesan wilayah tidak sesuai dalam Bahasa Indonesia', function () {
        $this->post('/pengajuan', pengajuanValid(['kecamatan_kode' => '1271010']))
            ->assertSessionHasErrors(['kecamatan_kode' => 'Kecamatan tidak sesuai dengan Kabupaten/Kota yang dipilih.']);
    });

    it('AC-11: menyimpan nama wilayah beserta kodenya', function () {
        $this->post('/pengajuan', pengajuanValid());

        expect(PtkSubmission::sole()->only([
            'provinsi_kode', 'provinsi', 'kabupaten_kode', 'kabupaten', 'kecamatan_kode', 'kecamatan', 'desa_kode', 'desa',
        ]))->toBe([
            'provinsi_kode' => '61', 'provinsi' => 'KALIMANTAN BARAT',
            'kabupaten_kode' => '6171', 'kabupaten' => 'KOTA PONTIANAK',
            'kecamatan_kode' => '6171010', 'kecamatan' => 'PONTIANAK SELATAN',
            'desa_kode' => '6171010001', 'desa' => 'BENUA MELAYU DARAT',
        ]);
    });

    it('AC-12 / NF-03: menolak berkas yang terlalu besar atau bertipe salah dengan pesan Indonesia', function (string $field, Closure $berkas, string $pesan) {
        $this->post('/pengajuan', pengajuanValid([$field => $berkas()]))
            ->assertSessionHasErrors([$field => $pesan]);

        expect(PtkSubmission::count())->toBe(0)
            ->and(Storage::disk('local')->allFiles())->toBeEmpty();
    })->with([
        'Foto KTP 1,2 MB' => ['foto_ktp', fn () => UploadedFile::fake()->image('ktp.jpg')->size(1229), 'Ukuran Foto KTP maksimal 1 MB.'],
        'Foto KTP berformat PDF' => ['foto_ktp', fn () => UploadedFile::fake()->create('ktp.pdf', 100, 'application/pdf'), 'Foto KTP harus berupa berkas JPG atau PNG.'],
        'SK Penugasan berformat JPG' => ['sk_penugasan', fn () => UploadedFile::fake()->image('sk.jpg')->size(100), 'SK Penugasan harus berupa berkas PDF.'],
        'SK Penugasan 1,2 MB' => ['sk_penugasan', fn () => UploadedFile::fake()->create('sk.pdf', 1229, 'application/pdf'), 'Ukuran SK Penugasan maksimal 1 MB.'],
    ]);

    it('AC-12: menerima berkas tepat 1 MB dan Foto KTP PNG', function () {
        $this->post('/pengajuan', pengajuanValid([
            'foto_ktp' => UploadedFile::fake()->image('ktp.png')->size(1024),
            'sk_penugasan' => UploadedFile::fake()->create('sk.pdf', 1024, 'application/pdf'),
        ]))->assertSessionHasNoErrors();

        expect(PtkSubmission::count())->toBe(1);
    });

    it('AC-12: menolak kiriman tanpa berkas', function () {
        $this->post('/pengajuan', pengajuanValid(['foto_ktp' => null, 'sk_penugasan' => null]))
            ->assertSessionHasErrors([
                'foto_ktp' => 'Foto KTP wajib diunggah.',
                'sk_penugasan' => 'SK Penugasan wajib diunggah.',
            ]);
    });

    it('AC-13: menyimpan berkas di disk privat, bukan public', function () {
        $this->post('/pengajuan', pengajuanValid());

        $submission = PtkSubmission::sole();

        Storage::disk('local')->assertExists($submission->foto_ktp_path);
        Storage::disk('local')->assertExists($submission->sk_penugasan_path);

        expect($submission->foto_ktp_path)->toStartWith('ptk/ktp/')
            ->and($submission->sk_penugasan_path)->toStartWith('ptk/sk/')
            ->and(Storage::disk('public')->allFiles())->toBeEmpty()
            ->and(config('filesystems.disks.local.serve'))->toBeFalse();
    });

    it('AC-13: mengarahkan tamu yang membuka URL berkas ke halaman login', function (string $jenis) {
        $this->post('/pengajuan', pengajuanValid());

        $this->get(route('admin.berkas', [PtkSubmission::sole(), $jenis]))
            ->assertRedirect(route('filament.admin.auth.login'));
    })->with(['ktp', 'sk']);

    it('AC-13: membuka berkas untuk admin yang sudah login', function () {
        $this->post('/pengajuan', pengajuanValid());
        $submission = PtkSubmission::sole();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.berkas', [$submission, 'sk']))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->get("/admin/berkas/{$submission->id}/lainnya")->assertNotFound();
    });

    it('AC-14: menolak NIK yang sudah terdaftar dan mengarahkan ke Cek Status', function () {
        $this->post('/pengajuan', pengajuanValid())->assertSessionHasNoErrors();

        $this->post('/pengajuan', pengajuanValid(['nama' => 'Orang Lain', 'email' => 'lain@example.com']))
            ->assertSessionHasErrors(['nik' => 'NIK ini sudah pernah mengirim data. Silakan gunakan menu Cek Status untuk melihat pengajuan Bapak/Ibu.']);

        expect(PtkSubmission::count())->toBe(1)
            ->and(session('errors')->first('nik'))->toContain('Cek Status');
    });

    it('AC-15: menolak NIK yang bukan tepat 16 digit angka', function (string $nik) {
        $this->post('/pengajuan', pengajuanValid(['nik' => $nik]))
            ->assertSessionHasErrors(['nik' => 'NIK harus tepat 16 digit angka.']);

        expect(PtkSubmission::count())->toBe(0);
    })->with([
        '15 digit' => '617101010190000',
        '17 digit' => '61710101019000012',
        'berisi huruf' => '61710101019000A1',
        'berisi spasi' => '6171 01010190000',
        'desimal' => '617101010190000.',
    ]);

    it('AC-16: membuat Submit ID berpola PTK-YYYYMMDD-HHmmss-XXXX dalam WIB', function () {
        // 01:30:45 UTC = 08:30:45 WIB
        Carbon::setTestNow(Carbon::parse('2026-10-02 01:30:45', 'UTC'));

        $this->post('/pengajuan', pengajuanValid());

        expect(PtkSubmission::sole()->submit_id)
            ->toMatch('/^PTK-\d{8}-\d{6}-\d{4}$/')
            ->toStartWith('PTK-20261002-083045-');

        Carbon::setTestNow();
    });

    it('AC-17: halaman terkirim memuat Submit ID, tombol salin, dan tautan WhatsApp admin', function () {
        config()->set('ptk.admin_whatsapp', '628111222333');

        $this->post('/pengajuan', pengajuanValid());
        $submission = PtkSubmission::sole();

        $response = $this->get(route('pengajuan.terkirim'))
            ->assertOk()
            ->assertSee($submission->submit_id)
            ->assertSee('Salin Submit ID')
            ->assertSee('data-salin', escape: false);

        preg_match('/href="(https:\/\/wa\.me\/[^"]+)"/', $response->getContent(), $match);
        $url = html_entity_decode($match[1] ?? '');
        $pesan = rawurldecode(substr($url, strpos($url, '?text=') + 6));

        expect($url)->toStartWith('https://wa.me/628111222333?text=')
            ->and($pesan)->toContain('Nama: Budi Santoso')
            ->toContain('Sekolah: SMK KARYA BANGSA')
            ->toContain('Submit ID: '.$submission->submit_id);
    });

    it('AC-17: menyembunyikan tombol WhatsApp bila nomor admin belum diatur', function () {
        config()->set('ptk.admin_whatsapp', null);

        $this->post('/pengajuan', pengajuanValid());

        $this->get(route('pengajuan.terkirim'))
            ->assertOk()
            ->assertDontSee('wa.me', escape: false);
    });

    it('NF-21: halaman terkirim tidak memuat NIK lengkap', function () {
        $this->post('/pengajuan', pengajuanValid());

        $this->get(route('pengajuan.terkirim'))->assertOk()->assertDontSee('6171010101900001');
    });

    it('mengarahkan ke beranda bila halaman terkirim dibuka tanpa mengirim', function () {
        $this->get(route('pengajuan.terkirim'))->assertRedirect(route('beranda'));
    });

    it('NF-01: menolak kiriman tanpa token CSRF (419)', function () {
        // Middleware CSRF dilewati saat unit test; simulasikan lingkungan non-testing
        $this->app['env'] = 'production';

        $this->post('/pengajuan', pengajuanValid())->assertStatus(419);

        expect(PtkSubmission::count())->toBe(0);
    });

    it('menampilkan kembali isian dan pesan error setelah validasi server gagal', function () {
        $this->from(route('pengajuan.create'))
            ->post('/pengajuan', pengajuanValid(['nik' => '123']))
            ->assertRedirect(route('pengajuan.create'));

        $config = ptkFormConfig($this->get(route('pengajuan.create'))->getContent());

        expect($config['values']['nama'])->toBe('Budi Santoso')
            ->and($config['values']['kabupaten_kode'])->toBe('6171')
            ->and($config['errors']['nik'][0])->toBe('NIK harus tepat 16 digit angka.');
    });
});
