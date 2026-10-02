<?php

namespace App\Filament\Exports;

use App\Models\PtkSubmission;
use App\Support\Tanggal;
use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

/**
 * Ekspor pengajuan PTK ke Excel (REQ-13): semua field SPEC §4.1 (tanpa path berkas),
 * tanggal format Indonesia panjang, berkas hasil di disk privat.
 */
class PtkSubmissionExporter extends Exporter
{
    protected static ?string $model = PtkSubmission::class;

    public static function getColumns(): array
    {
        $tanggal = fn ($state): string => Tanggal::format($state);

        return [
            ExportColumn::make('submit_id')->label('Submit ID'),
            ExportColumn::make('status')->label('Status')->formatStateUsing(fn ($state): string => $state->getLabel()),
            ExportColumn::make('catatan_revisi')->label('Catatan Revisi'),
            ExportColumn::make('created_at')->label('Tanggal Kirim')->formatStateUsing($tanggal),
            ExportColumn::make('nik')->label('NIK'),
            ExportColumn::make('nama')->label('Nama'),
            ExportColumn::make('tempat_lahir')->label('Tempat Lahir'),
            ExportColumn::make('tanggal_lahir')->label('Tanggal Lahir')->formatStateUsing($tanggal),
            ExportColumn::make('jenis_kelamin')->label('Jenis Kelamin'),
            ExportColumn::make('nama_ibu_kandung')->label('Nama Ibu Kandung'),
            ExportColumn::make('agama')->label('Agama'),
            ExportColumn::make('kebutuhan_khusus')->label('Kebutuhan Khusus'),
            ExportColumn::make('status_perkawinan')->label('Status Perkawinan'),
            ExportColumn::make('email')->label('Email'),
            ExportColumn::make('provinsi')->label('Provinsi'),
            ExportColumn::make('kabupaten')->label('Kabupaten/Kota'),
            ExportColumn::make('kecamatan')->label('Kecamatan'),
            ExportColumn::make('desa')->label('Desa/Kelurahan'),
            ExportColumn::make('alamat')->label('Alamat'),
            ExportColumn::make('kode_pos')->label('Kode Pos'),
            ExportColumn::make('jenis_ptk')->label('Jenis PTK'),
            ExportColumn::make('jabatan_ptk')->label('Jabatan PTK'),
            ExportColumn::make('status_kepegawaian')->label('Status Kepegawaian'),
            ExportColumn::make('lembaga_pengangkat')->label('Lembaga Pengangkat'),
            ExportColumn::make('sk_pengangkatan')->label('SK Pengangkatan'),
            ExportColumn::make('tmt_pengangkatan')->label('TMT Pengangkatan')->formatStateUsing($tanggal),
            ExportColumn::make('sumber_gaji')->label('Sumber Gaji'),
            ExportColumn::make('lisensi_kepsek')->label('Lisensi Kepala Sekolah'),
            ExportColumn::make('school_npsn')->label('NPSN'),
            ExportColumn::make('school.name')->label('Sekolah'),
            ExportColumn::make('nomor_surat_tugas')->label('Nomor Surat Tugas'),
            ExportColumn::make('tanggal_surat_tugas')->label('Tanggal Surat Tugas')->formatStateUsing($tanggal),
            ExportColumn::make('tmt_tugas')->label('TMT Tugas')->formatStateUsing($tanggal),
        ];
    }

    public function getFormats(): array
    {
        return [ExportFormat::Xlsx];
    }

    /**
     * Berkas ekspor berisi NIK dan data pribadi → selalu disk privat (NFR-21).
     */
    public function getFileDisk(): string
    {
        return 'local';
    }

    public function getFileName(Export $export): string
    {
        return 'pengajuan-ptk-'.now()->format('Ymd-His').'-'.$export->getKey();
    }

    public static function getCompletedNotificationTitle(Export $export): string
    {
        return 'Ekspor selesai';
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Ekspor '.Number::format($export->successful_rows, locale: 'id').' pengajuan PTK selesai dan siap diunduh.';

        if ($gagal = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($gagal, locale: 'id').' baris gagal diekspor.';
        }

        return $body;
    }
}
