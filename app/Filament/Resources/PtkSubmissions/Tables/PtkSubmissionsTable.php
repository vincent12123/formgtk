<?php

namespace App\Filament\Resources\PtkSubmissions\Tables;

use App\Enums\PtkStatus;
use App\Filament\Exports\PtkSubmissionExporter;
use App\Filament\Resources\PtkSubmissions\Actions\UbahStatusAction;
use App\Support\Tanggal;
use Filament\Actions\ExportAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Daftar pengajuan (REQ-11): cari nama / NIK / Submit ID, filter status / sekolah / jenis PTK,
 * ekspor XLSX mengikuti filter & pencarian aktif (REQ-13).
 */
class PtkSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('submit_id')
                    ->label('Submit ID')
                    ->searchable()
                    ->fontFamily(FontFamily::Mono)
                    ->copyable()
                    ->copyMessage('Submit ID disalin'),
                TextColumn::make('nama')
                    ->label('Nama')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record): string => $record->nik),
                TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('school.name')
                    ->label('Sekolah')
                    ->sortable(),
                TextColumn::make('jenis_ptk')
                    ->label('Jenis PTK')
                    ->description(fn ($record): string => $record->jabatan_ptk)
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Tanggal Kirim')
                    ->formatStateUsing(fn ($state): string => Tanggal::format($state))
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->searchPlaceholder('Cari nama, NIK, atau Submit ID')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(PtkStatus::class),
                SelectFilter::make('school_npsn')
                    ->label('Sekolah')
                    ->relationship('school', 'name'),
                SelectFilter::make('jenis_ptk')
                    ->label('Jenis PTK')
                    ->options(array_combine(array_keys(config('ptk.jabatan')), array_keys(config('ptk.jabatan')))),
            ])
            ->emptyStateIcon(Heroicon::OutlinedInbox)
            ->emptyStateHeading('Belum ada data')
            ->emptyStateDescription('Pengajuan PTK yang dikirim lewat form publik akan tampil di sini.')
            ->headerActions([
                ExportAction::make()
                    ->label('Ekspor Excel')
                    ->exporter(PtkSubmissionExporter::class)
                    ->formats([ExportFormat::Xlsx])
                    ->fileDisk('local')
                    ->columnMapping(false),
            ])
            ->recordActions([
                ViewAction::make()->label('Detail'),
                UbahStatusAction::make(),
            ])
            ->toolbarActions([]);
    }
}
