<?php

namespace App\Filament\Resources\PtkSubmissions\Schemas;

use App\Enums\PtkStatus;
use App\Models\PtkSubmission;
use App\Support\Tanggal;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;

/**
 * Detail pengajuan (REQ-11): semua field SPEC §4.1 + tautan berkas yang hanya bisa dibuka admin.
 */
class PtkSubmissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        $tanggal = fn ($state): string => Tanggal::format($state);

        return $schema
            ->columns(1)
            ->components([
                Section::make('Validasi')
                    ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        TextEntry::make('submit_id')->label('Submit ID')->fontFamily(FontFamily::Mono)->copyable(),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('created_at')->label('Tanggal Kirim')->formatStateUsing($tanggal),
                        TextEntry::make('catatan_revisi')
                            ->label('Catatan Revisi')
                            ->columnSpanFull()
                            ->visible(fn (PtkSubmission $record): bool => $record->status === PtkStatus::PerluRevisi),
                    ]),

                Section::make('Identitas')
                    ->icon(Heroicon::OutlinedIdentification)
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('nik')->label('NIK')->fontFamily(FontFamily::Mono)->copyable(),
                        TextEntry::make('nama')->label('Nama'),
                        TextEntry::make('tempat_lahir')->label('Tempat Lahir'),
                        TextEntry::make('tanggal_lahir')->label('Tanggal Lahir')->formatStateUsing($tanggal),
                        TextEntry::make('jenis_kelamin')->label('Jenis Kelamin'),
                        TextEntry::make('nama_ibu_kandung')->label('Nama Ibu Kandung'),
                        TextEntry::make('agama')->label('Agama'),
                        TextEntry::make('kebutuhan_khusus')->label('Kebutuhan Khusus'),
                        TextEntry::make('status_perkawinan')->label('Status Perkawinan'),
                        TextEntry::make('email')->label('Email')->copyable(),
                    ]),

                Section::make('Domisili')
                    ->icon(Heroicon::OutlinedMapPin)
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('provinsi')->label('Provinsi'),
                        TextEntry::make('kabupaten')->label('Kabupaten/Kota'),
                        TextEntry::make('kecamatan')->label('Kecamatan'),
                        TextEntry::make('desa')->label('Desa/Kelurahan'),
                        TextEntry::make('alamat')->label('Alamat')->columnSpanFull(),
                        TextEntry::make('kode_pos')->label('Kode Pos')->placeholder('-'),
                    ]),

                Section::make('Kepegawaian')
                    ->icon(Heroicon::OutlinedBriefcase)
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('jenis_ptk')->label('Jenis PTK'),
                        TextEntry::make('jabatan_ptk')->label('Jabatan PTK'),
                        TextEntry::make('status_kepegawaian')->label('Status Kepegawaian'),
                        TextEntry::make('lembaga_pengangkat')->label('Lembaga Pengangkat'),
                        TextEntry::make('sk_pengangkatan')->label('Nomor SK Pengangkatan'),
                        TextEntry::make('tmt_pengangkatan')->label('TMT Pengangkatan')->formatStateUsing($tanggal),
                        TextEntry::make('sumber_gaji')->label('Sumber Gaji'),
                        TextEntry::make('lisensi_kepsek')
                            ->label('Lisensi Kepala Sekolah')
                            ->visible(fn (PtkSubmission $record): bool => $record->jenis_ptk === 'Kepala Sekolah'),
                    ]),

                Section::make('Penugasan')
                    ->icon(Heroicon::OutlinedBuildingLibrary)
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('school.name')
                            ->label('Sekolah')
                            ->formatStateUsing(fn ($state, PtkSubmission $record): string => "({$record->school_npsn}) {$state}"),
                        TextEntry::make('nomor_surat_tugas')->label('Nomor Surat Tugas'),
                        TextEntry::make('tanggal_surat_tugas')->label('Tanggal Surat Tugas')->formatStateUsing($tanggal),
                        TextEntry::make('tmt_tugas')->label('TMT Tugas')->formatStateUsing($tanggal),
                        TextEntry::make('persetujuan')
                            ->label('Pernyataan Persetujuan')
                            ->formatStateUsing(fn (bool $state): string => $state ? 'Disetujui' : 'Belum disetujui'),
                    ]),

                Section::make('Berkas')
                    ->icon(Heroicon::OutlinedPaperClip)
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('foto_ktp_path')
                            ->label('Foto KTP')
                            ->formatStateUsing(fn (): string => 'Buka Foto KTP')
                            ->icon(Heroicon::OutlinedPhoto)
                            ->color('primary')
                            ->url(fn (PtkSubmission $record): string => route('admin.berkas', [$record, 'ktp']), shouldOpenInNewTab: true),
                        TextEntry::make('sk_penugasan_path')
                            ->label('SK Penugasan')
                            ->formatStateUsing(fn (): string => 'Buka SK Penugasan (PDF)')
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->color('primary')
                            ->url(fn (PtkSubmission $record): string => route('admin.berkas', [$record, 'sk']), shouldOpenInNewTab: true),
                    ]),
            ]);
    }
}
