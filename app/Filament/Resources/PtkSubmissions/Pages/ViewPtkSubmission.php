<?php

namespace App\Filament\Resources\PtkSubmissions\Pages;

use App\Filament\Resources\PtkSubmissions\Actions\UbahStatusAction;
use App\Filament\Resources\PtkSubmissions\PtkSubmissionResource;
use Filament\Resources\Pages\ViewRecord;

class ViewPtkSubmission extends ViewRecord
{
    protected static string $resource = PtkSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            UbahStatusAction::make()
                // Muat ulang halaman agar detail dan tabel riwayat (komponen terpisah) ikut terbaru
                ->after(fn () => $this->redirect(PtkSubmissionResource::getUrl('view', ['record' => $this->getRecord()]), navigate: false)),
        ];
    }
}
