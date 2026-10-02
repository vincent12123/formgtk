# Acceptance Criteria — ptk-yhkb

> **Layer 2: Verifier.** Dokumen ini mendefinisikan kapan sebuah pekerjaan dianggap **selesai**.
> Setiap kriteria harus bisa dijawab **ya/tidak** — tidak boleh "kurang lebih sudah".
> Diturunkan langsung dari SPEC.md v1.2. Setiap requirement di SPEC minimal punya satu kriteria di sini.

---

## Cara Memakai

1. Sebelum mulai coding satu fitur, baca kriteria fitur tersebut.
2. Setelah selesai, periksa setiap kriteria dan tandai hasilnya.
3. Pekerjaan **belum selesai** selama masih ada kriteria yang gagal.
4. Tuliskan bukti verifikasi (perintah test, screenshot, URL, output).
5. Kolom **Req** wajib diisi dengan ID requirement dari `SPEC.md §3.1` (atau `NFR-xx` dari §7).

**Aturan penulisan kriteria:**
- Setiap fitur wajib punya **minimal satu kriteria gagal/negatif** (input tidak valid, tanpa hak akses, data kosong, duplikat).
- Kriteria harus spesifik dan terukur — hindari kata "cepat", "mudah", "rapi" tanpa angka.

**Metode verifikasi:**
- `AUTO` — dicek otomatis (Pest feature/unit test, Pint)
- `MANUAL` — dicek manusia di browser
- `REVIEW` — ditinjau AI lain atau reviewer terhadap SPEC

**Status:** ☐ belum · ✅ lolos · ❌ gagal · ⏳ menunggu dicek pemilik (MANUAL)

---

## A. Form Pengajuan PTK (publik) — SPEC §5.1

