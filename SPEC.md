# SPEC.md — ptk-yhkb

> **Layer 1: Spec.** Dokumen ini adalah sumber kebenaran tunggal tentang *apa* yang dibangun.
> Semua keputusan di sini hasil wawancara (interview) dengan pemilik project.
> Jika ada konflik antara dokumen lain dan SPEC.md, SPEC.md yang berlaku.
>
> **Konvensi penulisan:**
> - Teks dalam `[kurung siku]` = placeholder yang **wajib diganti**. Teks berawalan `mis.` hanya contoh, **bukan keputusan**.
> - Setiap requirement diberi ID `REQ-xx` agar bisa dilacak ke acceptance criteria.
> - SPEC hanya boleh diubah oleh pemilik. AI boleh **mengusulkan** perubahan (lihat §12), bukan mengubah langsung.

- **Versi:** 1.3
- **Status:** Disepakati
- **Pemilik:** Sukardi (Admin Yayasan Harapan Karya Bangsa)
- **Terakhir diperbarui:** 2026-10-02

---

## 1. Latar Belakang & Tujuan

**Masalah yang diselesaikan:**
Pendataan PTK (Pendidik dan Tenaga Kependidikan) di 6 unit sekolah Yayasan Harapan Karya Bangsa saat ini memakai form Google Apps Script yang menyimpan ke Google Sheet dan Drive. Versi tersebut punya kelemahan: siapa pun yang mengetahui Submit ID dapat melihat NIK dan nama PTK, PTK yang diminta revisi tidak bisa memperbaiki datanya sendiri (harus kirim ulang sehingga data ganda), dan input tanggal mengikuti bahasa browser (bisa tampil `mm/dd/yyyy`). Aplikasi ditulis ulang di luar Apps Script.

**Tujuan project:**
- Data seluruh PTK di 6 unit terkumpul dan tervalidasi admin di satu tempat.
- PTK dapat mengirim, mengecek status, dan memperbaiki datanya sendiri tanpa data ganda.

**Indikator keberhasilan:**
- Semua PTK aktif di 6 unit berstatus **Valid** sebelum tanggal target (lihat §10 — tanggal belum ditetapkan).
- Tidak ada dua pengajuan dengan NIK yang sama.

**Referensi perilaku:** form Apps Script versi terakhir (`form-ptk.html`) dan dashboard `AdminPTK.html` versi lama. Kode Apps Script **tidak** dipakai ulang.

---

## 2. Pengguna & Peran

| Peran | Deskripsi | Jumlah |
|---|---|---|
| Admin Yayasan | Melihat, memvalidasi, dan mengubah status seluruh pengajuan PTK semua unit | 1 |
| PTK (publik, tanpa akun) | Mengisi form, mengecek status, memperbaiki data saat Perlu Revisi | ± jumlah PTK 6 unit |

---

## 3. Ruang Lingkup

