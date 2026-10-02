<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengajuan PTK (SPEC.md §4.1).
     *
     * Daftar pilihan (agama, kebutuhan khusus, jabatan, dst.) disimpan sebagai string
     * dan diperiksa terhadap config/ptk.php saat validasi, agar satu sumber aturan.
     */
    public function up(): void
    {
        Schema::create('ptk_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('submit_id', 30)->unique();

            // Identitas
            $table->string('nik', 16)->unique();
            $table->string('nama');
            $table->string('tempat_lahir');
            $table->date('tanggal_lahir');
            $table->string('jenis_kelamin', 20);
            $table->string('nama_ibu_kandung');
            $table->string('agama', 50);
            $table->string('kebutuhan_khusus', 50);
            $table->string('status_perkawinan', 20);
            $table->string('email');
            $table->string('foto_ktp_path');

            // Domisili — kode wilayah (untuk mengisi ulang form perbaikan) + nama wilayah
            $table->string('provinsi_kode', 13);
            $table->string('provinsi');
            $table->string('kabupaten_kode', 13);
            $table->string('kabupaten');
            $table->string('kecamatan_kode', 13);
            $table->string('kecamatan');
            $table->string('desa_kode', 13);
            $table->string('desa');
            $table->text('alamat');
            $table->string('kode_pos', 10)->nullable();

            // Kepegawaian
            $table->string('jenis_ptk', 30);
            $table->string('jabatan_ptk');
            $table->string('status_kepegawaian', 50);
            $table->string('lembaga_pengangkat', 50);
            $table->string('sk_pengangkatan');
            $table->date('tmt_pengangkatan');
            $table->string('sumber_gaji', 20);
            $table->string('lisensi_kepsek', 10)->nullable();

            // Penugasan
            $table->string('school_npsn', 8);
            $table->foreign('school_npsn')->references('npsn')->on('schools')->restrictOnDelete();
            $table->string('nomor_surat_tugas');
            $table->date('tanggal_surat_tugas');
            $table->date('tmt_tugas');
            $table->string('sk_penugasan_path');
            $table->boolean('persetujuan');

            // Validasi admin
            $table->string('status', 20)->default('Menunggu')->index();
            $table->text('catatan_revisi')->nullable();

            $table->timestamps();

            $table->index('jenis_ptk');
            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ptk_submissions');
    }
};
