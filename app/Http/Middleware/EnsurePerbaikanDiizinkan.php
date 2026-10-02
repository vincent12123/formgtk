<?php

namespace App\Http\Middleware;

use App\Enums\PtkStatus;
use App\Http\Controllers\Public\CekStatusController;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Perbaikan data hanya untuk pengajuan yang sudah diverifikasi (Submit ID + tanggal lahir)
 * di sesi ini dan berstatus Perlu Revisi (SPEC.md §5.3, AC-31, AC-32).
 * Pengajuan yang lolos diteruskan lewat atribut request `ptk_submission`.
 */
class EnsurePerbaikanDiizinkan
{
    public function handle(Request $request, Closure $next): Response
    {
        $submission = CekStatusController::terverifikasi($request);

        if (! $submission) {
            return redirect()->route('cek-status.create')
                ->with('pesanUmum', 'Silakan cek status dengan Submit ID dan tanggal lahir terlebih dahulu.');
        }

        if ($submission->status !== PtkStatus::PerluRevisi) {
            return redirect()->route('cek-status.hasil')
                ->with('pesanUmum', 'Data hanya dapat diperbaiki saat status pengajuan Perlu Revisi.');
        }

        $request->attributes->set('ptk_submission', $submission);

        return $next($request);
    }
}
