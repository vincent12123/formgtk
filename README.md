# ptk-yhkb

Aplikasi pendataan PTK (Pendidik dan Tenaga Kependidikan) untuk 6 unit sekolah Yayasan Harapan Karya Bangsa: form publik untuk PTK dan dashboard admin untuk validasi. Pengganti form Google Apps Script.

---

## 📁 Struktur Dokumentasi

Project ini memakai pendekatan **Spec-Driven Development** (template **app baru**) dengan 3 lapisan.
Untuk memperbaiki aplikasi yang sudah berjalan, pakai template **perbaikan-app**.

| Lapisan | File | Fungsi |
|---|---|---|
| **1. Spec** | [`SPEC.md`](SPEC.md) | Apa yang dibangun |
| **2. Verifier** | [`verification/acceptance-criteria.md`](verification/acceptance-criteria.md) | Kapan dianggap selesai |
| **3. Environment** | [`AGENTS.md`](AGENTS.md) | Aturan kerja AI |
| | [`CLAUDE.md`](CLAUDE.md) | Penghubung agar Claude Code membaca AGENTS.md |
| | [`PROGRESS.md`](PROGRESS.md) | Posisi terakhir pengerjaan antar sesi |
| | [`docs/design-system.md`](docs/design-system.md) | Aturan tampilan |
| | `README.md` | Orientasi & cara menjalankan |

```
ptk-yhkb/
├── AGENTS.md
├── CLAUDE.md
├── PROGRESS.md
├── README.md
├── SPEC.md
├── docs/
│   └── design-system.md
└── verification/
    └── acceptance-criteria.md
```

---

## 🔄 Alur Kerja dengan AI

1. **Interview** — AI mewawancarai pemilik project → hasilnya `SPEC.md` (requirement ber-ID `REQ-xx`)
2. **Turunkan Verifier** — setiap REQ → kriteria ya/tidak (positif **dan** negatif) di `acceptance-criteria.md`
3. **Siapkan Environment** — `AGENTS.md` + `design-system.md`
4. **Bangun per checkpoint** — AI kerjakan satu checkpoint (SPEC.md §9)
5. **Verifikasi** — cek terhadap acceptance criteria, baru lanjut
6. **Catat posisi** — AI memperbarui `PROGRESS.md` di akhir sesi
7. **Perbaiki sistem** — kesalahan berulang dicatat di `AGENTS.md §8`; perubahan requirement diusulkan di `SPEC.md §12`

### Prompt pembuka sesi baru
```
Baca AGENTS.md, PROGRESS.md, SPEC.md, dan verification/acceptance-criteria.md.
Lanjutkan checkpoint sesuai PROGRESS.md. Jelaskan rencanamu dulu sebelum menulis kode.
```

### Prompt menutup sesi
```
Sebelum berhenti: jalankan verifikasi checkpoint ini, laporkan pakai format AGENTS.md §7,
lalu perbarui PROGRESS.md dan matriks keterlacakan.
```

### Prompt untuk memulai project baru (tahap interview)
```
Saya ingin membangun aplikasi pendataan PTK yayasan.
Sebelum menulis apa pun, wawancarai saya secara bertahap (maks 5 pertanyaan per putaran)
sampai requirement jelas: tujuan, pengguna, data, alur, hak akses, non-fungsional, tech stack.
Tanyakan juga: kasus gagal tiap fitur, lingkup data per peran/unit, data awal yang
harus diimpor, dan cara deploy. Jika saya belum menentukan tech stack, bandingkan
2–3 pilihan (mis. Laravel / Node.js / Python / Apps Script) beserta kelebihan-kekurangannya
untuk kebutuhan ini, lalu minta saya memilih. Catat setiap jawaban saya sebagai keputusan.
Setelah selesai, susun SPEC.md mengikuti template yang ada (beri ID REQ-xx),
lalu turunkan acceptance-criteria.md. Jangan isi bagian yang belum saya jawab —
masukkan ke §10 Pertanyaan Terbuka.
```

---

## ⚙️ Persyaratan

