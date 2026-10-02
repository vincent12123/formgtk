<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Tanggal tanpa jam (tanggal lahir, TMT, surat tugas) — NFR-04.
 *
 * Disimpan sebagai `Y-m-d`, dibaca sebagai Carbon tengah malam WIB, dan diserialisasi sebagai `Y-m-d`.
 * Cast `date` bawaan menyerialisasi ke UTC (`1992-05-16T17:00:00Z`) sehingga tanggal mundur sehari
 * bila dibaca ulang — misalnya saat mengisi form perbaikan.
 *
 * @implements CastsAttributes<Carbon, mixed>
 */
class DateOnly implements CastsAttributes, SerializesCastableAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', substr((string) $value, 0, 10))->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return ($value instanceof \DateTimeInterface ? Carbon::instance($value) : Carbon::parse($value))->format('Y-m-d');
    }

    public function serialize(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        // $value sudah diubah Eloquent menjadi string ISO UTC (bisa mundur sehari);
        // pakai nilai mentah database yang memang berformat Y-m-d.
        return $value === null ? null : substr((string) $attributes[$key], 0, 10);
    }
}