### 3.1 Masuk MVP
| ID | Requirement | Prioritas |
|---|---|---|
| REQ-01 | PTK mengisi form publik 4 tab berurutan (Identitas, Domisili, Kepegawaian, Penugasan); tab berikutnya hanya bisa dibuka bila tab sebelumnya valid | Wajib |
| REQ-02 | Pilihan Jabatan PTK bergantung pada Jenis PTK; jabatan harus sesuai jenis (diperiksa juga di server); isian Lisensi Kepala Sekolah hanya tampil dan wajib untuk Jenis PTK = Kepala Sekolah | Wajib |
| REQ-03 | Domisili dipilih bertingkat: Provinsi → Kabupaten/Kota → Kecamatan → Desa/Kelurahan; yang tersimpan adalah nama wilayah (bukan hanya kode) | Wajib |
| REQ-04 | PTK mengunggah Foto KTP (JPG/PNG) dan SK Penugasan (PDF), masing-masing maks 1 MB; berkas disimpan privat dan tidak bisa diakses tanpa login admin | Wajib |
| REQ-05 | NIK yang sudah pernah mengirim pengajuan ditolak dengan pesan yang mengarahkan ke menu Cek Status | Wajib |
| REQ-06 | Setelah berhasil kirim, PTK menerima Submit ID (`PTK-YYYYMMDD-HHmmss-XXXX`), tombol salin, dan tombol WhatsApp ke admin berisi nama, sekolah, dan Submit ID | Wajib |
| REQ-07 | PTK mengecek status dengan Submit ID + tanggal lahir; yang tampil: nama, sekolah, status, tanggal kirim, dan catatan revisi (bila ada); NIK ditampilkan tersamar; maks 5 percobaan per menit per IP | Wajib |
| REQ-08 | Bila status Perlu Revisi, PTK (Submit ID + tanggal lahir) dapat mengubah semua data dan mengganti berkas; setelah disimpan status kembali ke Menunggu | Wajib |
| REQ-09 | Admin login ke dashboard admin; seluruh halaman admin dan berkas tidak dapat diakses tanpa login | Wajib |
| REQ-10 | Dashboard menampilkan jumlah pengajuan total dan per status (Menunggu, Diproses, Valid, Perlu Revisi) | Wajib |
| REQ-11 | Admin melihat daftar pengajuan dengan pencarian (nama, NIK, Submit ID) dan filter (status, sekolah, jenis PTK), serta halaman detail lengkap termasuk membuka berkas | Wajib |
| REQ-12 | Admin mengubah status pengajuan ke salah satu dari Menunggu, Diproses, Valid, Perlu Revisi (perpindahan bebas); status Perlu Revisi wajib disertai catatan revisi | Wajib |
| REQ-13 | Admin mengekspor data pengajuan ke Excel (.xlsx), mengikuti filter yang sedang aktif | Sebaiknya |
| REQ-14 | Semua tanggal tampil dalam format Indonesia panjang `d F Y` (contoh: `02 Oktober 2026`) di form publik, cek status, dashboard, dan ekspor; input tanggal di form publik tidak memakai `<input type="date">` bawaan browser | Wajib |
| REQ-15 | Setiap perubahan status (oleh admin) dan setiap perbaikan data (oleh PTK) tercatat di riwayat: siapa, kapan, status lama → baru, catatan; riwayat tampil di detail admin | Wajib |

> Aturan: setiap REQ **wajib** punya minimal satu kriteria di `verification/acceptance-criteria.md`.

### 3.2 Tidak Masuk MVP (Out of Scope)
- Impor data lama dari Google Sheet / Drive — keputusan pemilik: mulai dari nol.
- Akun untuk operator unit / kepala sekolah — hanya admin yayasan yang memakai dashboard.
- Notifikasi email / WhatsApp otomatis — cukup tombol WhatsApp ke admin seperti versi lama.
- Backup otomatis — keputusan pemilik: tidak perlu.
- PTK mengubah data di luar status Perlu Revisi.

---

## 4. Model Data

### 4.1 Pengajuan PTK (`ptk_submissions`)

