<?php

namespace App\Filament\Resources\PtkSubmissions\RelationManagers;

use App\Support\Tanggal;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Riwayat status pengajuan (REQ-15, NFR-20): urut waktu, hanya baca — tanpa aksi tambah/ubah/hapus.
 */
class StatusHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistories';

    protected static ?string $title = 'Riwayat Status';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modelLabel('riwayat')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->formatStateUsing(fn ($state): string => Tanggal::formatWaktu($state)),
                TextColumn::make('actor')
                    ->label('Oleh'),
                TextColumn::make('from_status')
                    ->label('Dari')
                    ->badge()
                    ->placeholder('Pengajuan baru'),
                TextColumn::make('to_status')
                    ->label('Menjadi')
                    ->badge(),
                TextColumn::make('note')
                    ->label('Catatan')
                    ->wrap()
                    ->placeholder('-'),
            ])
            ->defaultSort(fn ($query) => $query->orderBy('created_at')->orderBy('id'))
            ->paginated(false)
            ->emptyStateHeading('Belum ada riwayat')
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