| ID | Req | Kriteria (Given – When – Then) | Jenis | Metode | Status | Bukti |
|---|---|---|---|---|---|---|
| AC-01 | REQ-01 | **Diberikan** tab Identitas dengan NIK kosong, **ketika** menekan "Lanjut ke Domisili", **maka** tetap di tab Identitas dan tampil pesan error berbahasa Indonesia | Negatif | MANUAL | ⏳ | Tetap di Identitas + 11 pesan error Indonesia saat NIK dkk. kosong — dicek AI di browser 2026-10-02 (CP2), menunggu pemilik |
| AC-02 | REQ-01 | **Diberikan** tab Identitas belum valid, **ketika** mengklik tab "Penugasan" di navigasi atas, **maka** yang tampil tab Identitas beserta pesan error | Negatif | MANUAL | ⏳ | Klik tab Penugasan saat Identitas kosong → tetap di Identitas dengan error — dicek AI di browser 2026-10-02 (CP2), menunggu pemilik |
| AC-03 | REQ-01 | **Diberikan** semua field valid di 4 tab, **ketika** menekan "Lanjut" di tiap tab, **maka** tab berpindah berurutan sampai Penugasan | Positif | MANUAL | ⏳ | Data valid → Identitas → Domisili → Kepegawaian → Penugasan berurutan, Kirim lolos validasi browser — dicek AI di browser 2026-10-02 (CP2), menunggu pemilik |
| AC-04 | REQ-01 | **Diberikan** request kirim tanpa field wajib (mis. `nama` kosong), **ketika** dikirim langsung ke server, **maka** ditolak dengan error validasi dan jumlah data `ptk_submissions` tidak bertambah | Negatif | AUTO | ✅ | `tests/Feature/AFormPengajuanTest.php` — 7 field wajib + persetujuan kosong ditolak, jumlah data tetap 0 (CP3) |
| AC-05 | REQ-02 | **Diberikan** Jenis PTK = Guru, **ketika** membuka dropdown Jabatan, **maka** berisi tepat 88 jabatan Guru; **ketika** Jenis diganti Tenaga Kependidikan, **maka** Jabatan kembali kosong dan berisi tepat 10 pilihan | Positif | MANUAL | ⏳ | Guru = 88 jabatan; ganti ke Tenaga Kependidikan → jabatan kosong, 10 pilihan — dicek AI di browser 2026-10-02 (CP2), menunggu pemilik |
| AC-06 | REQ-02 | **Diberikan** Jenis PTK = Kepala Sekolah, **ketika** dipilih, **maka** Jabatan otomatis "Kepala Sekolah" dan isian Lisensi Kepala Sekolah tampil; **ketika** Jenis diganti Guru, **maka** isian Lisensi tersembunyi dan pilihannya kosong | Positif | MANUAL | ⏳ | Kepala Sekolah → jabatan otomatis + Lisensi tampil; ganti ke Guru → Lisensi tersembunyi & kosong — dicek AI di browser 2026-10-02 (CP2), menunggu pemilik |
| AC-07 | REQ-02 | **Diberikan** request dengan Jenis = Guru dan Jabatan = "Kepala Sekolah", **ketika** dikirim, **maka** ditolak dengan pesan "Jabatan PTK tidak sesuai dengan Jenis PTK" | Negatif | AUTO | ✅ | `tests/Feature/AFormPengajuanTest.php` — pesan "Jabatan PTK tidak sesuai dengan Jenis PTK." (CP3) |
| AC-08 | REQ-02 | **Diberikan** Jenis = Kepala Sekolah tanpa Lisensi, **ketika** dikirim, **maka** ditolak; **diberikan** Jenis = Guru dengan Lisensi terisi, **ketika** dikirim, **maka** tersimpan dengan `lisensi_kepsek` kosong | Negatif | AUTO | ✅ | `tests/Feature/AFormPengajuanTest.php` — Kepsek tanpa lisensi ditolak; Guru + lisensi tersimpan `null` (CP3) |
| AC-09 | REQ-03 | **Diberikan** kode provinsi Kalimantan Barat, **ketika** meminta daftar kabupaten, **maka** respons hanya berisi kabupaten/kota dengan `parent_id` provinsi tersebut | Positif | AUTO | ✅ | `tests/Feature/AFormPengajuanTest.php` — /wilayah/kabupaten/61 hanya berisi parent_id 61 (CP2) |
| AC-10 | REQ-03 | **Diberikan** kecamatan yang bukan bagian kabupaten terpilih, **ketika** dikirim, **maka** ditolak | Negatif | AUTO | ✅ | `tests/Feature/AFormPengajuanTest.php` — 5 kombinasi kode wilayah salah induk/tidak ada ditolak (CP3) |
| AC-11 | REQ-03 | **Diberikan** pengajuan valid, **ketika** tersimpan, **maka** kolom provinsi/kabupaten/kecamatan/desa berisi nama wilayah (bukan hanya kode) | Positif | AUTO | ✅ | `tests/Feature/AFormPengajuanTest.php` — kode + nama wilayah tersimpan (CP3) |
| AC-18 | REQ-03 | **Diberikan** form pengajuan baru dibuka, **ketika** tab Domisili tampil, **maka** Provinsi = Kalimantan Barat dan Kabupaten/Kota = Kabupaten Sintang sudah terpilih, kecamatan Sintang termuat, dan keduanya paling atas di daftarnya; **diberikan** form kembali dengan old input provinsi lain, **maka** pilihan PTK tidak ditimpa | Positif | AUTO | ✅ | `tests/Feature/AFormPengajuanTest.php` (urutan & nilai awal); dicek AI di browser: 14 kecamatan Sintang termuat (2026-10-02) |
| AC-12 | REQ-04 | **Diberikan** Foto KTP 1,2 MB, atau Foto KTP berformat PDF, atau SK Penugasan berformat JPG, **ketika** dikirim, **maka** ditolak dengan pesan ukuran/tipe berkas | Negatif | AUTO | ✅ | `tests/Feature/AFormPengajuanTest.php` — KTP 1,2 MB / KTP PDF / SK JPG / SK 1,2 MB ditolak; tepat 1 MB diterima (CP3) |
| AC-13 | REQ-04 | **Diberikan** pengajuan tersimpan, **ketika** berkas diperiksa, **maka** berada di disk privat (bukan `public`); **ketika** URL berkas dibuka tanpa login, **maka** diarahkan ke login / 403 | Negatif | AUTO | ✅ | `tests/Feature/AFormPengajuanTest.php` — berkas di disk `local` (storage/app/private), `serve` dimatikan; tamu → login admin; admin → 200 (CP3) |
| AC-14 | REQ-05 | **Diberikan** NIK sudah ada di database, **ketika** pengajuan baru dengan NIK itu dikirim, **maka** ditolak dengan pesan yang menyebut menu "Cek Status" dan jumlah data tidak bertambah | Negatif | AUTO | ✅ | `tests/Feature/AFormPengajuanTest.php` — NIK ganda ditolak, pesan menyebut "Cek Status", data tetap 1; juga dicek di browser (CP3) |
| AC-15 | REQ-05 | **Diberikan** NIK 15 digit atau berisi huruf, **ketika** dikirim, **maka** ditolak | Negatif | AUTO | ✅ | `tests/Feature/AFormPengajuanTest.php` — 15/17 digit, huruf, spasi, titik ditolak (CP3) |
| AC-16 | REQ-06 | **Diberikan** pengajuan valid, **ketika** tersimpan, **maka** `submit_id` cocok dengan pola `^PTK-\d{8}-\d{6}-\d{4}$` dan bagian tanggal-jamnya mengikuti WIB | Positif | AUTO | ✅ | `tests/Feature/AFormPengajuanTest.php` — 01:30:45 UTC → `PTK-20261002-083045-XXXX` (CP3) |
| AC-17 | REQ-06 | **Diberikan** pengajuan berhasil, **ketika** halaman sukses tampil, **maka** ada Submit ID, tombol salin, dan tautan `wa.me` ke nomor dari konfigurasi yang teksnya berisi nama, sekolah, dan Submit ID | Positif | AUTO | ✅ | `tests/Feature/AFormPengajuanTest.php` — Submit ID, tombol salin, `wa.me/<PTK_ADMIN_WHATSAPP>` berisi nama/sekolah/Submit ID; juga dicek di browser (CP3) |
## B. Cek Status (publik) — SPEC §5.2

