<x-layouts.public title="Data Terkirim">
    <section class="card mx-auto max-w-2xl p-6 md:p-8">
        <span class="inline-flex rounded-full bg-success/10 px-3 py-1 text-[13px] font-semibold text-success">Berhasil dikirim</span>
        <h1 class="mt-3 text-2xl font-bold md:text-[32px]">Terima kasih, Bapak/Ibu {{ $submission->nama }}</h1>
        <p class="mt-2 text-text-muted">
            Data PTK untuk <strong class="text-text">{{ $submission->school->name }}</strong> sudah kami terima dan menunggu validasi admin.
        </p>

        <div class="mt-6 rounded-md border border-accent/60 bg-background p-4 md:p-5"
             x-data="{ disalin: false, salin() {
                navigator.clipboard.writeText(this.$refs.submitId.textContent.trim())
                    .then(() => { this.disalin = true; setTimeout(() => this.disalin = false, 2000) });
             } }">
            <p class="text-[13px] font-semibold tracking-wide text-text-muted">SUBMIT ID</p>
            <div class="mt-1 flex flex-wrap items-center justify-between gap-3">
                <span x-ref="submitId" class="font-mono text-lg font-bold text-primary md:text-xl" data-submit-id>{{ $submission->submit_id }}</span>
                <button type="button" class="btn btn-secondary" @click="salin()" data-salin>
                    <span x-text="disalin ? 'Tersalin' : 'Salin Submit ID'">Salin Submit ID</span>
                </button>
            </div>
            <p class="mt-3 text-sm text-text-muted">
                Simpan Submit ID ini. Bapak/Ibu memerlukannya bersama tanggal lahir untuk membuka menu <strong class="text-text">Cek Status</strong>.
            </p>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
            @if ($whatsappUrl)
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-primary" data-whatsapp>
                    Hubungi Admin via WhatsApp
                </a>
            @endif
            <a href="{{ route('beranda') }}" class="btn btn-ghost">Kembali ke Beranda</a>
        </div>
    </section>
</x-layouts.public>
