<?php

namespace App\Filament\Resources\PtkSubmissions\Pages;

use App\Filament\Resources\PtkSubmissions\PtkSubmissionResource;
use App\Filament\Widgets\StatistikPengajuan;
use Filament\Resources\Pages\ListRecords;

class ListPtkSubmissions extends ListRecords
{
    protected static string $resource = PtkSubmissionResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            StatistikPengajuan::class,
        ];
    }
}
