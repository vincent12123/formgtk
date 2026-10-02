<?php

namespace App\Filament\Widgets;

use App\Enums\PtkStatus;
use App\Filament\Resources\PtkSubmissions\PtkSubmissionResource;
use App\Models\PtkSubmission;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Jumlah pengajuan total dan per status (REQ-10). Tiap kartu status membuka daftar terfilter.
 */
class StatistikPengajuan extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Data kecil — tampil langsung tanpa kotak kosong saat memuat
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = ['default' => 1, 'sm' => 2, 'xl' => 5];

    protected function getStats(): array
    {
        $perStatus = PtkSubmission::query()
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        $stats = [
            Stat::make('Total Pengajuan', number_format($perStatus->sum(), 0, ',', '.'))
                ->icon(Heroicon::OutlinedUserGroup)
                ->url(PtkSubmissionResource::getUrl('index')),
        ];

        foreach (PtkStatus::cases() as $status) {
            $stats[] = Stat::make($status->getLabel(), number_format((int) ($perStatus[$status->value] ?? 0), 0, ',', '.'))
                ->color($status->getColor())
                ->description('Lihat daftar')
                ->descriptionIcon(Heroicon::ArrowRight)
                ->url(PtkSubmissionResource::getUrl('index', ['filters' => ['status' => ['value' => $status->value]]]));
        }

        return $stats;
    }
}