> Kolom bertipe "enum" disimpan sebagai `string` di database. Daftar nilai sah dijaga di `config/ptk.php` (dan enum PHP `PtkStatus` untuk status) lalu diperiksa saat validasi — satu sumber aturan.

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| submit_id | string | Ya | Unik, format `PTK-YYYYMMDD-HHmmss-XXXX` (waktu WIB, XXXX = 4 digit acak) |
| nik | string(16) | Ya | Unik, 16 digit angka |
| nama | string | Ya | |
| tempat_lahir | string | Ya | |
| tanggal_lahir | date | Ya | Dipakai sebagai verifikasi cek status |
| jenis_kelamin | enum: Laki-laki, Perempuan | Ya | |
| nama_ibu_kandung | string | Ya | |
| agama | enum (8 pilihan sesuai form lama) | Ya | |
| kebutuhan_khusus | enum (17 pilihan sesuai form lama) | Ya | |
| status_perkawinan | enum: Menikah, Belum Menikah, Janda/Duda | Ya | |
| email | string | Ya | Format email valid |
| foto_ktp_path | string | Ya | Disk privat; JPG/PNG ≤ 1 MB |
| provinsi_kode + provinsi, kabupaten_kode + kabupaten, kecamatan_kode + kecamatan, desa_kode + desa | string | Ya | Kode dan nama wilayah dari tabel wilayah (§4.4) disimpan di kolom terpisah: kode untuk mengisi ulang form perbaikan & validasi induk, nama untuk tampilan |
| alamat | text | Ya | |
| kode_pos | string | Tidak | Sama seperti form lama (opsional) |
| jenis_ptk | enum: Kepala Sekolah, Guru, Tenaga Kependidikan | Ya | |
| jabatan_ptk | string | Ya | Harus termasuk daftar jabatan untuk jenis_ptk |
| status_kepegawaian | enum: GTY/PTY, Tenaga Pendidikan Sekolah, Guru Tidak Tetap | Ya | |
| lembaga_pengangkat | enum (6 pilihan sesuai form lama) | Ya | |
| sk_pengangkatan | string | Ya | Nomor SK |
| tmt_pengangkatan | date | Ya | |
| sumber_gaji | enum: Yayasan, Sekolah, Pemerintah | Ya | |
| lisensi_kepsek | enum: Sudah, Belum | Kondisional | Wajib hanya bila jenis_ptk = Kepala Sekolah; selain itu kosong |
| school_npsn | string | Ya | Salah satu dari 6 unit (§4.2) |
| nomor_surat_tugas | string | Ya | |
| tanggal_surat_tugas | date | Ya | |
| tmt_tugas | date | Ya | |
| sk_penugasan_path | string | Ya | Disk privat; PDF ≤ 1 MB |
| persetujuan | boolean | Ya | Harus true |
| status | enum: Menunggu, Diproses, Valid, Perlu Revisi | Ya | Default Menunggu |
| catatan_revisi | text | Kondisional | Wajib saat status = Perlu Revisi; tampil di Cek Status |
| created_at / updated_at | datetime | Ya | |

### 4.2 Unit Sekolah (`schools`) — data tetap
| NPSN | Nama |
|---|---|
| 70027214 | KB GOLDEN BEE |
| 70011827 | SD KARYA BANGSA |
| 70050352 | SMA KARYA BANGSA |
| 70004213 | SMK KARYA BANGSA |
| 70011201 | SMP KARYA BANGSA |
| 70027216 | TK GOLDEN BEE |

### 4.3 Jabatan PTK — data tetap
Daftar Jenis PTK → Jabatan PTK persis seperti `jabatanPtkData` di form versi terakhir: Kepala Sekolah (1), Guru (88, termasuk Guru Kelas, Pamong Belajar, Tutor), Tenaga Kependidikan (10).

### 4.4 Wilayah (`provinces`, `regencies`, `districts`, `villages`)
| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| id | string | Ya | Kode wilayah |
| parent_id | string | Ya (kecuali provinsi) | Kode wilayah induk |
| name | string | Ya | |

Diimpor dari berkas CSV repositori `emsifa/api-wilayah-indonesia` (data yang sama dengan API yang dipakai form lama). Form publik membaca dari database sendiri, bukan API luar.

### 4.5 Riwayat Status (`ptk_status_histories`)
| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| ptk_submission_id | FK | Ya | |
| actor | string | Ya | Nama admin, atau "PTK" untuk perbaikan data |
| from_status / to_status | enum | Ya | from_status kosong untuk pengajuan pertama |
| note | text | Tidak | Catatan revisi / keterangan |
| created_at | datetime | Ya | |

### 4.6 Admin (`users`)
| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| name, email | string | Ya | |
| password | string (hash) | Ya | |

### 4.6a Tabel teknis (Filament)
| Tabel | Fungsi |
|---|---|
| `exports` | Riwayat ekspor Excel per admin; berkas XLSX di disk privat `storage/app/private/filament_exports` |
| `notifications` | Notifikasi "ekspor selesai" berisi tautan unduh bertanda tangan, hanya untuk admin pemilik |

Tidak menyimpan field PTK baru. Ekspor diproses di antrean, jadi **queue worker wajib berjalan** di server.

### 4.7 Relasi
- `schools` **one-to-many** `ptk_submissions` — setiap pengajuan ditugaskan ke satu unit.
- `ptk_submissions` **one-to-many** `ptk_status_histories` — jejak perubahan status.
- `provinces` → `regencies` → `districts` → `villages` **one-to-many** berjenjang.

---

## 5. Fitur & Workflow