- PHP 8.3+ (Laravel 13 mensyaratkan ^8.3) dengan ekstensi intl, mbstring, pdo_sqlite, zip, gd/fileinfo
- Composer 2, Node.js + npm
- SQLite (berkas `database/database.sqlite`, dibuat otomatis — tidak perlu server database)
- Laravel 13 dan Filament v5 (terpasang: Laravel 13.34, Filament 5.9, Livewire 4, Pest 4)

## 🚀 Instalasi

```bash
git clone [url-repo]
cd ptk-yhkb
composer install && npm install
cp .env.example .env       # APP_LOCALE=id, APP_TIMEZONE=Asia/Jakarta,
                           # ADMIN_NAME/ADMIN_EMAIL/ADMIN_PASSWORD, PTK_ADMIN_WHATSAPP
php artisan key:generate
php artisan migrate --seed # sekolah, data wilayah (CSV di database/data/), akun admin pertama
                           # ADMIN_EMAIL & ADMIN_PASSWORD wajib terisi, seeder gagal bila kosong
npm run build              # aset form publik (Tailwind, Alpine, flatpickr, font lokal)
composer run dev           # server + Vite + queue worker
```

Form publik: `http://localhost:8000` · Dashboard admin: `http://localhost:8000/admin`

## 🧪 Menjalankan Test

```bash
php artisan test
./vendor/bin/pint --test
```

## 🌐 Deployment (produksi)

**Live:** https://formptk.karyabangsa.sch.id (admin: `/admin`) — VPS Hostinger **karyabangsa**,
di balik Cloudflare Tunnel. Detail keputusan di `SPEC.md §8A.2`.

| Bagian | Nilai |
|---|---|
| Direktori | `/var/www/formptk.karyabangsa.sch.id` (pemilik `sukardi:www-data`) |
| Database | SQLite `database/database.sqlite` |
| Berkas privat | `storage/app/private/` (Foto KTP, SK, hasil ekspor) |
| Queue worker | systemd `formptk-worker.service` (log `/var/log/formptk-worker.log`) — **wajib** untuk ekspor Excel |
| nginx | `/etc/nginx/sites-available/formptk.karyabangsa.sch.id` (`client_max_body_size 5m`) |
| Tunnel | ingress di `/etc/cloudflared/config.yml` + DNS `CNAME formptk → <tunnel>.cfargotunnel.com` |
| Rahasia | `.env` di server (`ADMIN_*`, `PTK_ADMIN_WHATSAPP`, `APP_KEY`) — tidak ada di repo |

### Update rilis

Jalankan sebagai `sukardi` (bukan root), **backup dulu**:

```bash
ssh root@karyabangsa
cd /var/www/formptk.karyabangsa.sch.id
sudo -u sukardi cp database/database.sqlite ~/backup-formptk-$(date +%Y%m%d-%H%M).sqlite
sudo -u sukardi git pull origin main
sudo -u sukardi composer install --no-dev --optimize-autoloader --no-interaction
sudo -u sukardi npm ci && sudo -u sukardi npm run build
sudo -u sukardi php artisan migrate --force
sudo -u sukardi php artisan optimize && sudo -u sukardi php artisan filament:optimize
systemctl restart formptk-worker
```

**Rollback:** `git checkout <commit-sebelumnya>`, ulangi langkah build, dan kembalikan salinan
`database.sqlite` bila migrasi sudah mengubah data.

Catatan: aplikasi mempercayai header `X-Forwarded-*` hanya dari loopback (`bootstrap/app.php`),
karena cloudflared meneruskan dari `localhost` — tanpa itu URL keluar `http://` dan batas Cek Status
berlaku untuk semua pengunjung sekaligus.
## 🔐 Dashboard Admin

`/admin` — login dengan `ADMIN_EMAIL` / `ADMIN_PASSWORD` dari `.env` (dibuat oleh seeder).
Menu **Pengajuan PTK**: statistik per status, cari (nama / NIK / Submit ID), filter (status / sekolah /
jenis PTK), detail + berkas, **Ubah Status** (Perlu Revisi wajib catatan), riwayat status, **Ekspor Excel**
(mengikuti filter & pencarian; tautan unduh muncul di ikon lonceng notifikasi).

---

## 👤 Kontak

- **Pemilik:** Sukardi
- **Status project:** Produksi (CP6) — https://formptk.karyabangsa.sch.id; lihat `PROGRESS.md`
