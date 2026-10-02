# PROGRESS.md — Posisi Pengerjaan

> Diperbarui AI **di akhir setiap sesi**. Dibaca AI **di awal setiap sesi**.
> File ini bukan sumber requirement — requirement tetap di `SPEC.md`.

---

## Status Saat Ini

- **Checkpoint aktif:** 5 — Dashboard admin (SPEC.md §9). Semua kriteria AUTO CP5 lolos. CP1–CP5 tinggal kriteria MANUAL ⏳ pemilik; CP6 (deploy) terhambat keputusan server.
- **Versi SPEC yang dipakai:** 1.2 (SQLite, PHP 8.3+, kolom wilayah kode+nama, kolom pilihan sebagai string, prioritas Kalbar/Sintang)
- **Terakhir diperbarui:** 2026-10-02 oleh AI

## ✅ Sudah Selesai
### Checkpoint 1 (tuntas)
- Laravel 13.34 + Filament 5.9 + Pest 4.7; locale `id` & WIB; terjemahan `lang/id`; `config/ptk.php`; migrasi & model sekolah/pengajuan/riwayat; seeder sekolah & admin; tema warna panel. Database **SQLite** (keputusan pemilik) — migrate+seed lolos.

### Checkpoint 2
- **Data wilayah:** CSV Emsifa v1 di `database/data/` (diambil dari commit `ebc5151`, commit terakhir sebelum data v1 dihapus dari repo pada 2026-09-01). Migrasi `provinces/regencies/districts/villages` (`id`, `parent_id`, `name`), model `Wilayah` + 4 turunan, `WilayahSeeder` (idempoten). Hasil: 34 / 514 / 7.215 / 80.532.
- **Endpoint lokal** `GET /wilayah/provinsi`, `/wilayah/kabupaten/{2 digit}`, `/wilayah/kecamatan/{4 digit}`, `/wilayah/desa/{7 digit}` — `Public\WilayahController`, tanpa API luar.
- **Frontend publik:** Tailwind 4.3 dengan token design system (palet bawaan Tailwind dimatikan), Inter + Source Serif 4 lewat `@fontsource`, Alpine 3, flatpickr locale `id` (`altFormat d F Y`, nilai `Y-m-d`, `disableMobile`). Font Bunny CDN bawaan skeleton dibuang.
- **Halaman:** beranda (`/`), form 4 tab (`/pengajuan`) — partial `public/partials/ptk-form.blade.php` siap dipakai ulang form perbaikan (CP4); komponen `x-field`, `x-public.select`, `x-public.tab-heading`, `x-public.tab-actions`.
- **Wizard (`resources/js/ptk-form.js`):** tab berurutan, validasi browser per tab, Jenis→Jabatan + lisensi kepsek, wilayah bertingkat (reset tingkat bawah, pesan gagal + "Coba lagi"), cek tipe/ukuran berkas, isi ulang dari old input & error server (siap untuk CP3).
- **Prioritas wilayah (SPEC v1.2):** form baru langsung memilih Kalimantan Barat + Kabupaten Sintang (`6107`), keduanya teratas di daftar; old input tidak ditimpa. Konfigurasi di `config/ptk.php` → `wilayah_prioritas`. Request wilayah memakai `?v=` agar cache browser ikut berganti bila prioritas diubah.
- Test: `tests/Feature/AFormPengajuanTest.php` — total 31 lolos.