| ID | Req | Kriteria | Jenis | Metode | Status | Bukti |
|---|---|---|---|---|---|---|
| AC-20 | REQ-07 | **Diberikan** Submit ID dan tanggal lahir benar, **ketika** cek status, **maka** tampil nama, sekolah, status, dan tanggal kirim berformat `d F Y` Indonesia | Positif | AUTO | ✅ | `tests/Feature/BCekStatusTest.php` — nama, sekolah, status, Submit ID, "02 Oktober 2026"; dicek di browser (CP4) |
| AC-21 | REQ-07 | **Diberikan** Submit ID benar tetapi tanggal lahir salah, **ketika** cek status, **maka** tampil "Data tidak ditemukan" — pesan identik dengan Submit ID yang tidak ada | Negatif | AUTO | ✅ | `tests/Feature/BCekStatusTest.php` — pesan identik (konstanta `PESAN_TIDAK_DITEMUKAN`) untuk tanggal salah & Submit ID tidak ada; sesi tidak terverifikasi (CP4) |
| AC-22 | REQ-07 | **Diberikan** 5 percobaan dari IP yang sama dalam 1 menit, **ketika** percobaan ke-6, **maka** ditolak (HTTP 429) dengan pesan berbahasa Indonesia | Negatif | AUTO | ✅ | `tests/Feature/BCekStatusTest.php` — 5× → 302, ke-6 → 429 "Terlalu banyak percobaan, coba lagi sebentar lagi."; IP lain tetap bisa (CP4) |
| AC-23 | REQ-07 | **Diberikan** status Perlu Revisi, **ketika** cek status berhasil, **maka** catatan revisi dan tombol "Perbaiki Data" tampil; **diberikan** status lain, **maka** tombol tidak tampil | Positif | AUTO | ✅ | `tests/Feature/BCekStatusTest.php` — Perlu Revisi: catatan + tombol; Menunggu/Diproses/Valid: tidak ada (CP4) |
## C. Perbaikan Data saat Perlu Revisi — SPEC §5.3

