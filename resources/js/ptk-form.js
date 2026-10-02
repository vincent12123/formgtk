import { pasangDatepicker } from './datepicker';

/**
 * Form pengajuan PTK 4 tab (SPEC.md §5.1).
 * Validasi di sini hanya untuk kenyamanan — server tetap memvalidasi ulang semuanya.
 *
 * config = { values, errors, jabatan, wilayahUrl, wilayahVersion, action, existingFiles }
 */
const TABS = ['identitas', 'domisili', 'kepegawaian', 'penugasan'];

// Field → tab tempatnya tampil (dipakai untuk membuka tab yang berisi error dari server)
const FIELD_TAB = {
    nik: 'identitas', nama: 'identitas', tempat_lahir: 'identitas', tanggal_lahir: 'identitas',
    jenis_kelamin: 'identitas', nama_ibu_kandung: 'identitas', agama: 'identitas',
    kebutuhan_khusus: 'identitas', status_perkawinan: 'identitas', email: 'identitas', foto_ktp: 'identitas',
    provinsi_kode: 'domisili', kabupaten_kode: 'domisili', kecamatan_kode: 'domisili', desa_kode: 'domisili',
    alamat: 'domisili', kode_pos: 'domisili',
    jenis_ptk: 'kepegawaian', jabatan_ptk: 'kepegawaian', status_kepegawaian: 'kepegawaian',
    lembaga_pengangkat: 'kepegawaian', sk_pengangkatan: 'kepegawaian', tmt_pengangkatan: 'kepegawaian',
    sumber_gaji: 'kepegawaian', lisensi_kepsek: 'kepegawaian',
    school_npsn: 'penugasan', nomor_surat_tugas: 'penugasan', tanggal_surat_tugas: 'penugasan',
    tmt_tugas: 'penugasan', sk_penugasan: 'penugasan', persetujuan: 'penugasan',
};

const MAX_FILE_BYTES = 1024 * 1024;

// Jenjang wilayah: nama tingkat → field kode, segmen URL, tingkat berikutnya
const WILAYAH = {
    provinsi: { field: 'provinsi_kode', next: 'kabupaten' },
    kabupaten: { field: 'kabupaten_kode', next: 'kecamatan' },
    kecamatan: { field: 'kecamatan_kode', next: 'desa' },
    desa: { field: 'desa_kode', next: null },
};

