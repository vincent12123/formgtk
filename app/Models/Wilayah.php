<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Dasar model wilayah (SPEC.md §4.4): kunci = kode wilayah (string), induk di `parent_id`.
 */
#[Fillable(['id', 'parent_id', 'name'])]
#[WithoutTimestamps]
abstract class Wilayah extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * Daftar wilayah berurutan nama, opsional dibatasi pada satu induk.
     * Wilayah $priorityId (bila ada di daftar) ditaruh paling atas.
     */
    public static function options(?string $parentId = null, ?string $priorityId = null): Builder
    {
        return static::query()
            ->when($parentId !== null, fn (Builder $query) => $query->where('parent_id', $parentId))
            ->when($priorityId !== null, fn (Builder $query) => $query->orderByRaw('id = ? desc', [$priorityId]))
            ->orderBy('name')
            ->select(['id', 'name']);
    }
}