### 5.1 Form Pengajuan PTK (publik)
**Alur:**
```
Halaman awal (Isi Form / Cek Status) → Tab Identitas → Domisili → Kepegawaian → Penugasan
→ Kirim → validasi server → simpan + berkas → tampil Submit ID + tombol salin + tombol WhatsApp admin
```
**Requirement terkait:** REQ-01, REQ-02, REQ-03, REQ-04, REQ-05, REQ-06, REQ-14

**Aturan bisnis:**
- Semua validasi dilakukan ulang di server; validasi di browser hanya untuk kenyamanan.
- Ganti Jenis PTK → Jabatan PTK dikosongkan; Jenis = Kepala Sekolah → Jabatan otomatis Kepala Sekolah.
- Ganti Jenis PTK dari Kepala Sekolah ke jenis lain → isian Lisensi Kepala Sekolah dikosongkan dan disembunyikan.
- Mengganti Jenis PTK tidak mengosongkan pilihan sekolah.
- Wilayah prioritas (unit sekolah berada di Kabupaten Sintang): pada form baru, Provinsi = **Kalimantan Barat** dan Kabupaten/Kota = **Kabupaten Sintang** sudah terpilih otomatis, dan keduanya tampil paling atas di daftarnya; PTK dari daerah lain tetap dapat menggantinya. Form yang kembali karena gagal validasi memakai pilihan PTK sendiri.
- Nomor WhatsApp admin disimpan di konfigurasi (`.env`), bukan di kode.

**Kasus gagal / edge case:**
- Field wajib kosong → tetap di tab terkait, pesan error Bahasa Indonesia.
- Jabatan tidak sesuai Jenis PTK (mis. dari request yang dimanipulasi) → ditolak.
- NIK bukan tepat 16 digit angka → ditolak.
- NIK sudah terdaftar → ditolak, pesan mengarahkan ke Cek Status.
- Berkas > 1 MB atau tipe salah → ditolak dengan pesan.
- Daftar wilayah gagal dimuat dari server → pesan error dan tombol coba lagi (tidak memakai daftar provinsi palsu).
- Kode wilayah yang dikirim tidak cocok dengan induknya (mis. kecamatan bukan bagian kabupaten terpilih) → ditolak.

### 5.2 Cek Status (publik)
**Alur:**
```
Masukkan Submit ID + tanggal lahir → cocok → tampil nama, sekolah, NIK tersamar, status, tanggal kirim, catatan revisi
→ bila Perlu Revisi: tombol Perbaiki Data
```
**Requirement terkait:** REQ-07, REQ-14

**Kasus gagal / edge case:**
- Submit ID tidak ada **atau** tanggal lahir tidak cocok → pesan yang sama: "Data tidak ditemukan" (tidak membedakan mana yang salah).
- Lebih dari 5 percobaan per menit dari IP yang sama → ditolak sementara dengan pesan "Terlalu banyak percobaan, coba lagi sebentar lagi".

### 5.3 Perbaikan Data saat Perlu Revisi (publik)
**Alur:**
```
Cek Status berhasil + status Perlu Revisi → form 4 tab terisi data lama → ubah → simpan → status = Menunggu
```
**Requirement terkait:** REQ-08, REQ-15

**Aturan bisnis:**
- Semua field dan berkas boleh diubah; berkas yang tidak diganti tetap memakai berkas lama.
- NIK baru tidak boleh sama dengan NIK pengajuan lain.
- Submit ID tidak berubah.
- Catatan revisi dari admin tampil di atas form perbaikan.
- Perbaikan dicatat di riwayat (actor = PTK, Perlu Revisi → Menunggu).

**Kasus gagal / edge case:**
- Status bukan Perlu Revisi (termasuk lewat akses URL langsung) → ditolak.
- Sesi perbaikan tanpa verifikasi Submit ID + tanggal lahir → ditolak.

### 5.4 Dashboard Admin
**Alur:**
```
Login → Dashboard (statistik) → Daftar pengajuan (cari/filter) → Detail (data + berkas + riwayat) → Ubah status / Ekspor Excel
```
**Requirement terkait:** REQ-09, REQ-10, REQ-11, REQ-12, REQ-13, REQ-14, REQ-15

