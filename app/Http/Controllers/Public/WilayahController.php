<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Village;
use Illuminate\Http\JsonResponse;

/**
 * Daftar wilayah bertingkat untuk form publik (REQ-03), dibaca dari database lokal —
 * tidak memanggil API luar saat aplikasi berjalan.
 * Respons: [{"id": "<kode>", "name": "<nama>"}], urut nama. Induk tak dikenal → daftar kosong.
 */
class WilayahController extends Controller
{
    public function provinces(): JsonResponse
    {
        return $this->respond(Province::options(priorityId: config('ptk.wilayah_prioritas.provinsi'))->get());
    }

    public function regencies(string $province): JsonResponse
    {
        return $this->respond(Regency::options($province, config('ptk.wilayah_prioritas.kabupaten'))->get());
    }

    public function districts(string $regency): JsonResponse
    {
        return $this->respond(District::options($regency)->get());
    }

    public function villages(string $district): JsonResponse
    {
        return $this->respond(Village::options($district)->get());
    }

    private function respond(iterable $items): JsonResponse
    {
        // Data wilayah jarang berubah; izinkan browser menyimpan sementara selama 1 hari
        return response()->json($items)->header('Cache-Control', 'public, max-age=86400');
    }
}
