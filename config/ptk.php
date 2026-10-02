<?php

/*
 * Konfigurasi data tetap PTK (SPEC.md §4.1 & §4.3).
 * Daftar pilihan disalin persis dari form versi terakhir (form-ptk.html).
 * Satu sumber aturan untuk form publik, validasi server, dan dashboard admin.
 */

return [

    // Nomor WhatsApp admin untuk tombol hubungi admin (REQ-06), format internasional tanpa "+"
    'admin_whatsapp' => env('PTK_ADMIN_WHATSAPP'),

    // Wilayah prioritas (unit sekolah di Sintang): terpilih otomatis di form baru dan
    // tampil paling atas di daftarnya (SPEC.md §5.1)
    'wilayah_prioritas' => [
        'provinsi' => '61',   // KALIMANTAN BARAT
        'kabupaten' => '6107', // KABUPATEN SINTANG
    ],

    // Akun admin pertama yang dibuat seeder (SPEC.md §8A.1) — isi di .env
    'admin' => [
        'name' => env('ADMIN_NAME'),
        'email' => env('ADMIN_EMAIL'),
        'password' => env('ADMIN_PASSWORD'),
    ],

    // Jenis PTK => daftar Jabatan PTK (Kepala Sekolah 1, Guru 88, Tenaga Kependidikan 10)
    'jabatan' => [
        'Kepala Sekolah' => [
            'Kepala Sekolah',
        ],
        'Guru' => [
            'Guru Agama Budha',
            'Guru Agama Hindu',
            'Guru Agama Islam',
            'Guru Agama Katolik',
            'Guru Agama Kristen',
            'Guru Agribisnis Perikanan',
            'Guru Agribisnis Tanaman',
            'Guru Agribisnis Ternak',
            'Guru Agriteknologi Pengolahan Hasil Pertanian',
            'Guru Akuntansi Dan Keuangan Lembaga',
            'Guru Animasi',
            'Guru Antropologi',
            'Guru Bahasa Arab',
            'Guru Bahasa Bali',
            'Guru Bahasa Indonesia',
            'Guru Bahasa Inggris',
            'Guru Bahasa Jawa',
            'Guru Bahasa Jepang',
            'Guru Bahasa Jerman',
            'Guru Bahasa Madura',
            'Guru Bahasa Mandarin',
            'Guru Bahasa Perancis',
            'Guru Bahasa Sunda',
            'Guru Bimbingan Konseling',
            'Guru Biologi',
            'Guru Broadcasting Dan Perfilman',
            'Guru Busana',
            'Guru Desain Dan Produksi Kriya',
            'Guru Desain Komunikasi Visual',
            'Guru Desain Pemodelan Dan Informasi Bangunan',
            'Guru Ekonomi',
            'Guru Fisika',
            'Guru Geografi',
            'Guru IPA',
            'Guru IPS',
            'Guru Kecantikan Dan Spa',
            'Guru Kehutanan',
            'Guru Kelas',
            'Guru Kimia',
            'Guru Kimia Analisis',
            'Guru Konstruksi Dan Perawatan Bangunan Sipil',
            'Guru Kuliner',
            'Guru Layanan Kesehatan',
            'Guru Manajemen Perkantoran Dan Layanan Bisnis',
            'Guru Matematika',
            'Guru Muatan Lokal Lainnya',
            'Guru Nautika Kapal Niaga',
            'Guru Nautika Kapal Penangkapan Ikan',
            'Guru Pekerjaan Sosial',
            'Guru Pemasaran',
            'Guru Pendidikan Khusus',
            'Guru Pengembangan Perangkat Lunak Dan Gim',
            'Guru Penjasorkes',
            'Guru Perhotelan',
            'Guru PPKN',
            'Guru Prakarya Dan Kewirausahaan',
            'Guru Sejarah',
            'Guru Seni Budaya',
            'Guru Seni Pertunjukan',
            'Guru Seni Rupa',
            'Guru Sosiologi',
            'Guru Teknik Elektronika',
            'Guru Teknik Energi Terbarukan',
            'Guru Teknik Furnitur',
            'Guru Teknik Geologi Pertambangan',
            'Guru Teknik Geospasial',
            'Guru Teknik Jaringan Komputer Dan Telekomunikasi',
            'Guru Teknik Ketenagalistrikan',
            'Guru Teknik Kimia Industri',
            'Guru Teknik Konstruksi Dan Perumahan',
            'Guru Teknik Konstruksi Kapal',
            'Guru Teknik Laboratorium Medik',
            'Guru Teknik Logistik',
            'Guru Teknik Mesin',
            'Guru Teknik Otomotif',
            'Guru Teknik Pengelasan Dan Fabrikasi Logam',
            'Guru Teknik Perawatan Gedung',
            'Guru Teknik Perminyakan',
            'Guru Teknik Pesawat Udara',
            'Guru Teknik Tekstil',
            'Guru Teknika Kapal Niaga',
            'Guru Teknika Kapal Penangkapan Ikan',
            'Guru Teknologi Farmasi',
            'Guru TIK',
            'Guru Usaha Layanan Pariwisata',
            'Guru Usaha Pertanian Terpadu',
            'Pamong Belajar',
            'Tutor',
        ],
        'Tenaga Kependidikan' => [
            'Instruktur',
            'Instruktur Kejuruan',
            'Laboran',
            'Penjaga Sekolah',
            'Pesuruh/Office Boy',
            'Petugas Keamanan',
            'Pustakawan',
            'Tenaga Administrasi Sekolah',
            'Terapis',
            'Tukang Kebun',
        ],
    ],

    'jenis_kelamin' => [
        'Laki-laki',
        'Perempuan',
    ],

    'agama' => [
        'Islam',
        'Kristen',
        'Katholik',
        'Hindu',
        'Budha',
        'Khonghucu',
        'Lainnya',
        'Kepercayaan kpd Tuhan YME',
    ],

    'kebutuhan_khusus' => [
        'Tidak ada',
        'A - Tuna netra',
        'B - Tuna rungu',
        'C - Tuna grahita ringan',
        'C1 - Tuna grahita sedang',
        'D - Tuna daksa ringan',
        'E - Tuna laras',
        'F - Tuna wicara',
        'H - Hiperaktif',
        'I - Cerdas Istimewa',
        'J - Bakat Istimewa',
        'K - Kesulitan Belajar',
        'N - Narkoba',
        'O - Indigo',
        'P - Down Syndrome',
        'Q - Autis',
        'Lainnya',
    ],

    'status_perkawinan' => [
        'Menikah',
        'Belum Menikah',
        'Janda/Duda',
    ],

    'status_kepegawaian' => [
        'GTY/PTY',
        'Tenaga Pendidikan Sekolah',
        'Guru Tidak Tetap',
    ],

    'lembaga_pengangkat' => [
        'Pemerintah Pusat',
        'Pemerintah Provinsi',
        'Pemerintah Kab/Kota',
        'Ketua Yayasan',
        'Kepala Sekolah',
        'Komite Sekolah',
    ],

    'sumber_gaji' => [
        'Yayasan',
        'Sekolah',
        'Pemerintah',
    ],

    'lisensi_kepsek' => [
        'Sudah',
        'Belum',
    ],

];
