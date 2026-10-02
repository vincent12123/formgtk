<?php

namespace App\Http\Controllers\Public;

use App\Actions\StorePtkSubmission;
use App\Http\Controllers\Controller;
use App\Http\Requests\PtkSubmissionRequest;
use App\Models\PtkSubmission;
use App\Models\School;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Form pengajuan PTK baru (SPEC.md §5.1).
 */
class PengajuanController extends Controller
{
    /**
     * Kunci sesi berisi id pengajuan yang baru dikirim — halaman sukses hanya untuk pengirimnya.
     */
    public const SESSION_TERKIRIM = 'pengajuan_terkirim';

    /**
     * Field isian form beserta nilai kosongnya (berkas tidak termasuk — tidak bisa diisi ulang).
     */
    public const FIELDS = [
        'nik', 'nama', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'nama_ibu_kandung', 'agama',
        'kebutuhan_khusus', 'status_perkawinan', 'email',
        'provinsi_kode', 'kabupaten_kode', 'kecamatan_kode', 'desa_kode', 'alamat', 'kode_pos',
        'jenis_ptk', 'jabatan_ptk', 'status_kepegawaian', 'lembaga_pengangkat', 'sk_pengangkatan',
        'tmt_pengangkatan', 'sumber_gaji', 'lisensi_kepsek',
        'school_npsn', 'nomor_surat_tugas', 'tanggal_surat_tugas', 'tmt_tugas',
    ];

    public function create(): View
    {
        // Form baru: Kalimantan Barat + Sintang terpilih otomatis. Bila form kembali dengan
        // old input (gagal validasi), pilihan PTK sendiri yang dipakai — tanpa default.
        $defaults = session()->hasOldInput() ? [] : [
            'provinsi_kode' => config('ptk.wilayah_prioritas.provinsi'),
            'kabupaten_kode' => config('ptk.wilayah_prioritas.kabupaten'),
        ];

        $values = collect(self::FIELDS)
            ->mapWithKeys(fn (string $field) => [$field => (string) old($field, $defaults[$field] ?? '')])
            ->put('persetujuan', (bool) old('persetujuan', false))
            ->all();

        return view('public.pengajuan.create', [
            'values' => $values,
            'action' => route('pengajuan.store'),
            'schools' => School::query()->orderBy('name')->get(),
        ]);
    }

    public function store(PtkSubmissionRequest $request, StorePtkSubmission $store): RedirectResponse
    {
        try {
            $submission = $store->handle(
                $request->safe()->except(['persetujuan']),
                $request->file('foto_ktp'),
                $request->file('sk_penugasan'),
            );
        } catch (UniqueConstraintViolationException) {
            // NIK yang sama terkirim bersamaan dari dua permintaan (REQ-05)
            return back()->withInput()->withErrors(['nik' => PtkSubmissionRequest::NIK_TERDAFTAR]);
        }

        $request->session()->put(self::SESSION_TERKIRIM, $submission->id);
        $request->session()->regenerateToken();

        return redirect()->route('pengajuan.terkirim');
    }

    public function terkirim(): View|RedirectResponse
    {
        $submission = PtkSubmission::with('school')->find(session(self::SESSION_TERKIRIM));

        if (! $submission) {
            return redirect()->route('beranda');
        }

        return view('public.pengajuan.terkirim', [
            'submission' => $submission,
            'whatsappUrl' => self::whatsappUrl($submission),
        ]);
    }

    /**
     * Tautan WhatsApp ke admin berisi nama, sekolah, dan Submit ID (REQ-06).
     * Null bila nomor admin belum diisi di .env.
     */
    public static function whatsappUrl(PtkSubmission $submission): ?string
    {
        $nomor = preg_replace('/\D/', '', (string) config('ptk.admin_whatsapp'));

        if ($nomor === '') {
            return null;
        }

        $pesan = implode("\n", [
            'Halo Admin PTK Yayasan, data PTK saya sudah berhasil dikirim.',
            'Nama: '.$submission->nama,
            'Sekolah: '.$submission->school->name,
            'Submit ID: '.$submission->submit_id,
        ]);

        return 'https://wa.me/'.$nomor.'?text='.rawurlencode($pesan);
    }
}
