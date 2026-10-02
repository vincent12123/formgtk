<?php

namespace App\Filament\Resources\PtkSubmissions\Actions;

use App\Actions\ChangePtkStatus;
use App\Enums\PtkStatus;
use App\Models\PtkSubmission;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;

/**
 * Aksi "Ubah Status" (REQ-12): dipakai di tabel daftar dan halaman detail pengajuan.
 */
class UbahStatusAction
{
    public static function make(): Action
    {
        return Action::make('ubahStatus')
            ->label('Ubah Status')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('primary')
            ->modalHeading('Ubah Status Pengajuan')
            ->modalDescription(fn (PtkSubmission $record): string => "{$record->nama} — {$record->submit_id}")
            ->modalSubmitActionLabel('Simpan Status')
            ->fillForm(fn (PtkSubmission $record): array => [
                'status' => $record->status->value,
                'catatan' => $record->catatan_revisi,
            ])
            ->schema([
                Select::make('status')
                    ->label('Status')
                    ->options(PtkStatus::class)
                    ->required()
                    ->native(false)
                    ->live()
                    // Pesan error catatan lama tidak relevan lagi setelah status diganti
                    ->afterStateUpdated(fn ($livewire) => $livewire->resetErrorBag()),
                Textarea::make('catatan')
                    ->label(fn (Get $get): string => self::perluRevisi($get('status')) ? 'Catatan revisi' : 'Catatan (opsional)')
                    ->helperText(fn (Get $get): ?string => self::perluRevisi($get('status'))
                        ? 'Tampil untuk PTK di menu Cek Status dan di atas form perbaikan.'
                        : 'Disimpan di riwayat status.')
                    ->rows(4)
                    ->maxLength(2000)
                    // Validasi di server (bukan atribut HTML required) agar pesan selalu Bahasa Indonesia
                    ->markAsRequired(fn (Get $get): bool => self::perluRevisi($get('status')))
                    ->rule('required', fn (Get $get): bool => self::perluRevisi($get('status')))
                    ->validationMessages(['required' => ChangePtkStatus::PESAN_CATATAN_WAJIB]),
            ])
            ->action(function (array $data, PtkSubmission $record, ChangePtkStatus $ubah): void {
                $riwayat = $ubah->handle(
                    $record,
                    self::status($data['status']),
                    $data['catatan'] ?? null,
                    auth()->user(),
                );

                Notification::make()
                    ->title($riwayat ? "Status diubah menjadi {$record->status->getLabel()}" : 'Tidak ada perubahan')
                    ->{$riwayat ? 'success' : 'info'}()
                    ->send();
            });
    }

    private static function status(PtkStatus|string|null $status): ?PtkStatus
    {
        return $status instanceof PtkStatus ? $status : PtkStatus::tryFrom((string) $status);
    }

    private static function perluRevisi(PtkStatus|string|null $status): bool
    {
        return self::status($status) === PtkStatus::PerluRevisi;
    }
}
