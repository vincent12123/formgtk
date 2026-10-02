<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PtkSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cek status pengajuan dengan Submit ID + tanggal lahir (SPEC.md §5.2).
 * Verifikasi yang berhasil disimpan di sesi; sesi itu juga syarat membuka form perbaikan (§5.3).
 */
class CekStatusController extends Controller
{
    public const SESSION_TERVERIFIKASI = 'cek_status_terverifikasi';

    // Pesan sama untuk Submit ID tidak ada maupun tanggal lahir salah (AGENTS.md §5)
    public const PESAN_TIDAK_DITEMUKAN = 'Data tidak ditemukan. Periksa kembali Submit ID dan tanggal lahir.';

    public function create(): View
    {
        return view('public.cek-status.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'submit_id' => ['required', 'string', 'max:30'],
            'tanggal_lahir' => ['required', 'date_format:Y-m-d'],
        ], [
            'submit_id.required' => 'Submit ID wajib diisi.',
            'tanggal_lahir.required' => 'Tanggal Lahir wajib diisi.',
            'tanggal_lahir.date_format' => 'Tanggal Lahir harus berupa tanggal yang valid.',
        ]);

        $submission = PtkSubmission::query()
            ->where('submit_id', strtoupper(trim($data['submit_id'])))
            ->whereDate('tanggal_lahir', $data['tanggal_lahir'])
            ->first();

        if (! $submission) {
            return back()->withInput()->with('pesanUmum', self::PESAN_TIDAK_DITEMUKAN);
        }

        // Sesi baru setelah verifikasi agar ID sesi lama tidak bisa dipakai ulang
        $request->session()->regenerate();
        $request->session()->put(self::SESSION_TERVERIFIKASI, $submission->id);

        return redirect()->route('cek-status.hasil');
    }

    public function hasil(Request $request): View|RedirectResponse
    {
        $submission = self::terverifikasi($request);

        if (! $submission) {
            return redirect()->route('cek-status.create');
        }

        return view('public.cek-status.hasil', ['submission' => $submission->load('school')]);
    }

    /**
     * Pengajuan yang sudah lolos verifikasi Submit ID + tanggal lahir di sesi ini.
     */
    public static function terverifikasi(Request $request): ?PtkSubmission
    {
        $id = $request->session()->get(self::SESSION_TERVERIFIKASI);

        return $id ? PtkSubmission::find($id) : null;
    }
}