| ID | Req | Kriteria | Jenis | Metode | Status | Bukti |
|---|---|---|---|---|---|---|
| AC-30 | REQ-08 | **Diberikan** status Perlu Revisi dan verifikasi berhasil, **ketika** PTK mengubah nama dan mengganti SK Penugasan lalu menyimpan, **maka** data berubah, status = Menunggu, Submit ID tetap, dan Foto KTP lama tetap terpakai | Positif | AUTO | ✅ | `tests/Feature/CPerbaikanDataTest.php` — nama & SK berubah, status Menunggu, Submit ID & Foto KTP tetap, SK lama dihapus; dicek di browser (CP4) |
| AC-31 | REQ-08 | **Diberikan** status Valid, **ketika** URL perbaikan dibuka langsung (meski sudah verifikasi), **maka** ditolak | Negatif | AUTO | ✅ | `tests/Feature/CPerbaikanDataTest.php` — Valid/Menunggu/Diproses: GET & PUT /perbaikan ditolak, data tidak berubah (CP4) |
| AC-32 | REQ-08 | **Diberikan** belum verifikasi Submit ID + tanggal lahir di sesi ini, **ketika** mengirim perbaikan, **maka** ditolak | Negatif | AUTO | ✅ | `tests/Feature/CPerbaikanDataTest.php` — tanpa verifikasi / verifikasi gagal → diarahkan ke Cek Status (CP4) |
| AC-33 | REQ-08 | **Diberikan** NIK diubah menjadi NIK milik pengajuan lain, **ketika** disimpan, **maka** ditolak | Negatif | AUTO | ✅ | `tests/Feature/CPerbaikanDataTest.php` — NIK pengajuan lain ditolak; NIK baru yang belum dipakai diterima (CP4) |
| AC-34 | REQ-15 | **Diberikan** perbaikan berhasil, **ketika** riwayat diperiksa, **maka** ada entri actor = "PTK", Perlu Revisi → Menunggu | Positif | AUTO | ✅ | `tests/Feature/CPerbaikanDataTest.php` — tepat 1 riwayat: PTK, Perlu Revisi → Menunggu (CP4) |
## D1. Dashboard Admin — SPEC §5.4

