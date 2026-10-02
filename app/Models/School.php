<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Unit sekolah yayasan (SPEC.md §4.2), dikunci dengan NPSN.
 */
#[Fillable(['npsn', 'name'])]
class School extends Model
{
    protected $primaryKey = 'npsn';

    protected $keyType = 'string';

    public $incrementing = false;

    public function ptkSubmissions(): HasMany
    {
        return $this->hasMany(PtkSubmission::class, 'school_npsn', 'npsn');
    }
}