export default (config) => ({
    tabs: TABS,
    current: 'identitas',
    form: { ...config.values },
    errors: {},
    jabatanMap: config.jabatan,
    wilayah: { provinsi: [], kabupaten: [], kecamatan: [], desa: [] },
    wilayahLoading: null,
    wilayahGagal: null,
    mengirim: false,

    init() {
        // Error dari server: ambil pesan pertama tiap field, buka tab pertama yang bermasalah
        for (const [field, messages] of Object.entries(config.errors || {})) {
            this.errors[field] = Array.isArray(messages) ? messages[0] : messages;
        }
        const firstError = TABS.find((tab) => Object.keys(this.errors).some((f) => FIELD_TAB[f] === tab));
        if (firstError) this.current = firstError;

        this.$watch('form.jenis_ptk', (jenis) => this.onJenisChange(jenis));
        this.initDatepickers();
        this.loadWilayahAwal();
    },

    // ---------------------------------------------------------------- Tab

    tabIndex(tab) {
        return TABS.indexOf(tab);
    },

    isDone(tab) {
        return this.tabIndex(tab) < this.tabIndex(this.current);
    },

    // Pindah tab: semua tab sebelum tujuan harus valid (REQ-01)
    goTo(target) {
        for (const tab of TABS.slice(0, this.tabIndex(target))) {
            if (!this.validateTab(tab)) {
                this.show(tab);
                return;
            }
        }
        this.show(target);
    },

    next() {
        if (!this.validateTab(this.current)) {
            this.focusFirstError();
            return;
        }
        this.show(TABS[this.tabIndex(this.current) + 1]);
    },

    prev() {
        this.show(TABS[this.tabIndex(this.current) - 1]);
    },

    show(tab) {
        this.current = tab;
        this.$nextTick(() => {
            this.$root.scrollIntoView({ behavior: 'smooth', block: 'start' });
            this.focusFirstError();
        });
    },

    focusFirstError() {
        this.$nextTick(() => {
            const el = this.$root.querySelector(`[data-tab="${this.current}"] [aria-invalid="true"]`);
            el?.focus({ preventScroll: true });
        });
    },

    // ---------------------------------------------------------------- Validasi

    rules() {
        const f = this.form;
        const file = (name) => this.$refs[name]?.files?.[0] ?? null;
        const filled = (v) => v !== undefined && v !== null && String(v).trim() !== '';
        const wajib = (label) => `${label} wajib diisi.`;
        const pilih = (label) => `${label} wajib dipilih.`;
        const jabatanSah = (this.jabatanMap[f.jenis_ptk] || []).includes(f.jabatan_ptk);

        const fileError = (name, label, types, typeLabel, existing) => {
            const berkas = file(name);
            if (!berkas) return existing ? null : `${label} wajib diunggah.`;
            if (!types.includes(berkas.type)) return `${label} harus berupa berkas ${typeLabel}.`;
            if (berkas.size > MAX_FILE_BYTES) return `Ukuran ${label} maksimal 1 MB.`;
            return null;
        };

        return {
            identitas: {
                nik: !filled(f.nik) ? wajib('NIK') : !/^\d{16}$/.test(f.nik) ? 'NIK harus tepat 16 digit angka.' : null,
                nama: !filled(f.nama) ? wajib('Nama') : null,
                tempat_lahir: !filled(f.tempat_lahir) ? wajib('Tempat Lahir') : null,
                tanggal_lahir: !filled(f.tanggal_lahir) ? wajib('Tanggal Lahir') : null,
                jenis_kelamin: !filled(f.jenis_kelamin) ? pilih('Jenis Kelamin') : null,
                nama_ibu_kandung: !filled(f.nama_ibu_kandung) ? wajib('Nama Ibu Kandung') : null,
                agama: !filled(f.agama) ? pilih('Agama') : null,
                kebutuhan_khusus: !filled(f.kebutuhan_khusus) ? pilih('Kebutuhan Khusus') : null,
                status_perkawinan: !filled(f.status_perkawinan) ? pilih('Status Perkawinan') : null,
                email: !filled(f.email)
                    ? wajib('Email')
                    : !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(f.email) ? 'Email harus berupa alamat email yang valid.' : null,
                foto_ktp: fileError('foto_ktp', 'Foto KTP', ['image/jpeg', 'image/png'], 'JPG atau PNG', config.existingFiles?.foto_ktp),
            },
            domisili: {
                provinsi_kode: !filled(f.provinsi_kode) ? pilih('Provinsi') : null,
                kabupaten_kode: !filled(f.kabupaten_kode) ? pilih('Kabupaten/Kota') : null,
                kecamatan_kode: !filled(f.kecamatan_kode) ? pilih('Kecamatan') : null,
                desa_kode: !filled(f.desa_kode) ? pilih('Desa/Kelurahan') : null,
                alamat: !filled(f.alamat) ? wajib('Alamat') : null,
            },
            kepegawaian: {
                jenis_ptk: !filled(f.jenis_ptk) ? pilih('Jenis PTK') : null,
                jabatan_ptk: !filled(f.jabatan_ptk)
                    ? pilih('Jabatan PTK')
                    : !jabatanSah ? 'Jabatan PTK tidak sesuai dengan Jenis PTK.' : null,
                status_kepegawaian: !filled(f.status_kepegawaian) ? pilih('Status Kepegawaian') : null,
                lembaga_pengangkat: !filled(f.lembaga_pengangkat) ? pilih('Lembaga Pengangkat') : null,
                sk_pengangkatan: !filled(f.sk_pengangkatan) ? wajib('SK Pengangkatan') : null,
                tmt_pengangkatan: !filled(f.tmt_pengangkatan) ? wajib('TMT Pengangkatan') : null,
                sumber_gaji: !filled(f.sumber_gaji) ? pilih('Sumber Gaji') : null,
                lisensi_kepsek: f.jenis_ptk === 'Kepala Sekolah' && !filled(f.lisensi_kepsek)
                    ? pilih('Lisensi Kepala Sekolah') : null,
            },
            penugasan: {
                school_npsn: !filled(f.school_npsn) ? pilih('Sekolah') : null,
                nomor_surat_tugas: !filled(f.nomor_surat_tugas) ? wajib('Nomor Surat Tugas') : null,
                tanggal_surat_tugas: !filled(f.tanggal_surat_tugas) ? wajib('Tanggal Surat Tugas') : null,
                tmt_tugas: !filled(f.tmt_tugas) ? wajib('TMT Tugas') : null,
                sk_penugasan: fileError('sk_penugasan', 'SK Penugasan', ['application/pdf'], 'PDF', config.existingFiles?.sk_penugasan),
                persetujuan: !f.persetujuan ? 'Bapak/Ibu wajib menyetujui pernyataan.' : null,
            },
        };
    },

    validateTab(tab) {
        const result = this.rules()[tab];
        let valid = true;
        for (const [field, message] of Object.entries(result)) {
            if (message) {
                this.errors[field] = message;
                valid = false;
            } else {
                delete this.errors[field];
            }
        }
        return valid;
    },

    // Validasi ulang satu field saat berubah, hanya bila sebelumnya sudah ditandai error
    revalidate(field) {
        if (!this.errors[field]) return;
        const message = this.rules()[FIELD_TAB[field]][field];
        if (message) this.errors[field] = message;
        else delete this.errors[field];
    },

    submit(event) {
        for (const tab of TABS) {
            if (!this.validateTab(tab)) {
                event.preventDefault();
                this.show(tab);
                return;
            }
        }

        this.mengirim = true;
    },

    // ---------------------------------------------------------------- Jenis → Jabatan PTK (REQ-02)

    get jabatanOptions() {
        return this.jabatanMap[this.form.jenis_ptk] || [];
    },

    onJenisChange(jenis) {
        // Ganti jenis → jabatan dikosongkan; Kepala Sekolah → jabatan otomatis
        this.form.jabatan_ptk = jenis === 'Kepala Sekolah' ? 'Kepala Sekolah' : '';
        if (jenis !== 'Kepala Sekolah') this.form.lisensi_kepsek = '';
        delete this.errors.jabatan_ptk;
        delete this.errors.lisensi_kepsek;
    },

    // ---------------------------------------------------------------- Wilayah (REQ-03)

    async loadWilayahAwal() {
        if (!(await this.fetchWilayah('provinsi'))) return;

        // Mengisi ulang pilihan berjenjang (old input / form perbaikan)
        for (const level of ['kabupaten', 'kecamatan', 'desa']) {
            const parent = Object.keys(WILAYAH).find((k) => WILAYAH[k].next === level);
            if (!this.form[WILAYAH[parent].field]) return;
            if (!(await this.fetchWilayah(level, this.form[WILAYAH[parent].field]))) return;
        }
    },

    async fetchWilayah(level, parentId = null) {
        const path = parentId ? `${level}/${encodeURIComponent(parentId)}` : level;
        const url = `${config.wilayahUrl}/${path}?v=${config.wilayahVersion}`;
        this.wilayahLoading = level;
        this.wilayahGagal = null;

        try {
            const response = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            this.wilayah[level] = await response.json();
            return true;
        } catch (error) {
            this.wilayah[level] = [];
            this.wilayahGagal = { level, parentId };
            return false;
        } finally {
            this.wilayahLoading = null;
        }
    },

    retryWilayah() {
        const { level, parentId } = this.wilayahGagal;
        if (level === 'provinsi') this.loadWilayahAwal();
        else this.fetchWilayah(level, parentId);
    },

    // Pilihan berubah → kosongkan semua tingkat di bawahnya lalu muat tingkat berikutnya
    onWilayahChange(level) {
        let next = WILAYAH[level].next;
        while (next) {
            this.form[WILAYAH[next].field] = '';
            this.wilayah[next] = [];
            next = WILAYAH[next].next;
        }
        this.revalidate(WILAYAH[level].field);

        const value = this.form[WILAYAH[level].field];
        if (value && WILAYAH[level].next) this.fetchWilayah(WILAYAH[level].next, value);
    },

    wilayahNama(level) {
        return this.wilayah[level].find((w) => w.id === this.form[WILAYAH[level].field])?.name ?? '';
    },

    // ---------------------------------------------------------------- Tanggal (REQ-14)

    initDatepickers() {
        this.$root.querySelectorAll('[data-datepicker]').forEach((input) => {
            const field = input.name;
            pasangDatepicker(input, {
                defaultDate: this.form[field],
                onChange: (value) => {
                    this.form[field] = value;
                    this.revalidate(field);
                },
                onReady: (instance) => {
                    const tandai = (message) => instance.altInput.setAttribute('aria-invalid', message ? 'true' : 'false');
                    tandai(this.errors[field]);
                    this.$watch(`errors.${field}`, tandai);
                },
            });
        });
    },
});