| ID | Req | Kriteria | Jenis | Metode | Status | Bukti |
|---|---|---|---|---|---|---|
| AC-40 | REQ-09 | **Diberikan** belum login, **ketika** membuka `/admin` atau halaman detail pengajuan, **maka** diarahkan ke halaman login | Negatif | AUTO | ✅ | `tests/Feature/D1DashboardAdminTest.php` — tamu membuka `/admin`, daftar, dan detail pengajuan → login (CP1, CP5) |
| AC-41 | REQ-09 | **Diberikan** password salah, **ketika** login, **maka** ditolak dan tetap di halaman login | Negatif | AUTO | ✅ | `tests/Feature/D1DashboardAdminTest.php` — password salah & email tak terdaftar ditolak, tetap tamu (CP1, 2026-10-02) |
| AC-42 | REQ-10 | **Diberikan** 3 pengajuan Menunggu, 1 Diproses, 2 Valid, 0 Perlu Revisi, **ketika** membuka dashboard, **maka** tampil Total 6, Menunggu 3, Diproses 1, Valid 2, Perlu Revisi 0 | Positif | AUTO | ✅ | `tests/Feature/D1DashboardAdminTest.php` — widget: Total 6, Menunggu 3, Diproses 1, Valid 2, Perlu Revisi 0 (CP5) |
| AC-43 | REQ-10 | **Diberikan** belum ada pengajuan, **ketika** membuka daftar pengajuan, **maka** tampil pesan kosong berbahasa Indonesia dan statistik bernilai 0 | Negatif | MANUAL | ⏳ | Otomatis lolos (`tests/Feature/D1DashboardAdminTest.php`: "Belum ada data" + statistik 0) — dicek AI di browser 2026-10-02 (CP5), menunggu pemilik |
| AC-44 | REQ-11 | **Diberikan** 3 pengajuan, **ketika** mencari dengan NIK / Submit ID / nama salah satunya, **maka** hanya pengajuan itu yang tampil | Positif | AUTO | ✅ | `tests/Feature/D1DashboardAdminTest.php` — cari NIK / Submit ID / nama → hanya 1 baris; tanpa hasil → 0 (CP5) |
| AC-45 | REQ-11 | **Diberikan** pengajuan di SMK dan SD, **ketika** filter sekolah = SMK KARYA BANGSA, **maka** hanya pengajuan SMK yang tampil (sama untuk filter status dan jenis PTK) | Positif | AUTO | ✅ | `tests/Feature/D1DashboardAdminTest.php` — filter sekolah, status, jenis PTK (CP5) |
| AC-46 | REQ-11 | **Diberikan** admin login, **ketika** membuka detail pengajuan, **maka** semua field SPEC §4.1 tampil dan Foto KTP serta SK Penugasan bisa dibuka | Positif | MANUAL | ⏳ | Otomatis lolos (`tests/Feature/D1DashboardAdminTest.php`: semua field §4.1 + tautan berkas; berkas SK terbuka 200 application/pdf) — dicek AI di browser 2026-10-02 (CP5), menunggu pemilik |
| AC-47 | REQ-12 | **Diberikan** pengajuan Menunggu, **ketika** admin mengubah ke Valid, **maka** status tersimpan dan riwayat bertambah (nama admin, Menunggu → Valid); perpindahan Valid → Diproses juga diterima | Positif | AUTO | ✅ | `tests/Feature/D1DashboardAdminTest.php` — Menunggu → Valid (riwayat "Admin Yayasan"), Valid → Diproses dari tabel; juga di browser (CP5) |
| AC-48 | REQ-12 | **Diberikan** admin memilih Perlu Revisi tanpa catatan, **ketika** menyimpan, **maka** ditolak dengan pesan "Catatan revisi wajib diisi" | Negatif | AUTO | ✅ | `tests/Feature/D1DashboardAdminTest.php` — pesan "Catatan revisi wajib diisi." (validasi server, bukan HTML5 browser); status & riwayat tidak berubah (CP5) |
| AC-49 | REQ-13 | **Diberikan** filter status = Valid aktif, **ketika** ekspor, **maka** terunduh berkas `.xlsx` yang hanya berisi pengajuan Valid dan kolom tanggal berformat `d F Y` Indonesia | Positif | AUTO | ✅ | `tests/Feature/D1DashboardAdminTest.php` — XLSX dibaca OpenSpout: hanya baris Valid, tanggal "17 Mei 1990"; mengikuti pencarian; unduh via notifikasi dicek di browser (CP5) |
| AC-50 | REQ-13 | **Diberikan** filter yang tidak menghasilkan data, **ketika** ekspor, **maka** berkas `.xlsx` berisi baris judul kolom saja (tanpa error) | Negatif | AUTO | ✅ | `tests/Feature/D1DashboardAdminTest.php` — filter tanpa data → XLSX berisi baris judul saja (CP5) |
| AC-51 | REQ-15 | **Diberikan** admin login, **ketika** membuka riwayat sebuah pengajuan, **maka** entri tampil berurutan waktu dan tidak ada aksi ubah/hapus riwayat | Negatif | AUTO | ✅ | `tests/Feature/D1DashboardAdminTest.php` — riwayat urut waktu, tanpa aksi create/edit/delete/dissociate (CP5) |
## D1b. Tanggal Indonesia — REQ-14

| ID | Req | Kriteria | Jenis | Metode | Status | Bukti |
|---|---|---|---|---|---|---|
| AC-60 | REQ-14 | **Diberikan** halaman form publik dan form perbaikan, **ketika** HTML diperiksa, **maka** tidak ada elemen `<input type="date">` | Negatif | AUTO | ✅ | `AFormPengajuanTest` (form pengajuan) + `tests/Feature/CPerbaikanDataTest.php` (form perbaikan) — tanpa `type=date` (CP4) |
| AC-61 | REQ-14 | **Diberikan** date picker di form publik, **ketika** dibuka di browser berbahasa Inggris, **maka** nama bulan/hari berbahasa Indonesia; **ketika** memilih 2 Oktober 2026, **maka** tampil "02 Oktober 2026" dan nilai terkirim `2026-10-02` | Positif | MANUAL | ⏳ | Bulan/hari Indonesia; pilih 2026-10-02 → tampil "02 Oktober 2026", terkirim `2026-10-02` — dicek AI di browser 2026-10-02 (CP2), menunggu pemilik |
| AC-62 | REQ-14 | **Diberikan** admin login, **ketika** melihat tabel, detail, dan DatePicker di dashboard, **maka** semua tanggal tampil format `d F Y` Indonesia | Positif | MANUAL | ⏳ | Tabel, detail, riwayat, dan ekspor memakai `Tanggal::format` (`d F Y`); `DatePicker` global `native(false)`+`d F Y`+`id` (belum ada DatePicker di panel) — dicek AI di browser 2026-10-02 (CP5), menunggu pemilik |
---

