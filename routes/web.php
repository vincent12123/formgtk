<?php

use App\Http\Controllers\Admin\BerkasController;
use App\Http\Controllers\Public\CekStatusController;
use App\Http\Controllers\Public\PengajuanController;
use App\Http\Controllers\Public\PerbaikanController;
use App\Http\Controllers\Public\WilayahController;
use App\Http\Middleware\EnsurePerbaikanDiizinkan;
use Illuminate\Support\Facades\Route;

// Halaman publik PTK (SPEC.md §5.1–5.3)
Route::view('/', 'public.home')->name('beranda');

Route::controller(PengajuanController::class)->group(function () {
    Route::get('/pengajuan', 'create')->name('pengajuan.create');
    Route::post('/pengajuan', 'store')->name('pengajuan.store');
    Route::get('/pengajuan/terkirim', 'terkirim')->name('pengajuan.terkirim');
});

// Cek status: Submit ID + tanggal lahir, maksimal 5 percobaan/menit/IP (REQ-07)
Route::controller(CekStatusController::class)->group(function () {
    Route::get('/cek-status', 'create')->name('cek-status.create');
    Route::post('/cek-status', 'store')->middleware('throttle:cek-status')->name('cek-status.store');
    Route::get('/cek-status/hasil', 'hasil')->name('cek-status.hasil');
});

// Perbaikan data saat Perlu Revisi (REQ-08)
Route::controller(PerbaikanController::class)->middleware(EnsurePerbaikanDiizinkan::class)->group(function () {
    Route::get('/perbaikan', 'edit')->name('perbaikan.edit');
    Route::put('/perbaikan', 'update')->name('perbaikan.update');
});

// Daftar wilayah lokal untuk dropdown bertingkat (REQ-03)
Route::prefix('wilayah')->name('wilayah.')->controller(WilayahController::class)->group(function () {
    Route::get('provinsi', 'provinces')->name('provinsi');
    Route::get('kabupaten/{province}', 'regencies')->where('province', '\d{2}')->name('kabupaten');
    Route::get('kecamatan/{regency}', 'districts')->where('regency', '\d{4}')->name('kecamatan');
    Route::get('desa/{district}', 'villages')->where('district', '\d{7}')->name('desa');
});

// Berkas KTP & SK — wajib login admin (REQ-04, REQ-09)
Route::get('/admin/berkas/{submission}/{jenis}', [BerkasController::class, 'show'])
    ->whereIn('jenis', ['ktp', 'sk'])
    ->middleware('auth')
    ->name('admin.berkas');
