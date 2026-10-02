# AGENTS.md — Instruksi untuk AI

> **Layer 3: Environment.** File ini dibaca AI di awal setiap sesi.
> Tujuannya: AI tidak perlu dijelaskan ulang tentang project, aturan, dan cara kerja.

---

## 1. Konteks Project

- **Nama:** ptk-yhkb
- **Deskripsi singkat:** Aplikasi pendataan PTK 6 unit sekolah Yayasan Harapan Karya Bangsa: form publik 4 tab (kirim, cek status, perbaikan saat Perlu Revisi) dan dashboard admin untuk validasi. Pengganti form Google Apps Script.
- **Pemilik / pengambil keputusan:** Sukardi
- **Bahasa komunikasi:** Bahasa Indonesia
- **Bahasa kode:** nama tabel, model, kelas, dan method dalam bahasa Inggris (konvensi Laravel); nama kolom data PTK memakai istilah resmi Indonesia sesuai SPEC §4.1 (`nik`, `nama`, `tmt_tugas`, …); komentar dalam Bahasa Indonesia

## 2. Dokumen Acuan (urutan prioritas)

1. `SPEC.md` — apa yang dibangun (sumber kebenaran)
2. `verification/acceptance-criteria.md` — kapan dianggap selesai
3. `docs/design-system.md` — bagaimana tampilannya
4. `README.md` — cara menjalankan project
5. `PROGRESS.md` — posisi terakhir pengerjaan (bukan sumber requirement)

Jika ada konflik, ikuti dokumen dengan prioritas lebih tinggi, lalu **laporkan konfliknya**.

Saat membangun bagian admin, gunakan juga skill **filament5-dev** (referensi Filament v5).

---

## 3. Cara Kerja

### Sebelum mulai
- Baca `PROGRESS.md` untuk mengetahui posisi terakhir dan langkah berikutnya.
- Baca SPEC.md bagian yang relevan dengan tugas.
- Teks `[kurung siku]` dan contoh berawalan `mis.` **bukan keputusan**. Jika bagian yang dibutuhkan masih berisi placeholder → **berhenti dan tanyakan**, jangan pakai contohnya.
- Identifikasi checkpoint mana yang sedang dikerjakan (SPEC.md §9).
- Jika requirement ambigu atau tidak ada di SPEC → **tanya dulu, jangan berasumsi.**

### Saat bekerja
- Kerjakan **satu checkpoint** pada satu waktu.
- Perubahan kecil dan bertahap; jelaskan apa yang diubah dan kenapa.
- Jangan menambah fitur di luar SPEC tanpa persetujuan.
- Tulis test untuk kasus negatif (input salah, tanpa akses, data kosong), bukan hanya happy path.

### Mengubah SPEC
- AI **tidak boleh** mengubah isi SPEC.md secara langsung.
- Jika menemukan requirement yang kurang, salah, atau bertentangan: tulis usulan di SPEC.md §12 dengan status **Diusulkan**, lalu laporkan.
- Setelah pemilik menyetujui: terapkan perubahan, naikkan versi SPEC, catat di §11 Log Keputusan, dan perbarui acceptance criteria terkait.

### Sebelum menyatakan "selesai"
- Jalankan verifikasi sesuai `acceptance-criteria.md`.
- Laporkan hasil tiap kriteria (lolos / gagal) beserta buktinya.
- **Jangan pernah bilang selesai jika belum diverifikasi.** Kriteria MANUAL yang belum dicek pemilik ditandai ⏳.

### Sebelum sesi berakhir
- Perbarui `PROGRESS.md`: checkpoint aktif, yang sudah selesai, yang tertunda, blocker, dan langkah berikutnya.
- Perbarui matriks keterlacakan di acceptance-criteria.md §D2.

---

## 4. Tech Stack & Konvensi

| Hal | Aturan |
|---|---|
| Jenis aplikasi | Web (form publik + panel admin) |
| Bahasa & versi runtime | PHP 8.3+ |
| Framework | Laravel 13 (versi terbaru yang didukung Filament v5) |
| Frontend / UI | Publik: Blade + Alpine.js + Tailwind CSS 4.1 (Vite). Admin: Filament v5 dengan tema mengikuti design system |
| Date picker publik | flatpickr + locale `id`; `altInput` tampil `d F Y`, nilai terkirim `Y-m-d` |
| Date picker admin | `DatePicker::make()->native(false)->displayFormat('d F Y')->locale('id')` |
| Database & akses data | SQLite (`database/database.sqlite`), Eloquent; test memakai SQLite in-memory |
| Migrasi skema | `php artisan migrate` |
| Manajer paket | Composer, npm |
| Test | Pest — `php artisan test` |
| Format & lint | Laravel Pint — `./vendor/bin/pint` |
| Ekspor Excel | Exporter bawaan Filament v5 (format XLSX) |
| Penamaan | Konvensi Laravel: tabel snake_case jamak, model PascalCase tunggal |
| Konfigurasi rahasia | `.env` (DB, akun admin seeder, `PTK_ADMIN_WHATSAPP`) |
| Locale & waktu | `APP_LOCALE=id`, `APP_TIMEZONE=Asia/Jakarta`, Carbon `translatedFormat('d F Y')` |