## C. Kebutuhan Non-Fungsional

| ID | Req | Kriteria | Metode | Status | Bukti |
|---|---|---|---|---|---|
| NF-01 | NFR-01 | POST form publik tanpa token CSRF ditolak (419); halaman admin tidak bisa diakses tanpa login (lihat AC-40) | AUTO | ✅ | `tests/Feature/AFormPengajuanTest.php` — POST tanpa token CSRF → 419 (CP3); admin wajib login: AC-40 |
| NF-02 | NFR-02 | Tidak ada scroll horizontal di lebar layar 360px pada form publik, cek status, dan dashboard | MANUAL | ⏳ | Form publik, Cek Status, dasbor, daftar, detail admin: scrollWidth = lebar layar di 360px (CP2, CP5) — dicek AI, menunggu pemilik |
| NF-03 | NFR-03 | Pesan validasi untuk field wajib, email, dan ukuran berkas berbahasa Indonesia | AUTO | ✅ | `SetupTest` (wajib, email) + `tests/Feature/AFormPengajuanTest.php` (ukuran & tipe berkas) (CP3) |
| NF-04 | NFR-04 | Dengan server berjalan di UTC, waktu kirim yang tampil di Cek Status dan dashboard = WIB | AUTO | ✅ | `tests/Feature/BCekStatusTest.php` (Cek Status) + `tests/Feature/D1DashboardAdminTest.php` daftar admin: 20:00 UTC tampil "02 Oktober 2026" saat app di UTC (CP4–CP5) |
| NF-20 | NFR-20 | Setiap perubahan status dan perbaikan data menghasilkan tepat satu entri riwayat (lihat AC-34, AC-47) | AUTO | ✅ | `CPerbaikanDataTest` (perbaikan PTK) + `tests/Feature/D1DashboardAdminTest.php` (ubah status admin; tanpa perubahan → 0 entri) (CP4–CP5) |
| NF-21 | NFR-21 | Respons halaman Cek Status dan halaman sukses tidak memuat NIK lengkap 16 digit | AUTO | ✅ | Halaman terkirim (`AFormPengajuanTest`) + Cek Status tampil `6171********0001` (`tests/Feature/BCekStatusTest.php`) (CP3–CP4) |
---

## D. Kepatuhan Design System

| ID | Kriteria | Metode | Status |
|---|---|---|---|
| DS-01 | Hanya memakai warna yang terdaftar di `docs/design-system.md` (form publik dan tema Filament) | REVIEW | ☐ |
| DS-02 | Judul memakai Source Serif 4, teks dan UI memakai Inter | REVIEW | ☐ |
| DS-03 | Komponen tombol mengikuti varian yang ditetapkan | REVIEW | ☐ |
| DS-04 | Badge status memakai warna status yang sama di Cek Status dan dashboard | REVIEW | ☐ |

---

## D2. Matriks Keterlacakan (Traceability)

