# Design System — ptk-yhkb

> **Layer 3: Environment (visual).** Aturan tampilan agar setiap halaman yang dibuat AI konsisten.
> AI **hanya boleh** memakai token yang terdaftar di sini.
> Token diterapkan sebagai CSS variables / tema Tailwind 4 (`@theme`) di form publik, dan sebagai warna panel Filament v5.

---

## 1. Identitas Visual

- **Sumber:** palet brand Yayasan Harapan Karya Bangsa (navy & gold)
- **Kesan yang ingin dibangun:** resmi, terpercaya, tenang
- **Hindari:** warna mencolok di luar token, dekorasi berlebihan pada form

---

## 2. Warna

### 2.1 Warna Utama
| Token | Hex | Pemakaian |
|---|---|---|
| `primary` | #14306C | Header, tombol utama, tab aktif, link |
| `primary-dark` | #061540 | Hover tombol utama, latar header |
| `primary-light` | #E7ECF6 | Latar badge info, tab tidak aktif saat hover |
| `accent` | #D4A017 | Aksen garis, ikon langkah, sorotan kecil (bukan latar teks panjang) |

### 2.2 Warna Netral
| Token | Hex | Pemakaian |
|---|---|---|
| `text` | #1F2937 | Teks utama |
| `text-muted` | #6B7280 | Teks sekunder, petunjuk field |
| `border` | #E5E1D6 | Garis input, pemisah |
| `surface` | #FFFFFF | Latar kartu dan input |
| `background` | #FAF7EF | Latar halaman (krem) |

### 2.3 Warna Status
| Token | Hex | Pemakaian |
|---|---|---|
| `success` | #2F7D4F | Status **Valid**, pesan berhasil |
| `warning` | #B7791F | Status **Menunggu** |
| `info` | #14306C | Status **Diproses** (memakai `primary`) |
| `danger` | #B42318 | Status **Perlu Revisi**, pesan error |

**Aturan kontras:** teks di atas warna latar wajib kontras minimal 4.5:1 (WCAG AA). Teks di atas `accent` memakai `primary-dark`, bukan putih.

> Nilai `primary-light`, warna netral, dan warna status diturunkan AI dari palet yayasan agar lengkap; pemilik boleh menggantinya.

---

## 3. Tipografi

| Peran | Font | Fallback |
|---|---|---|
| Heading | Source Serif 4 | Georgia, serif |
| Body & UI | Inter | system-ui, sans-serif |

Font di-host sendiri (paket `@fontsource`) supaya tidak bergantung CDN luar.

| Level | Ukuran (mobile / desktop) | Berat |
|---|---|---|
| H1 | 24px / 32px | 700 |
| H2 | 20px / 24px | 600 |
| H3 | 17px / 18px | 600 |
| Body | 15px | 400 |
| Small | 13px | 400 |

---

## 4. Spasi & Tata Letak

- **Skala spasi:** 4, 8, 12, 16, 24, 32, 48 px
- **Lebar konten maksimal:** 1120px (form publik)
- **Padding samping:** 16px mobile / 32px desktop
- **Breakpoint:** sm 640 · md 768 · lg 1024 · xl 1280
- **Radius sudut:** kecil 8px · sedang 12px · besar 20px
- **Bayangan:** satu level: `0 1px 3px rgba(6,21,64,.08)`

---

## 5. Komponen

### Tombol
| Varian | Tampilan | Kapan dipakai |
|---|---|---|
| Primary | Latar `primary`, teks putih | Lanjut, Kirim, Cek Sekarang (1 per layar) |
| Secondary | Garis `border`, latar `surface`, teks `primary` | Kembali, Salin Submit ID |
| Ghost | Tanpa garis, teks `primary` | Tautan ringan |
| Danger | Latar `danger`, teks putih | Aksi berisiko (admin) |

### Navigasi tab (wizard)
- 4 langkah bernomor; langkah aktif latar `primary` teks putih; langkah selesai bernomor dengan garis `accent`.

### Badge status
- Bentuk pil, latar warna status 12% opasitas, teks warna status penuh. Sama di Cek Status dan dashboard.

### Formulir
- Label selalu di atas input (bukan placeholder saja)
- Pesan error di bawah input, warna `danger`
- Field tanggal memakai date picker dengan tampilan `d F Y` (contoh: 02 Oktober 2026)

---

## 6. Gambar & Ikon

- **Ikon:** Heroicons outline (bawaan Filament), ukuran 20/24px
- **Logo:** belum ada berkas; hanya teks nama yayasan sampai logo diberikan
- **Alt text:** wajib bila ada gambar

---

## 7. Nada Bahasa

- Formal dan sopan, menyapa "Bapak/Ibu".
- Kalimat pendek; pesan error menyebut field dan cara memperbaikinya.

---

## 8. Yang Tidak Boleh

- ❌ Warna hex di luar daftar token
- ❌ Lebih dari 2 keluarga font
- ❌ Teks putih di atas `accent`
- ❌ Font atau ikon dari CDN luar
