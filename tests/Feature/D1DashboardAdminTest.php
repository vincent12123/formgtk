<?php

/*
 * Bagian D1 acceptance criteria — Dashboard Admin (SPEC.md §5.4).
 * Checkpoint 1: AC-40 & AC-41 (akses admin wajib login).
 * Checkpoint 5: AC-40 (detail), AC-42–51, NF-04 (dashboard), NF-20 (perubahan status admin).
 */

use App\Actions\ChangePtkStatus;
use App\Enums\PtkStatus;
use App\Filament\Resources\PtkSubmissions\Pages\ListPtkSubmissions;
use App\Filament\Resources\PtkSubmissions\Pages\ViewPtkSubmission;
use App\Filament\Resources\PtkSubmissions\RelationManagers\StatusHistoriesRelationManager;
use App\Filament\Widgets\StatistikPengajuan;
use App\Models\PtkStatusHistory;
use App\Models\PtkSubmission;
use App\Models\User;
use Database\Seeders\SchoolSeeder;
use Filament\Actions\Exports\Models\Export;
use Filament\Actions\Testing\TestAction;
use Filament\Auth\Pages\Login;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'name' => 'Admin Yayasan',
        'email' => 'admin@ptk-yhkb.test',
        'password' => 'rahasia-benar',
    ]);
});

/**
 * Buat pengajuan dengan status tertentu (tanpa riwayat).
 */
function pengajuanBerstatus(PtkStatus $status, array $atribut = []): PtkSubmission
{
    $submission = PtkSubmission::factory()->create($atribut);
    $submission->forceFill(['status' => $status])->save();

    return $submission;
}

// ===================================================================== Checkpoint 1: login

it('AC-40: mengarahkan tamu yang membuka /admin ke halaman login', function () {
    $this->get('/admin')->assertRedirect('/admin/login');

    $this->assertGuest();
});

it('AC-40: menampilkan dashboard untuk admin yang sudah login', function () {
    $this->actingAs($this->admin)->get('/admin')->assertOk();
});

it('AC-41: menolak login dengan password salah dan tetap di halaman login', function () {
    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'admin@ptk-yhkb.test',
            'password' => 'password-salah',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email'])
        ->assertNoRedirect();

    $this->assertGuest();
});

it('AC-41: menolak login dengan email yang tidak terdaftar', function () {
    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'bukan-admin@ptk-yhkb.test',
            'password' => 'rahasia-benar',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
});