| Req | Kriteria terkait | Semua lolos? |
|---|---|---|
| REQ-01 | AC-01, AC-02, AC-03, AC-04 | ☐ (AC-04 ✅; AC-01–03 ⏳ pemilik) |
| REQ-02 | AC-05, AC-06, AC-07, AC-08 | ☐ (AC-07–08 ✅; AC-05–06 ⏳ pemilik) |
| REQ-03 | AC-09, AC-10, AC-11, AC-18 | ✅ |
| REQ-04 | AC-12, AC-13 | ✅ |
| REQ-05 | AC-14, AC-15 | ✅ |
| REQ-06 | AC-16, AC-17 | ✅ |
| REQ-07 | AC-20, AC-21, AC-22, AC-23 | ✅ |
| REQ-08 | AC-30, AC-31, AC-32, AC-33 | ✅ |
| REQ-09 | AC-40, AC-41 | ✅ |
| REQ-10 | AC-42, AC-43 | ☐ (AC-42 ✅; AC-43 ⏳ pemilik) |
| REQ-11 | AC-44, AC-45, AC-46 | ☐ (AC-44–45 ✅; AC-46 ⏳ pemilik) |
| REQ-12 | AC-47, AC-48 | ✅ |
| REQ-13 | AC-49, AC-50 | ✅ |
| REQ-14 | AC-20, AC-49, AC-60, AC-61, AC-62 | ☐ (AC-20, 49, 60 ✅; AC-61, 62 ⏳ pemilik) |
| REQ-15 | AC-34, AC-47, AC-51 | ✅ |
| NFR-01 | NF-01 | ✅ |
| NFR-02 | NF-02 | ☐ (semua halaman dicek AI; ⏳ pemilik) |
| NFR-03 | NF-03 | ✅ |
| NFR-04 | NF-04 | ✅ |
| NFR-20 | NF-20 | ✅ |
| NFR-21 | NF-21 | ✅ |

---

## E. Definition of Done (berlaku untuk semua pekerjaan)

- [ ] Semua kriteria fitur terkait berstatus lolos
- [ ] Semua test otomatis hijau (`php artisan test`)
- [ ] `./vendor/bin/pint --test` tanpa temuan
- [ ] Tidak ada error / warning baru di `storage/logs/laravel.log`
- [ ] SPEC.md diperbarui bila ada perubahan perilaku (lewat §12)
- [ ] Tidak ada data dummy / kredensial tertinggal di kode
- [ ] Setiap fitur yang dikerjakan punya minimal satu kriteria negatif yang lolos
- [ ] `PROGRESS.md` diperbarui (apa yang selesai, apa yang tertunda, langkah berikutnya)

---

## F. Riwayat Verifikasi

| Tanggal | Checkpoint | Hasil | Catatan |
|---|---|---|---|
| 2026-10-02 | 1 | 17 test lolos (66 assertion), Pint bersih, migrate:fresh --seed jalan, log kosong | AC-41 lolos; AC-40 bagian `/admin` lolos (detail menyusul CP5). Database diputuskan SQLite (SPEC v1.1) — migrate+seed SQLite lolos, CP1 tuntas |
| 2026-10-02 | 2 | 28 test lolos (113 assertion), Pint bersih, migrate:fresh --seed (termasuk 80.532 desa) jalan, log kosong; uji browser manual oleh AI | AC-09 ✅, AC-60 sebagian ✅; AC-01–03, 05, 06, 61, NF-02 ⏳ menunggu pemilik |
| 2026-10-02 | 2 | Tambahan prioritas Kalbar/Sintang: 31 test lolos (122 assertion), Pint bersih, dicek di browser | AC-18 ✅ |
| 2026-10-02 | 3 | 83 test lolos (285 assertion), Pint bersih, log kosong; uji mutasi (3 test gagal saat aturan dirusak); kirim sukses & NIK ganda dicek di browser | AC-04, 07, 08, 10–17, NF-01, NF-03 ✅; NF-21 sebagian |
| 2026-10-02 | 4 | 116 test lolos (476 assertion), Pint bersih, log kosong; uji mutasi (middleware status & rate limit) tertangkap; alur cek status → perbaikan → simpan dicek di browser | AC-20–23, 30–34, 60, NF-21 ✅; NF-04 & NF-20 sebagian |
| 2026-10-02 | 5 | 139 test lolos (645 assertion), Pint bersih; uji mutasi AC-48/AC-49 tertangkap; dasbor, ubah status, ekspor + unduh, berkas, 360px dicek di browser; 2 bug ditemukan & diperbaiki (ikon, import) — log dibersihkan | AC-40, 42, 44, 45, 47–51, NF-04, NF-20 ✅; AC-43, 46, 62 ⏳ pemilik |
| 2026-10-02 | 6 | Live di https://formptk.karyabangsa.sch.id: halaman publik 200, /admin → login, /.env 403, berkas tanpa login → login, Livewire JS 200, X-Forwarded-For/Proto dari cloudflared terkonfirmasi, form produksi tanpa error console & tanpa aset http; 6 app lain tetap 200; 142 test lolos | CP6 ✅ |
