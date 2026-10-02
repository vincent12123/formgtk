<?php

namespace App\Http\Controllers\Public;

use App\Actions\UpdatePtkSubmission;
use App\Http\Controllers\Controller;
use App\Http\Requests\PerbaikanPtkRequest;
use App\Http\Requests\PtkSubmissionRequest;
use App\Models\PtkSubmission;
use App\Models\School;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Perbaikan data oleh PTK saat status Perlu Revisi (SPEC.md §5.3).
 * Akses dijaga middleware EnsurePerbaikanDiizinkan.
 */
class PerbaikanController extends Controller
{
    private const KOLOM_TANGGAL = ['tanggal_lahir', 'tmt_pengangkatan', 'tanggal_surat_tugas', 'tmt_tugas'];

    public function edit(Request $request): View
    {
        /** @var PtkSubmission $submission */
        $submission = $request->attributes->get('ptk_submission');

        // Nilai awal dari data tersimpan; old input (gagal validasi) tetap diutamakan
        $values = collect(PengajuanController::FIELDS)
            ->mapWithKeys(function (string $field) use ($submission) {
                $tersimpan = in_array($field, self::KOLOM_TANGGAL, true)
                    ? $submission->{$field}?->format('Y-m-d')
                    : $submission->{$field};

                return [$field => (string) old($field, $tersimpan ?? '')];
            })
            ->put('persetujuan', (bool) old('persetujuan', false))
            ->all();

        return view('public.perbaikan.edit', [
            'submission' => $submission,
            'values' => $values,
            'schools' => School::query()->orderBy('name')->get(),
        ]);
    }

    public function update(PerbaikanPtkRequest $request, UpdatePtkSubmission $update): RedirectResponse
    {
        try {
            $update->handle(
                $request->attributes->get('ptk_submission'),
                $request->validated(),
                $request->file('foto_ktp'),
                $request->file('sk_penugasan'),
            );
        } catch (UniqueConstraintViolationException) {
            return back()->withInput()->withErrors(['nik' => PtkSubmissionRequest::NIK_TERDAFTAR]);
        }

        return redirect()->route('cek-status.hasil')
            ->with('pesanBerhasil', 'Perbaikan data tersimpan. Status pengajuan kembali Menunggu validasi admin.');
    }
}