**Aturan bisnis:**
- Perpindahan status bebas antar 4 status.
- Memilih Perlu Revisi tanpa catatan → ditolak.
- Setiap perubahan status tercatat di riwayat (actor = nama admin).
- Ekspor mengikuti filter/pencarian yang sedang aktif; kolom tanggal berformat Indonesia panjang.

**Kasus gagal / edge case:**
- Belum login → diarahkan ke halaman login, termasuk saat membuka URL berkas langsung.
- Password salah → login ditolak.
- Belum ada pengajuan → tampil "Belum ada data".
- Pencarian tanpa hasil → tampil pesan kosong.

---

## 6. Hak Akses

| Aksi | Admin Yayasan | PTK (publik) |
|---|---|---|
| Mengisi form pengajuan baru | — | ✅ |
| Cek status (Submit ID + tanggal lahir) | — | ✅ (hanya pengajuannya sendiri) |
| Memperbaiki data saat Perlu Revisi | — | ✅ (hanya pengajuannya sendiri) |
| Melihat semua pengajuan & berkas | ✅ | ❌ |
| Mengubah status | ✅ | ❌ |
| Ekspor data | ✅ | ❌ |

---

## 7. Kebutuhan Non-Fungsional

**Jenis project:** ☐ Website publik  ☑ Aplikasi internal (dengan form publik)  ☐ API / layanan  ☐ Aplikasi mobile/PWA

### 7.1 Umum
| ID | Aspek | Target |
|---|---|---|
| NFR-01 | Keamanan | Login admin wajib, CSRF aktif, password di-hash, validasi input & upload di server, berkas di disk privat, Cek Status maks 5 percobaan/menit/IP |
| NFR-02 | Responsif | Layar 360px s/d desktop tanpa scroll horizontal |
| NFR-03 | Bahasa | UI, label, dan pesan error Bahasa Indonesia |
| NFR-04 | Tanggal & zona waktu | Zona waktu Asia/Jakarta (WIB); tanggal tampil format Indonesia panjang di semua tempat; disimpan sebagai `YYYY-MM-DD` |

### 7.3 Data sensitif
| ID | Aspek | Target |
|---|---|---|
| NFR-20 | Audit log | Riwayat status lengkap per pengajuan (REQ-15); tidak bisa diubah/dihapus dari dashboard |
| NFR-21 | Privasi data | NIK, nama ibu kandung, Foto KTP, dan SK hanya bisa dilihat admin; di halaman publik NIK selalu tersamar |

> NFR-05 (backup) dihapus — keputusan pemilik: tidak perlu backup.

---

## 8. Tech Stack

| Lapisan | Pilihan | Alasan |
|---|---|---|
| Jenis aplikasi | Web | Form publik + dashboard admin |
| Bahasa & runtime | PHP 8.3+ (Laravel 13, Tailwind CSS 4.1+) | Laravel terbaru yang didukung Filament v5; Laravel 13 mensyaratkan PHP ^8.3 |
| Framework backend | Laravel | Sudah dikuasai pemilik; validasi, upload privat, login tersedia |
| Frontend publik | Blade + Alpine.js | Mempertahankan pengalaman form 4 tab |
| Admin panel | Filament v5 | Tabel, filter, detail, aksi status, ekspor hampir siap pakai |
| Input tanggal | Date picker non-native berlocale Indonesia (publik); Filament DatePicker `native(false)` locale `id` (admin) | `<input type="date">` mengikuti bahasa browser |
| Database | SQLite (berkas `database/database.sqlite`) | Data kecil (± jumlah PTK 6 unit + data wilayah), satu admin; tanpa server database terpisah |
| Autentikasi | Session login Filament (admin); verifikasi Submit ID + tanggal lahir (PTK) | |
| Integrasi eksternal | Tautan `wa.me` ke admin | Data wilayah disimpan lokal, tanpa API luar saat runtime |
| Hosting | **Belum diputuskan** (§10) | |

**Pertimbangan pemilihan:** pemilik sudah terbiasa dengan Laravel/Filament; data sensitif butuh kontrol akses dan penyimpanan berkas privat yang tidak tersedia secara rapi di Apps Script.

---

## 8A. Data Awal & Deployment