### Perintah penting
| Tujuan | Perintah |
|---|---|
| Instal dependency | `composer install && npm install` |
| Jalankan lokal | `composer run dev` (server + Vite + queue) |
| Jalankan test | `php artisan test` |
| Format / lint | `./vendor/bin/pint` (cek saja: `./vendor/bin/pint --test`) |
| Migrasi database | `php artisan migrate --seed` |
| Build produksi | `npm run build && php artisan optimize` |

### Struktur folder penting
```
app/Filament/Resources/PtkSubmissions/   — resource admin: tabel, filter, detail, aksi status, riwayat
app/Filament/Widgets/                    — statistik per status (REQ-10)
app/Http/Controllers/Public/             — form, cek status, perbaikan, endpoint wilayah
app/Http/Requests/                       — validasi pengajuan & perbaikan (satu sumber aturan)
app/Models/                              — PtkSubmission, PtkStatusHistory, School, Province, Regency, District, Village
app/Support/                             — format tanggal Indonesia, penyamaran NIK, generator Submit ID
config/ptk.php                           — daftar Jenis → Jabatan PTK, nomor WhatsApp admin (dari .env)
database/seeders/ + database/data/       — sekolah, admin, CSV wilayah
resources/views/public/                  — halaman publik (Blade)
storage/app/private/ptk/                 — berkas KTP & SK (disk privat)
tests/Feature/                           — satu file test per bagian acceptance criteria (A, B, C, D1)
```

---

## 5. Larangan

- ❌ Mengubah struktur database tanpa memperbarui SPEC.md §4
- ❌ Mengubah SPEC.md tanpa persetujuan pemilik (usulkan di §12)
- ❌ Memakai nilai contoh (`mis. ...`) dari template sebagai keputusan
- ❌ Menjalankan migrasi destruktif (drop kolom/tabel) tanpa persetujuan
- ❌ Menghapus atau melemahkan test agar lolos
- ❌ Menaruh kredensial / API key / nomor WhatsApp di dalam kode (pakai `.env`)
- ❌ Menambah dependency baru tanpa menyebut alasannya
- ❌ Memakai warna / font di luar design system
- ❌ Memakai `<input type="date">` bawaan browser di halaman publik
- ❌ Menyimpan Foto KTP / SK di disk `public` atau membuat URL berkas yang bisa dibuka tanpa login
- ❌ Menampilkan NIK lengkap di halaman publik mana pun
- ❌ Memanggil API wilayah eksternal (Emsifa) saat aplikasi berjalan
- ❌ Menyediakan aksi ubah/hapus untuk riwayat status
- ❌ Membedakan pesan "Submit ID salah" dan "tanggal lahir salah" di Cek Status

## 6. Wajib

- ✅ Setiap fitur baru disertai test untuk acceptance criteria-nya
- ✅ Validasi semua input dari pengguna di server (validasi browser hanya pelengkap)
- ✅ Pesan error dan label UI dalam Bahasa Indonesia
- ✅ Semua tanggal yang tampil memakai satu helper format `d F Y` Indonesia
- ✅ Kecocokan Jenis → Jabatan PTK selalu diperiksa memakai `config/ptk.php`
- ✅ Setiap perubahan status / perbaikan data menulis riwayat dalam satu DB transaction

---

## 7. Format Laporan Setelah Tugas

```
## Ringkasan
[Apa yang dikerjakan]

## File yang diubah
- path/file — [alasan]

## Verifikasi
- AC-01 (REQ-01): ✅ lolos — [bukti]
- AC-02 (REQ-02): ❌ gagal — [penyebab & rencana]

## Usulan perubahan SPEC
- [jika ada — juga dicatat di SPEC.md §12]

## Pertanyaan / Keputusan yang dibutuhkan
- [jika ada]

## PROGRESS.md
- [sudah diperbarui ✅]
```

---

## 8. Pelajaran (diisi seiring waktu)

> Setiap kali AI melakukan kesalahan berulang, tambahkan aturannya di sini
> supaya tidak terulang di sesi berikutnya.

- **Filament v5 berbeda dari referensi lama (2026-10-02, CP5):** enum `Heroicon` hanya punya varian solid (`ArrowRight`) dan `Outlined...` — tidak ada `Mini...`; `Action::schema()` (bukan `form()`); `recordActions`/`toolbarActions`. Cek nama kelas/konstanta di `vendor/filament` sebelum dipakai, dan setiap halaman/widget admin baru wajib punya test yang me-render-nya (`actingAs` + `get()` / `Livewire::test`) agar error seperti konstanta/import salah tertangkap sebelum dibuka di browser.
- **`required()` pada field Filament memunculkan validasi HTML5 bawaan browser (bisa berbahasa Inggris).** Untuk pesan yang wajib Bahasa Indonesia, pakai `markAsRequired()` + `rule('required', ...)` + `validationMessages()`.
