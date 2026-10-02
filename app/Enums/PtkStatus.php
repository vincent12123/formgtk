<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Status pengajuan PTK (SPEC.md §4.1). Perpindahan antar status bebas (REQ-12).
 */
enum PtkStatus: string implements HasColor, HasLabel
{
    case Menunggu = 'Menunggu';
    case Diproses = 'Diproses';
    case Valid = 'Valid';
    case PerluRevisi = 'Perlu Revisi';

    public function getLabel(): string
    {
        return $this->value;
    }

    /**
     * Warna badge status sesuai docs/design-system.md §2.3.
     */
    public function getColor(): string
    {
        return match ($this) {
            self::Menunggu => 'warning',
            self::Diproses => 'info',
            self::Valid => 'success',
            self::PerluRevisi => 'danger',
        };
    }
}