### 8A.1 Data awal / migrasi
| Data | Sumber | Cara masuk | Penanggung jawab |
|---|---|---|---|
| 6 unit sekolah | §4.2 | Seeder | AI |
| Daftar Jenis → Jabatan PTK | §4.3 (form versi terakhir) | Seeder / konfigurasi | AI |
| Akun admin pertama | — | Seeder, kredensial dari `.env` | Sukardi |
| Data wilayah | CSV `emsifa/api-wilayah-indonesia` | Seeder / artisan command | AI |
| Data PTK lama | — | Tidak diimpor | — |

### 8A.2 Deployment & rollback
- **Lingkungan:** [belum diputuskan — §10]
- **Cara deploy:** [belum diputuskan — §10]
- **Sebelum deploy produksi:** backup database wajib.
- **Proses latar:** queue worker (`php artisan queue:work`) wajib berjalan untuk ekspor Excel (§4.6a).
- **Rencana rollback:** checkout tag rilis sebelumnya + rollback migrasi.
- **Migrasi database:** hanya yang bisa di-rollback; migrasi destruktif wajib disetujui pemilik.

---

## 9. Rencana Bertahap (Checkpoints)

| # | Checkpoint | Hasil yang bisa dicek | Status |
|---|---|---|---|
| 1 | Setup Laravel + Filament v5 + database, locale `id` & WIB, seeder sekolah/jabatan/admin, test pertama | Migrasi & seeder jalan, admin bisa login, test hijau | ☐ |
| 2 | Data wilayah lokal + form publik 4 tab (tanpa simpan): wizard berurutan, Jenis→Jabatan, lisensi kepsek, wilayah, date picker Indonesia | Kriteria REQ-01, 02, 03, 14 (bagian form) lolos | ☐ |
| 3 | Simpan pengajuan: validasi server, upload berkas privat, NIK unik, Submit ID, tombol salin & WhatsApp | Kriteria REQ-04, 05, 06 lolos | ☐ |
| 4 | Cek status + perbaikan data saat Perlu Revisi + riwayat | Kriteria REQ-07, 08, 15 (bagian PTK) lolos | ☐ |
| 5 | Dashboard admin: statistik, daftar, cari/filter, detail + berkas + riwayat, ubah status + catatan revisi, ekspor Excel | Kriteria REQ-09–13, 14 & 15 (bagian admin) lolos | ☐ |
| 6 | Deploy ke server produksi | Aplikasi bisa diakses dari domain | ☐ |

> Posisi terakhir pengerjaan checkpoint dicatat di `PROGRESS.md`.

---

## 10. Pertanyaan Terbuka

- [ ] **Server deploy:** server apa, OS, domain/subdomain, cara deploy (menghambat checkpoint 6).
- [ ] **Tanggal target** semua PTK berstatus Valid.

---

## 11. Log Keputusan