it('AC-41: menerima login dengan kredensial benar', function () {
    Livewire::test(Login::class)
        ->fillForm([
            'email' => 'admin@ptk-yhkb.test',
            'password' => 'rahasia-benar',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($this->admin);
});

it('menampilkan halaman login dalam Bahasa Indonesia', function () {
    $this->get('/admin/login')
        ->assertOk()
        ->assertSee('Masuk');
});

// ===================================================================== Checkpoint 5: dashboard

describe('dashboard admin', function () {
    beforeEach(function () {
        $this->seed(SchoolSeeder::class);
    });

    it('AC-40: mengarahkan tamu yang membuka daftar atau detail pengajuan ke login', function () {
        $submission = PtkSubmission::factory()->create();

        $this->get('/admin/pengajuan')->assertRedirect('/admin/login');
        $this->get("/admin/pengajuan/{$submission->id}")->assertRedirect('/admin/login');
    });

    it('membuka dasbor, daftar, dan detail pengajuan untuk admin', function () {
        $submission = PtkSubmission::factory()->create();
        $this->actingAs($this->admin);

        $this->get('/admin')->assertOk()->assertSee('Total Pengajuan');
        $this->get('/admin/pengajuan')->assertOk()->assertSee('Ekspor Excel');
        $this->get("/admin/pengajuan/{$submission->id}")->assertOk()->assertSee($submission->submit_id);
    });

    it('tidak menyediakan tambah, ubah isi, atau hapus pengajuan (SPEC §6)', function () {
        $submission = PtkSubmission::factory()->create();
        $this->actingAs($this->admin);

        expect(Route::has('filament.admin.resources.pengajuan.create'))->toBeFalse()
            ->and(Route::has('filament.admin.resources.pengajuan.edit'))->toBeFalse();

        Livewire::test(ListPtkSubmissions::class)
            ->assertTableActionDoesNotExist('delete')
            ->assertTableActionDoesNotExist('edit')
            ->assertActionDoesNotExist('create');
    });

    it('AC-42: menampilkan total dan jumlah per status', function () {
        foreach ([PtkStatus::Menunggu, PtkStatus::Menunggu, PtkStatus::Menunggu, PtkStatus::Diproses, PtkStatus::Valid, PtkStatus::Valid] as $status) {
            pengajuanBerstatus($status);
        }
        $this->actingAs($this->admin);

        Livewire::test(StatistikPengajuan::class)
            ->assertSeeInOrder(['Total Pengajuan', '6', 'Menunggu', '3', 'Diproses', '1', 'Valid', '2', 'Perlu Revisi', '0']);
    });

    it('AC-43: menampilkan pesan kosong dan statistik 0 saat belum ada pengajuan', function () {
        $this->actingAs($this->admin);

        Livewire::test(ListPtkSubmissions::class)
            ->assertCountTableRecords(0)
            ->assertSee('Belum ada data');

        Livewire::test(StatistikPengajuan::class)
            ->assertSeeInOrder(['Total Pengajuan', '0', 'Menunggu', '0', 'Diproses', '0', 'Valid', '0', 'Perlu Revisi', '0']);
    });

    it('AC-44: mencari pengajuan berdasarkan NIK, Submit ID, atau nama', function (string $kolom) {
        $dicari = PtkSubmission::factory()->create(['nama' => 'Yohanes Tumbuan', 'nik' => '6107010101800011', 'submit_id' => 'PTK-20261002-080000-1111']);
        $lain = PtkSubmission::factory()->count(2)->sequence(
            ['nama' => 'Maria Goreti', 'nik' => '6107010101800022', 'submit_id' => 'PTK-20261002-080000-2222'],
            ['nama' => 'Petrus Kanisius', 'nik' => '6107010101800033', 'submit_id' => 'PTK-20261002-080000-3333'],
        )->create();
        $this->actingAs($this->admin);

        Livewire::test(ListPtkSubmissions::class)
            ->searchTable($dicari->{$kolom})
            ->assertCanSeeTableRecords([$dicari])
            ->assertCanNotSeeTableRecords($lain)
            ->assertCountTableRecords(1);
    })->with(['nik', 'submit_id', 'nama']);

    it('AC-44: menampilkan daftar kosong untuk pencarian tanpa hasil', function () {
        PtkSubmission::factory()->count(2)->create();
        $this->actingAs($this->admin);

        Livewire::test(ListPtkSubmissions::class)
            ->searchTable('tidak-ada-yang-cocok')
            ->assertCountTableRecords(0);
    });

    it('AC-45: memfilter berdasarkan sekolah, status, dan jenis PTK', function () {
        $smk = pengajuanBerstatus(PtkStatus::Valid, ['school_npsn' => '70004213', 'jenis_ptk' => 'Guru', 'jabatan_ptk' => 'Guru TIK']);
        $sd = pengajuanBerstatus(PtkStatus::Menunggu, ['school_npsn' => '70011827', 'jenis_ptk' => 'Tenaga Kependidikan', 'jabatan_ptk' => 'Pustakawan']);
        $this->actingAs($this->admin);

        Livewire::test(ListPtkSubmissions::class)
            ->filterTable('school_npsn', '70004213')
            ->assertCanSeeTableRecords([$smk])
            ->assertCanNotSeeTableRecords([$sd]);

        Livewire::test(ListPtkSubmissions::class)
            ->filterTable('status', PtkStatus::Menunggu->value)
            ->assertCanSeeTableRecords([$sd])
            ->assertCanNotSeeTableRecords([$smk]);

        Livewire::test(ListPtkSubmissions::class)
            ->filterTable('jenis_ptk', 'Guru')
            ->assertCanSeeTableRecords([$smk])
            ->assertCanNotSeeTableRecords([$sd]);
    });

    it('AC-46: menampilkan semua field SPEC §4.1 dan tautan berkas di detail', function () {
        $submission = PtkSubmission::factory()->kepalaSekolah()->create(['tanggal_lahir' => '1985-03-20', 'school_npsn' => '70004213']);
        $this->actingAs($this->admin);

        $response = $this->get("/admin/pengajuan/{$submission->id}")->assertOk();

        foreach ([
            $submission->submit_id, $submission->nik, $submission->nama, $submission->tempat_lahir, '20 Maret 1985',
            $submission->jenis_kelamin, $submission->nama_ibu_kandung, $submission->agama, $submission->kebutuhan_khusus,
            $submission->status_perkawinan, $submission->email, $submission->provinsi, $submission->kabupaten,
            $submission->kecamatan, $submission->desa, $submission->alamat, $submission->kode_pos,
            'Kepala Sekolah', $submission->status_kepegawaian, $submission->lembaga_pengangkat,
            $submission->sk_pengangkatan, $submission->sumber_gaji, 'Sudah', '(70004213) SMK KARYA BANGSA',
            $submission->nomor_surat_tugas, 'Disetujui',
        ] as $nilai) {
            $response->assertSee($nilai);
        }

        $response->assertSee(route('admin.berkas', [$submission, 'ktp']), escape: false)
            ->assertSee(route('admin.berkas', [$submission, 'sk']), escape: false);
    });

    it('AC-47: mengubah Menunggu → Valid dan mencatat riwayat atas nama admin', function () {
        $submission = PtkSubmission::factory()->create();
        $this->actingAs($this->admin);

        Livewire::test(ViewPtkSubmission::class, ['record' => $submission->getRouteKey()])
            ->callAction('ubahStatus', data: ['status' => PtkStatus::Valid->value, 'catatan' => ''])
            ->assertHasNoActionErrors();

        $riwayat = $submission->statusHistories()->sole();

        expect($submission->fresh()->status)->toBe(PtkStatus::Valid)
            ->and($riwayat->actor)->toBe('Admin Yayasan')
            ->and($riwayat->from_status)->toBe(PtkStatus::Menunggu)
            ->and($riwayat->to_status)->toBe(PtkStatus::Valid);
    });

    it('AC-47: menerima perpindahan bebas Valid → Diproses dari tabel daftar', function () {
        $submission = pengajuanBerstatus(PtkStatus::Valid);
        $this->actingAs($this->admin);

        Livewire::test(ListPtkSubmissions::class)
            ->callTableAction('ubahStatus', $submission, data: ['status' => PtkStatus::Diproses->value])
            ->assertHasNoTableActionErrors();

        expect($submission->fresh()->status)->toBe(PtkStatus::Diproses)
            ->and($submission->statusHistories()->sole()->from_status)->toBe(PtkStatus::Valid);
    });

    it('AC-47: menyimpan catatan revisi saat Perlu Revisi dan mengosongkannya saat status lain', function () {
        $submission = PtkSubmission::factory()->create();
        $this->actingAs($this->admin);

        Livewire::test(ViewPtkSubmission::class, ['record' => $submission->getRouteKey()])
            ->callAction('ubahStatus', data: ['status' => PtkStatus::PerluRevisi->value, 'catatan' => 'Foto KTP buram.'])
            ->assertHasNoActionErrors();

        expect($submission->fresh()->catatan_revisi)->toBe('Foto KTP buram.');

        Livewire::test(ViewPtkSubmission::class, ['record' => $submission->getRouteKey()])
            ->callAction('ubahStatus', data: ['status' => PtkStatus::Valid->value, 'catatan' => 'Sudah lengkap.'])
            ->assertHasNoActionErrors();

        expect($submission->fresh())
            ->status->toBe(PtkStatus::Valid)
            ->catatan_revisi->toBeNull()
            ->and($submission->statusHistories()->pluck('note')->all())->toBe(['Foto KTP buram.', 'Sudah lengkap.']);
    });

    it('AC-48: menolak Perlu Revisi tanpa catatan dengan pesan "Catatan revisi wajib diisi"', function () {
        $submission = PtkSubmission::factory()->create();
        $this->actingAs($this->admin);

        Livewire::test(ViewPtkSubmission::class, ['record' => $submission->getRouteKey()])
            ->callAction('ubahStatus', data: ['status' => PtkStatus::PerluRevisi->value, 'catatan' => ''])
            ->assertHasActionErrors(['catatan' => 'Catatan revisi wajib diisi.']);

        expect($submission->fresh()->status)->toBe(PtkStatus::Menunggu)
            ->and($submission->statusHistories()->count())->toBe(0);
    });

    it('AC-48: action domain juga menolak Perlu Revisi tanpa catatan (spasi saja)', function () {
        $submission = PtkSubmission::factory()->create();

        expect(fn () => app(ChangePtkStatus::class)->handle($submission, PtkStatus::PerluRevisi, '   ', $this->admin))
            ->toThrow(ValidationException::class, 'Catatan revisi wajib diisi.');

        expect($submission->fresh()->status)->toBe(PtkStatus::Menunggu);
    });

    it('NF-20: tidak mencatat riwayat bila tidak ada yang berubah', function () {
        $submission = PtkSubmission::factory()->create();
        $this->actingAs($this->admin);

        Livewire::test(ViewPtkSubmission::class, ['record' => $submission->getRouteKey()])
            ->callAction('ubahStatus', data: ['status' => PtkStatus::Menunggu->value, 'catatan' => ''])
            ->assertHasNoActionErrors();

        expect(PtkStatusHistory::count())->toBe(0);
    });

    it('AC-51: menampilkan riwayat berurutan waktu tanpa aksi tambah/ubah/hapus', function () {
        $submission = PtkSubmission::factory()->create();
        $pertama = $submission->statusHistories()->create(['actor' => 'PTK', 'to_status' => PtkStatus::Menunggu, 'note' => 'Pengajuan baru']);
        Carbon::setTestNow(now()->addHour());
        $kedua = $submission->statusHistories()->create(['actor' => 'Admin Yayasan', 'from_status' => PtkStatus::Menunggu, 'to_status' => PtkStatus::PerluRevisi, 'note' => 'Foto buram']);
        Carbon::setTestNow(now()->addHour());
        $ketiga = $submission->statusHistories()->create(['actor' => 'PTK', 'from_status' => PtkStatus::PerluRevisi, 'to_status' => PtkStatus::Menunggu]);
        Carbon::setTestNow();
        $this->actingAs($this->admin);

        Livewire::test(StatusHistoriesRelationManager::class, [
            'ownerRecord' => $submission,
            'pageClass' => ViewPtkSubmission::class,
        ])
            ->assertCanSeeTableRecords([$pertama, $kedua, $ketiga], inOrder: true)
            ->assertSeeInOrder(['Pengajuan baru', 'Foto buram'])
            ->assertTableActionDoesNotExist('create')
            ->assertTableActionDoesNotExist('edit')
            ->assertTableActionDoesNotExist('delete')
            ->assertTableActionDoesNotExist('dissociate');
    });

    it('NF-04: tanggal kirim di daftar tampil dalam WIB walau aplikasi berjalan di UTC', function () {
        config()->set('app.timezone', 'UTC');
        date_default_timezone_set('UTC');

        $submission = PtkSubmission::factory()->create();
        $submission->forceFill(['created_at' => Carbon::parse('2026-10-01 20:00:00', 'UTC')])->save();
        $this->actingAs($this->admin);

        Livewire::test(ListPtkSubmissions::class)
            ->assertTableColumnFormattedStateSet('created_at', '02 Oktober 2026', $submission);

        date_default_timezone_set('Asia/Jakarta');
    });

    describe('ekspor Excel', function () {
        beforeEach(function () {
            Storage::fake('local');
            $this->actingAs($this->admin);
        });

        /**
         * Baca seluruh baris berkas XLSX hasil ekspor terakhir.
         *
         * @return array<int, array<int, string>>
         */
        function bacaEksporTerakhir(): array
        {
            $export = Export::latest('id')->firstOrFail();
            $berkas = collect(Storage::disk('local')->allFiles($export->getFileDirectory()))
                ->first(fn (string $path) => str_ends_with($path, '.xlsx'));

            expect($export->file_disk)->toBe('local')->and($berkas)->not->toBeNull();

            $reader = new Reader;
            $reader->open(Storage::disk('local')->path($berkas));
            $baris = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $baris[] = array_map(fn ($cell) => (string) $cell->getValue(), $row->getCells());
                }
            }
            $reader->close();

            return $baris;
        }

        it('AC-49: mengekspor hanya pengajuan sesuai filter Valid ke XLSX dengan tanggal Indonesia', function () {
            $valid = pengajuanBerstatus(PtkStatus::Valid, ['nama' => 'Valid Satu', 'tanggal_lahir' => '1990-05-17']);
            pengajuanBerstatus(PtkStatus::Menunggu, ['nama' => 'Masih Menunggu']);
            pengajuanBerstatus(PtkStatus::PerluRevisi, ['nama' => 'Perlu Revisi']);

            Livewire::test(ListPtkSubmissions::class)
                ->filterTable('status', PtkStatus::Valid->value)
                ->callAction(TestAction::make('export')->table())
                ->assertHasNoActionErrors();

            $baris = bacaEksporTerakhir();
            $judul = $baris[0];
            $data = array_slice($baris, 1);

            expect($data)->toHaveCount(1)
                ->and($data[0][array_search('Nama', $judul)])->toBe('Valid Satu')
                ->and($data[0][array_search('Status', $judul)])->toBe('Valid')
                ->and($data[0][array_search('Tanggal Lahir', $judul)])->toBe('17 Mei 1990')
                ->and($data[0][array_search('Tanggal Kirim', $judul)])->toMatch('/^\d{2} (Januari|Februari|Maret|April|Mei|Juni|Juli|Agustus|September|Oktober|November|Desember) \d{4}$/')
                ->and($data[0][array_search('NIK', $judul)])->toBe($valid->nik)
                ->and($judul)->toContain('Submit ID', 'Sekolah', 'TMT Tugas', 'Desa/Kelurahan');
        });

        it('AC-49: mengekspor mengikuti pencarian yang aktif', function () {
            pengajuanBerstatus(PtkStatus::Valid, ['nama' => 'Yohanes Tumbuan']);
            pengajuanBerstatus(PtkStatus::Valid, ['nama' => 'Maria Goreti']);

            Livewire::test(ListPtkSubmissions::class)
                ->searchTable('Yohanes')
                ->callAction(TestAction::make('export')->table());

            $data = array_slice(bacaEksporTerakhir(), 1);

            expect($data)->toHaveCount(1)
                ->and(implode('|', $data[0]))->toContain('Yohanes Tumbuan');
        });

        it('AC-50: menghasilkan XLSX berisi judul kolom saja bila filter tanpa data', function () {
            pengajuanBerstatus(PtkStatus::Menunggu);

            Livewire::test(ListPtkSubmissions::class)
                ->filterTable('status', PtkStatus::Valid->value)
                ->callAction(TestAction::make('export')->table())
                ->assertHasNoActionErrors();

            $baris = bacaEksporTerakhir();

            expect($baris)->toHaveCount(1)
                ->and($baris[0])->toContain('Submit ID', 'NIK', 'Nama', 'Status');
        });

        it('menyimpan berkas ekspor di disk privat dan menolak unduhan oleh admin lain', function () {
            pengajuanBerstatus(PtkStatus::Valid);

            Livewire::test(ListPtkSubmissions::class)
                ->callAction(TestAction::make('export')->table());

            $export = Export::latest('id')->firstOrFail();

            expect($export->file_disk)->toBe('local')
                ->and($export->user_id)->toBe($this->admin->id);

            $this->actingAs(User::factory()->create())
                ->get(route('filament.exports.download', ['export' => $export, 'format' => 'xlsx']))
                ->assertForbidden();

            auth()->logout();
            $this->get(route('filament.exports.download', ['export' => $export, 'format' => 'xlsx']))
                ->assertUnauthorized();
        });
    });
});