### Checkpoint 3
- **Validasi server satu sumber:** `app/Http/Requests/PtkSubmissionRequest.php` — NIK `digits:16` + unik (pesan ke Cek Status), pilihan dari `config/ptk.php`, jabatan ∈ jenis, lisensi wajib untuk Kepsek & dikosongkan untuk lainnya, kode wilayah wajib anak dari induk terpilih, berkas JPG/PNG & PDF ≤ 1 MB (cek ekstensi + MIME isi). Method `submission()` disiapkan untuk form perbaikan CP4 (abaikan NIK sendiri, berkas opsional).
- **Simpan:** `app/Actions/StorePtkSubmission.php` — berkas ke disk `local` (`storage/app/private/ptk/ktp|sk`), nama wilayah dari kode, Submit ID (`app/Support/SubmitId.php`, WIB), riwayat awal (PTK, kosong → Menunggu) dalam satu transaction; berkas dihapus bila simpan gagal. NIK ganda karena kiriman bersamaan ditangkap dari constraint unik.
- **Privasi berkas:** `filesystems.disks.local.serve = false` (tidak ada URL `/storage` bertanda tangan); berkas hanya lewat `GET /admin/berkas/{submission}/{ktp|sk}` (middleware `auth`, tamu → login Filament via `redirectGuestsTo`).
- **Halaman terkirim** `/pengajuan/terkirim` (hanya untuk sesi pengirim): Submit ID, tombol salin, tombol `wa.me` dari `PTK_ADMIN_WHATSAPP` (disembunyikan bila kosong), tanpa NIK.
- **Tanggal:** cast `app/Casts/DateOnly.php` untuk 4 kolom tanggal — disimpan `Y-m-d` (NFR-04), serialisasi tidak bergeser ke UTC (penting untuk isi ulang form perbaikan CP4).
- Form kembali dari error server: tetap di tab yang bermasalah, isian terisi ulang, pengingat untuk memilih ulang berkas.
- Test: 83 lolos (285 assertion); uji mutasi memastikan test AC-07/AC-12 gagal bila aturan dirusak.

### Checkpoint 4
- **Helper wajib:** `app/Support/Tanggal.php` (satu-satunya format tampilan `d F Y`, selalu WIB) dan `app/Support/Nik.php` (`6171********0001`).
- **Cek Status** `/cek-status` (`Public\CekStatusController`): Submit ID (tidak peka huruf besar/spasi) + tanggal lahir (date picker), pesan sama `PESAN_TIDAK_DITEMUKAN` untuk semua kegagalan, rate limiter `cek-status` 5/menit/IP → 429 berpesan Indonesia (di `AppServiceProvider`), sesi diregenerasi setelah verifikasi, hasil lewat PRG di `/cek-status/hasil`. Badge status `x-public.status-badge` (warna token, siap dipakai juga di dashboard).
- **Perbaikan** `/perbaikan` (GET/PUT, `Public\PerbaikanController`): dijaga middleware `EnsurePerbaikanDiizinkan` (harus terverifikasi di sesi & status Perlu Revisi), validasi `PerbaikanPtkRequest` (turunan `PtkSubmissionRequest`: NIK sendiri diabaikan, berkas opsional), simpan `app/Actions/UpdatePtkSubmission.php` (Submit ID tetap, status → Menunggu, riwayat PTK, satu transaction; berkas lama yang diganti dihapus setelah commit). Form memakai partial yang sama, terisi data lama + catatan revisi.
- `resources/js/datepicker.js`: konfigurasi flatpickr bersama (form PTK & Cek Status), komponen Alpine `tanggalInput`.
- Helper test (`isiWilayahUji`, `pengajuanValid`, `ptkFormConfig`) dipindah ke `tests/Pest.php`. Test baru: `BCekStatusTest.php`, `CPerbaikanDataTest.php` — total 116 lolos; uji mutasi middleware & rate limit tertangkap.

### Checkpoint 5
- **Resource** `app/Filament/Resources/PtkSubmissions/` (slug `/admin/pengajuan`): hanya halaman daftar + detail; `canCreate/Edit/Delete = false` (SPEC §6). Tabel: Submit ID, nama+NIK, sekolah, jenis+jabatan, badge status, tanggal kirim (`Tanggal::format`); cari nama/NIK/Submit ID; filter status/sekolah/jenis; empty state "Belum ada data".
- **Detail (infolist)**: semua field §4.1 per bagian, tautan berkas ke `admin.berkas` (tab baru), catatan revisi saat Perlu Revisi.
- **Ubah Status**: `app/Actions/ChangePtkStatus.php` (catatan wajib untuk Perlu Revisi, catatan_revisi dikosongkan untuk status lain, tanpa perubahan → tanpa riwayat, satu transaction, actor = nama admin) + aksi Filament `Actions/UbahStatusAction.php` (tabel & header detail; validasi server, bukan HTML5; halaman detail dimuat ulang agar riwayat terbaru).
- **Riwayat**: `RelationManagers/StatusHistoriesRelationManager.php` read-only, urut waktu, `Tanggal::formatWaktu` (`d F Y, H:i WIB`).
- **Statistik**: `app/Filament/Widgets/StatistikPengajuan.php` (Total + 4 status, tiap kartu membuka daftar terfilter), di dasbor & atas daftar.
- **Ekspor**: `app/Filament/Exports/PtkSubmissionExporter.php` — XLSX saja, semua field §4.1 (tanpa path berkas), tanggal `d F Y`, disk `local` privat; tabel `exports` + `notifications` (usulan SPEC v1.3 §12), `databaseNotifications()` untuk tautan unduh. **Butuh queue worker.**
- **Tema admin**: `resources/css/filament/admin/theme.css` (heading Source Serif 4 lokal, latar krem); `DatePicker::configureUsing` global (`native(false)`, `d F Y`, `id`).
- Test `D1DashboardAdminTest.php` diperluas (ekspor dibaca dengan OpenSpout) — total 139 lolos; uji mutasi AC-48/AC-49 tertangkap. 2 bug (ikon `Heroicon::MiniArrowRight`, import `FontFamily`) ditemukan saat cek browser → pelajaran dicatat di AGENTS.md §8.

