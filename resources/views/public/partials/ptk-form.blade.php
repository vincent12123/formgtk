{{--
    Form PTK 4 tab (SPEC.md §5.1) — dipakai form pengajuan baru dan form perbaikan (CP4).
    Variabel: $values (nilai awal), $action (URL kirim atau null), $method, $submitLabel,
    $schools, $existingFiles (berkas lama yang boleh tidak diganti).
--}}
@php
    $steps = [
        'identitas' => ['Identitas', 'Data pribadi'],
        'domisili' => ['Domisili', 'Alamat & wilayah'],
        'kepegawaian' => ['Kepegawaian', 'Status & pengangkatan'],
        'penugasan' => ['Penugasan', 'Sekolah & berkas'],
    ];
    $invalid = fn (string $field) => "errors.{$field} ? 'true' : 'false'";
@endphp

<div
    x-data="ptkForm(@js([
        'values' => $values,
        'errors' => $errors->toArray(),
        'jabatan' => config('ptk.jabatan'),
        'wilayahUrl' => url('/wilayah'),
        // Versi cache daftar wilayah: berubah bila urutan prioritas diubah
        'wilayahVersion' => substr(md5(json_encode(config('ptk.wilayah_prioritas'))), 0, 8),
        'action' => $action,
        'existingFiles' => $existingFiles ?? [],
    ]))"
    class="scroll-mt-4"