| Tanggal | Keputusan | Alasan |
|---|---|---|
| 2026-10-02 | Ditulis ulang di luar Apps Script | Keputusan pemilik |
| 2026-10-02 | Hanya satu peran admin (admin yayasan), tanpa lingkup data per unit | Hanya pemilik yang mengelola |
| 2026-10-02 | Status: Menunggu, Diproses, Valid, Perlu Revisi; PTK dapat memperbaiki semua data & berkas saat Perlu Revisi | Menghindari pengajuan ganda |
| 2026-10-02 | Tidak mengimpor data lama | Mulai dari nol |
| 2026-10-02 | Verifikasi PTK: Submit ID + tanggal lahir; NIK tersamar di publik | Menutup kebocoran data versi lama |
| 2026-10-02 | NIK ganda ditolak, diarahkan ke Cek Status | Satu NIK satu pengajuan |
| 2026-10-02 | Laravel + Filament v5 | Kemampuan pemilik, dashboard cepat dibangun |
| 2026-10-02 | Tanggal tampil format Indonesia panjang di semua tempat | Keputusan pemilik |
| 2026-10-02 | Nama project `ptk-yhkb`; warna brand yayasan (navy & gold) | Keputusan pemilik |
| 2026-10-02 | Notifikasi: tombol WhatsApp ke admin saja; batas berkas 1 MB; tanpa backup | Keputusan pemilik |
| 2026-10-02 | MySQL 8; data wilayah disimpan lokal; palet yayasan (navy #14306C, gold #D4A017, krem #FAF7EF, Source Serif 4 + Inter) | Rekomendasi AI, disetujui pemilik |
| 2026-10-02 | Catatan revisi wajib; status bebas; ekspor Excel; riwayat status; Cek Status 5×/menit/IP; NIK tepat 16 digit | Rekomendasi AI, disetujui pemilik |
| 2026-10-02 | Kolom wilayah kode + nama terpisah; kolom "enum" sebagai string dengan daftar di `config/ptk.php`; PHP 8.3+ (Laravel 13) | Usulan AI CP1, disetujui pemilik |
| 2026-10-02 | Database SQLite, menggantikan MySQL 8 | Keputusan pemilik: data kecil |
| 2026-10-02 | Kalimantan Barat + Kabupaten Sintang terpilih otomatis dan tampil teratas di domisili | Keputusan pemilik: unit sekolah berada di Sintang |
| 2026-10-02 | Tabel teknis `exports` + `notifications` untuk ekspor; queue worker wajib di server | Usulan AI CP5, disetujui pemilik |
| 2026-10-02 | Kode desa ganda di data sumber Emsifa (`9107182005`, `9109070015`, Papua Barat Daya): hanya baris pertama yang disimpan | Keputusan pemilik: dapat diterima |

---

## 12. Riwayat Perubahan SPEC

| Versi | Tanggal | Bagian | Perubahan | Diusulkan oleh | Status |
|---|---|---|---|---|---|
| 0.1 | 2026-10-02 | Semua | Draft awal hasil interview | AI | Disetujui |
| 1.0 | 2026-10-02 | §3, §4, §5, §7, §8, §8A, §9, §10, §11 | Disepakati; rekomendasi §10 diterima (REQ-15, NFR-20, MySQL, wilayah lokal, palet) | Pemilik | Disetujui |
| 1.1 | 2026-10-02 | §4.1 | Perjelas kolom domisili: simpan kode **dan** nama wilayah sebagai kolom terpisah (`provinsi_kode` + `provinsi`, `kabupaten_kode` + `kabupaten`, `kecamatan_kode` + `kecamatan`, `desa_kode` + `desa`). Kode dibutuhkan untuk mengisi ulang dropdown di form perbaikan (REQ-08) dan validasi induk (AC-10); nama untuk tampilan (AC-11). Sudah diterapkan di migrasi CP1 | AI | Disetujui |
| 1.1 | 2026-10-02 | §4.1 | Kolom bertipe "enum" (agama, kebutuhan_khusus, jenis_ptk, status, dst.) disimpan sebagai `string` di database; daftar nilai sah dijaga di `config/ptk.php` / enum PHP `PtkStatus` dan diperiksa saat validasi — satu sumber aturan, tanpa migrasi ALTER bila daftar berubah. Sudah diterapkan di migrasi CP1 | AI | Disetujui |
| 1.1 | 2026-10-02 | §8 | Runtime menjadi **PHP 8.3+**: Laravel versi terbaru yang didukung Filament v5 adalah Laravel 13 (terpasang 13.34 + Filament 5.9), yang mensyaratkan PHP ^8.3 (SPEC menulis 8.2+) | AI | Disetujui |
| 1.1 | 2026-10-02 | §8, §11 | Database diganti dari MySQL 8 ke SQLite | Pemilik | Disetujui |
| 1.2 | 2026-10-02 | §5.1, §11 | Prioritas wilayah Kalimantan Barat + Kabupaten Sintang (terpilih otomatis & teratas); keputusan kode desa ganda | Pemilik | Disetujui |
| 1.3 | 2026-10-02 | §4, §8A.2 | Tambah tabel teknis bawaan Filament untuk ekspor Excel (REQ-13): `exports` (riwayat ekspor per admin, berkas XLSX di disk privat `storage/app/private/filament_exports`) dan `notifications` (notifikasi "ekspor selesai" + tautan unduh bertanda tangan, hanya untuk admin pemilik). Tidak menyimpan field PTK baru. Konsekuensi deploy: **queue worker wajib berjalan** (`php artisan queue:work`) karena ekspor diproses di antrean | AI | Disetujui |