## 🔄 Sedang Dikerjakan / Tertunda
- Kriteria MANUAL ⏳ menunggu dicek pemilik: AC-01, 02, 03, 05, 06, 43, 46, 61, 62, NF-02 (semua sudah dicek AI di browser, bukti di acceptance-criteria).
- Usulan SPEC v1.3 (§12): tabel teknis `exports` + `notifications` dan kewajiban queue worker — menunggu persetujuan.
- Review design system DS-01–DS-04 belum dilakukan (metode REVIEW).
- `PTK_ADMIN_WHATSAPP` di `.env` lokal masih kosong — tombol WhatsApp di halaman terkirim tidak tampil sampai nomor diisi.

## ⛔ Blocker / Menunggu Keputusan
- Server deploy & domain (SPEC §10) — menghambat checkpoint 6.
- Tanggal target semua PTK Valid (SPEC §10) — tidak menghambat pembangunan.

## ➡️ Langkah Berikutnya
1. Pemilik uji coba menyeluruh di lokal (`npm run build` lalu `composer run dev` — sudah termasuk queue): kirim data → admin ubah ke Perlu Revisi → PTK cek status & perbaiki → admin Valid → ekspor. Centang kriteria ⏳.
2. Pemilik memutuskan usulan SPEC v1.3 dan server/domain deploy (SPEC §10) → CP6.
3. Opsional sebelum CP6: review DS-01–DS-04 oleh AI lain/pemilik.

---

## Riwayat Sesi

| Tanggal | Checkpoint | Yang dikerjakan | Hasil verifikasi |
|---|---|---|---|
| 2026-10-02 | 0 | Wawancara requirement, SPEC v1.0 disepakati, acceptance criteria & environment disiapkan | — |
| 2026-10-02 | 1 | Setup Laravel 13 + Filament 5 + Pest, locale/WIB, terjemahan id, config PTK, migrasi, model, seeder, tema warna panel | 17 test lolos; AC-41 ✅, AC-40 sebagian |
| 2026-10-02 | 1→2 | SPEC v1.1 (usulan disetujui + SQLite); data wilayah lokal, endpoint, form publik 4 tab, date picker Indonesia | 28 test lolos; AC-09 ✅, AC-60 sebagian; 7 kriteria MANUAL ⏳ pemilik |
| 2026-10-02 | 2 | SPEC v1.2: prioritas Kalbar + Sintang (terpilih otomatis & teratas); desa ganda diterima pemilik | 31 test lolos; AC-18 ✅ |
| 2026-10-02 | 3 | Validasi server, simpan pengajuan + berkas privat, Submit ID WIB, halaman terkirim + salin + WhatsApp, route berkas admin, cast tanggal Y-m-d | 83 test lolos; AC-04, 07, 08, 10–17, NF-01, NF-03 ✅; REQ-03–06 ✅ |
| 2026-10-02 | 4 | Cek Status (rate limit, pesan seragam, NIK tersamar), perbaikan data saat Perlu Revisi + riwayat, helper Tanggal & Nik, datepicker bersama | 116 test lolos; AC-20–23, 30–34, 60, NF-21 ✅; REQ-07, REQ-08 ✅ |
| 2026-10-02 | 5 | Dashboard Filament: daftar/cari/filter, detail + berkas, ubah status + riwayat read-only, statistik, ekspor XLSX privat, tema heading | 139 test lolos; REQ-09, 12, 13, 15 ✅; AC-43, 46, 62 ⏳ pemilik |