>
    {{-- Navigasi langkah --}}
    <nav aria-label="Langkah pengisian" class="mb-6">
        <ol class="grid grid-cols-2 gap-2 md:grid-cols-4 md:gap-3">
            @foreach ($steps as $tab => [$judul, $keterangan])
                <li>
                    <button
                        type="button"
                        data-step="{{ $tab }}"
                        @click="goTo('{{ $tab }}')"
                        :aria-current="current === '{{ $tab }}' ? 'step' : null"
                        class="flex w-full items-center gap-3 rounded-md border px-3 py-2.5 text-left transition"
                        :class="current === '{{ $tab }}'
                            ? 'border-primary bg-primary text-white'
                            : 'border-border bg-surface text-text hover:bg-primary-light'"
                    >
                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 text-sm font-bold"
                            :class="current === '{{ $tab }}'
                                ? 'border-white/40 bg-white/15'
                                : (isDone('{{ $tab }}') ? 'border-accent text-primary' : 'border-border text-text-muted')"
                        >{{ $loop->iteration }}</span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-semibold">{{ $judul }}</span>
                            <span class="hidden truncate text-[13px] opacity-80 sm:block">{{ $keterangan }}</span>
                        </span>
                    </button>
                </li>
            @endforeach
        </ol>
    </nav>

    <form
        method="POST"
        action="{{ $action }}"
        enctype="multipart/form-data"
        novalidate
        class="card p-4 md:p-8"
        @submit="submit($event)"
        @input="revalidate($event.target.name)"
        @change="revalidate($event.target.name)"
    >
        @csrf
        @isset($method)
            @method($method)
        @endisset

        @if ($errors->any())
            <div class="alert-danger mb-6" role="alert">
                Data belum dapat disimpan. Periksa kembali isian yang ditandai merah.
                @if (empty($existingFiles))
                    Foto KTP dan SK Penugasan perlu dipilih ulang.
                @endif
            </div>
        @endif

        {{-- ============================================================ TAB 1: IDENTITAS --}}
        <section data-tab="identitas" x-show="current === 'identitas'" aria-labelledby="judul-identitas">
            <x-public.tab-heading id="judul-identitas" step="1" title="Identitas PTK" subtitle="Isi data pokok sesuai dokumen resmi." />

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <x-field name="nik" label="NIK" hint="16 digit angka sesuai KTP.">
                    <input id="nik" name="nik" type="text" inputmode="numeric" maxlength="16" autocomplete="off"
                           class="field-input" x-model.trim="form.nik" :aria-invalid="{{ $invalid('nik') }}"
                           aria-describedby="nik_hint nik_error">
                </x-field>

                <x-field name="nama" label="Nama Lengkap">
                    <input id="nama" name="nama" type="text" autocomplete="name"
                           class="field-input" x-model="form.nama" :aria-invalid="{{ $invalid('nama') }}" aria-describedby="nama_error">
                </x-field>

                <x-field name="tempat_lahir" label="Tempat Lahir">
                    <input id="tempat_lahir" name="tempat_lahir" type="text"
                           class="field-input" x-model="form.tempat_lahir" :aria-invalid="{{ $invalid('tempat_lahir') }}" aria-describedby="tempat_lahir_error">
                </x-field>

                <x-field name="tanggal_lahir" label="Tanggal Lahir" for="tanggal_lahir_tampil">
                    <input id="tanggal_lahir" name="tanggal_lahir" type="text" data-datepicker class="field-input">
                </x-field>

                <x-field name="jenis_kelamin" label="Jenis Kelamin" for="jenis_kelamin_0">
                    <div class="choice-group" :aria-invalid="{{ $invalid('jenis_kelamin') }}">
                        @foreach (config('ptk.jenis_kelamin') as $opsi)
                            <label class="choice">
                                <input id="jenis_kelamin_{{ $loop->index }}" type="radio" name="jenis_kelamin" value="{{ $opsi }}" x-model="form.jenis_kelamin">
                                {{ $opsi }}
                            </label>
                        @endforeach
                    </div>
                </x-field>

                <x-field name="nama_ibu_kandung" label="Nama Ibu Kandung">
                    <input id="nama_ibu_kandung" name="nama_ibu_kandung" type="text"
                           class="field-input" x-model="form.nama_ibu_kandung" :aria-invalid="{{ $invalid('nama_ibu_kandung') }}" aria-describedby="nama_ibu_kandung_error">
                </x-field>

                <x-field name="agama" label="Agama">
                    <x-public.select name="agama" :options="config('ptk.agama')" placeholder="Pilih agama" />
                </x-field>

                <x-field name="kebutuhan_khusus" label="Kebutuhan Khusus">
                    <x-public.select name="kebutuhan_khusus" :options="config('ptk.kebutuhan_khusus')" placeholder="Pilih kebutuhan khusus" />
                </x-field>

                <x-field name="status_perkawinan" label="Status Perkawinan" for="status_perkawinan_0">
                    <div class="choice-group" :aria-invalid="{{ $invalid('status_perkawinan') }}">
                        @foreach (config('ptk.status_perkawinan') as $opsi)
                            <label class="choice">
                                <input id="status_perkawinan_{{ $loop->index }}" type="radio" name="status_perkawinan" value="{{ $opsi }}" x-model="form.status_perkawinan">
                                {{ $opsi }}
                            </label>
                        @endforeach
                    </div>
                </x-field>

                <x-field name="email" label="Email">
                    <input id="email" name="email" type="email" autocomplete="email" placeholder="nama@email.com"
                           class="field-input" x-model.trim="form.email" :aria-invalid="{{ $invalid('email') }}" aria-describedby="email_error">
                </x-field>

                <x-field name="foto_ktp" label="Foto KTP" class="md:col-span-2" :required="empty($existingFiles['foto_ktp'])"
                         :hint="empty($existingFiles['foto_ktp']) ? 'Format JPG atau PNG, maksimal 1 MB.' : 'Format JPG atau PNG, maksimal 1 MB. Kosongkan bila tidak diganti.'">
                    <input id="foto_ktp" name="foto_ktp" type="file" accept="image/jpeg,image/png" x-ref="foto_ktp"
                           class="field-input file:mr-3 file:rounded-sm file:border-0 file:bg-primary-light file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-primary"
                           :aria-invalid="{{ $invalid('foto_ktp') }}" aria-describedby="foto_ktp_hint foto_ktp_error">
                </x-field>
            </div>

            <x-public.tab-actions next="Lanjut ke Domisili" />
        </section>

        {{-- ============================================================ TAB 2: DOMISILI --}}
        <section data-tab="domisili" x-show="current === 'domisili'" x-cloak aria-labelledby="judul-domisili">
            <x-public.tab-heading id="judul-domisili" step="2" title="Domisili" subtitle="Pilih wilayah tempat tinggal secara berurutan." />

            <div x-show="wilayahGagal" x-cloak class="alert-danger mb-5 flex flex-wrap items-center justify-between gap-3" role="alert">
                <span>Daftar wilayah gagal dimuat. Periksa koneksi lalu coba lagi.</span>
                <button type="button" class="btn btn-secondary" @click="retryWilayah()">Coba lagi</button>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                @foreach ([
                    'provinsi' => ['provinsi_kode', 'Provinsi', 'Pilih provinsi'],
                    'kabupaten' => ['kabupaten_kode', 'Kabupaten/Kota', 'Pilih kabupaten/kota'],
                    'kecamatan' => ['kecamatan_kode', 'Kecamatan', 'Pilih kecamatan'],
                    'desa' => ['desa_kode', 'Desa/Kelurahan', 'Pilih desa/kelurahan'],
                ] as $level => [$field, $label, $placeholder])
                    <x-field :name="$field" :label="$label">
                        <select id="{{ $field }}" name="{{ $field }}" class="field-input"
                                x-model="form.{{ $field }}" @change="onWilayahChange('{{ $level }}')"
                                :disabled="wilayah.{{ $level }}.length === 0"
                                :aria-invalid="{{ $invalid($field) }}" aria-describedby="{{ $field }}_error">
                            <option value=""
                                    x-text="wilayahLoading === '{{ $level }}' ? 'Memuat…' : '{{ $placeholder }}'"></option>
                            <template x-for="item in wilayah.{{ $level }}" :key="item.id">
                                <option :value="item.id" x-text="item.name" :selected="item.id === form.{{ $field }}"></option>
                            </template>
                        </select>
                    </x-field>
                @endforeach

                <x-field name="alamat" label="Alamat" class="md:col-span-2" hint="Nama jalan, nomor rumah, RT/RW.">
                    <textarea id="alamat" name="alamat" rows="3" class="field-input" x-model="form.alamat"
                              :aria-invalid="{{ $invalid('alamat') }}" aria-describedby="alamat_hint alamat_error"></textarea>
                </x-field>

                <x-field name="kode_pos" label="Kode Pos" :required="false">
                    <input id="kode_pos" name="kode_pos" type="text" inputmode="numeric" maxlength="10"
                           class="field-input" x-model.trim="form.kode_pos" :aria-invalid="{{ $invalid('kode_pos') }}" aria-describedby="kode_pos_error">
                </x-field>
            </div>

            <x-public.tab-actions next="Lanjut ke Kepegawaian" back />
        </section>

        {{-- ============================================================ TAB 3: KEPEGAWAIAN --}}
        <section data-tab="kepegawaian" x-show="current === 'kepegawaian'" x-cloak aria-labelledby="judul-kepegawaian">
            <x-public.tab-heading id="judul-kepegawaian" step="3" title="Kepegawaian" subtitle="Status kepegawaian dan riwayat pengangkatan." />

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <x-field name="jenis_ptk" label="Jenis PTK">
                    <x-public.select name="jenis_ptk" :options="array_keys(config('ptk.jabatan'))" placeholder="Pilih jenis PTK" />
                </x-field>

                <x-field name="jabatan_ptk" label="Jabatan PTK" hint="Daftar jabatan mengikuti Jenis PTK.">
                    <select id="jabatan_ptk" name="jabatan_ptk" class="field-input" x-model="form.jabatan_ptk"
                            :disabled="jabatanOptions.length === 0"
                            :aria-invalid="{{ $invalid('jabatan_ptk') }}" aria-describedby="jabatan_ptk_hint jabatan_ptk_error">
                        <option value="" x-text="form.jenis_ptk ? 'Pilih jabatan PTK' : 'Pilih Jenis PTK terlebih dahulu'"></option>
                        <template x-for="jabatan in jabatanOptions" :key="jabatan">
                            <option :value="jabatan" x-text="jabatan" :selected="jabatan === form.jabatan_ptk"></option>
                        </template>
                    </select>
                </x-field>

                <x-field name="status_kepegawaian" label="Status Kepegawaian">
                    <x-public.select name="status_kepegawaian" :options="config('ptk.status_kepegawaian')" placeholder="Pilih status kepegawaian" />
                </x-field>

                <x-field name="lembaga_pengangkat" label="Lembaga Pengangkat">
                    <x-public.select name="lembaga_pengangkat" :options="config('ptk.lembaga_pengangkat')" placeholder="Pilih lembaga pengangkat" />
                </x-field>

                <x-field name="sk_pengangkatan" label="Nomor SK Pengangkatan">
                    <input id="sk_pengangkatan" name="sk_pengangkatan" type="text"
                           class="field-input" x-model="form.sk_pengangkatan" :aria-invalid="{{ $invalid('sk_pengangkatan') }}" aria-describedby="sk_pengangkatan_error">
                </x-field>

                <x-field name="tmt_pengangkatan" label="TMT Pengangkatan" for="tmt_pengangkatan_tampil">
                    <input id="tmt_pengangkatan" name="tmt_pengangkatan" type="text" data-datepicker class="field-input">
                </x-field>

                <x-field name="sumber_gaji" label="Sumber Gaji">
                    <x-public.select name="sumber_gaji" :options="config('ptk.sumber_gaji')" placeholder="Pilih sumber gaji" />
                </x-field>

                <template x-if="form.jenis_ptk === 'Kepala Sekolah'">
                    <x-field name="lisensi_kepsek" label="Sudah Memiliki Lisensi Kepala Sekolah?" for="lisensi_kepsek_0">
                        <div class="choice-group" :aria-invalid="{{ $invalid('lisensi_kepsek') }}">
                            @foreach (config('ptk.lisensi_kepsek') as $opsi)
                                <label class="choice">
                                    <input id="lisensi_kepsek_{{ $loop->index }}" type="radio" name="lisensi_kepsek" value="{{ $opsi }}" x-model="form.lisensi_kepsek">
                                    {{ $opsi }}
                                </label>
                            @endforeach
                        </div>
                    </x-field>
                </template>
            </div>

            <x-public.tab-actions next="Lanjut ke Penugasan" back />
        </section>

        {{-- ============================================================ TAB 4: PENUGASAN --}}
        <section data-tab="penugasan" x-show="current === 'penugasan'" x-cloak aria-labelledby="judul-penugasan">
            <x-public.tab-heading id="judul-penugasan" step="4" title="Penugasan" subtitle="Sekolah tempat bertugas dan dokumen penugasan." />

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                <x-field name="school_npsn" label="Sekolah" class="md:col-span-2">
                    <select id="school_npsn" name="school_npsn" class="field-input" x-model="form.school_npsn"
                            :aria-invalid="{{ $invalid('school_npsn') }}" aria-describedby="school_npsn_error">
                        <option value="">Pilih sekolah</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->npsn }}">({{ $school->npsn }}) {{ $school->name }}</option>
                        @endforeach
                    </select>
                </x-field>

                <x-field name="nomor_surat_tugas" label="Nomor Surat Tugas">
                    <input id="nomor_surat_tugas" name="nomor_surat_tugas" type="text"
                           class="field-input" x-model="form.nomor_surat_tugas" :aria-invalid="{{ $invalid('nomor_surat_tugas') }}" aria-describedby="nomor_surat_tugas_error">
                </x-field>

                <x-field name="tanggal_surat_tugas" label="Tanggal Surat Tugas" for="tanggal_surat_tugas_tampil">
                    <input id="tanggal_surat_tugas" name="tanggal_surat_tugas" type="text" data-datepicker class="field-input">
                </x-field>

                <x-field name="tmt_tugas" label="TMT Tugas" for="tmt_tugas_tampil">
                    <input id="tmt_tugas" name="tmt_tugas" type="text" data-datepicker class="field-input">
                </x-field>

                <x-field name="sk_penugasan" label="SK Penugasan" :required="empty($existingFiles['sk_penugasan'])"
                         :hint="empty($existingFiles['sk_penugasan']) ? 'Format PDF, maksimal 1 MB.' : 'Format PDF, maksimal 1 MB. Kosongkan bila tidak diganti.'">
                    <input id="sk_penugasan" name="sk_penugasan" type="file" accept="application/pdf" x-ref="sk_penugasan"
                           class="field-input file:mr-3 file:rounded-sm file:border-0 file:bg-primary-light file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-primary"
                           :aria-invalid="{{ $invalid('sk_penugasan') }}" aria-describedby="sk_penugasan_hint sk_penugasan_error">
                </x-field>

                <x-field name="persetujuan" label="Pernyataan" class="md:col-span-2" for="persetujuan">
                    <div class="choice-group" :aria-invalid="{{ $invalid('persetujuan') }}">
                        <label class="choice items-start">
                            <input id="persetujuan" type="checkbox" name="persetujuan" value="1" class="mt-1" x-model="form.persetujuan">
                            <span>Saya menyatakan data yang diisi sudah benar dan dapat dipertanggungjawabkan.</span>
                        </label>
                    </div>
                </x-field>
            </div>

            <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-6">
                <button type="button" class="btn btn-secondary" @click="prev()">Kembali</button>
                <button type="submit" class="btn btn-primary" :disabled="mengirim">
                    <span x-text="mengirim ? 'Mengirim…' : '{{ $submitLabel ?? 'Kirim Data PTK' }}'">{{ $submitLabel ?? 'Kirim Data PTK' }}</span>
                </button>
            </div>
        </section>
    </form>
</div>
