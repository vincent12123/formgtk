<x-layouts.public title="Beranda">
    <section class="mx-auto max-w-2xl text-center">
        <h1 class="text-2xl font-bold md:text-[32px]">Selamat Datang, Bapak/Ibu</h1>
        <p class="mt-3 text-text-muted">
            Silakan isi formulir data PTK baru, atau cek status pengajuan yang sudah pernah dikirim.
        </p>
    </section>

    <section class="mx-auto mt-8 grid max-w-2xl gap-4 sm:grid-cols-2">
        <a href="{{ route('pengajuan.create') }}" class="card block p-6 transition hover:border-primary">
            <h2 class="text-xl font-semibold">Isi Form</h2>
            <p class="mt-2 text-sm text-text-muted">Kirim data PTK untuk pertama kali.</p>
            <span class="btn btn-primary mt-5 w-full">Isi Form PTK</span>
        </a>

        @if (Route::has('cek-status.create'))
            <a href="{{ route('cek-status.create') }}" class="card block p-6 transition hover:border-primary">
                <h2 class="text-xl font-semibold">Cek Status</h2>
                <p class="mt-2 text-sm text-text-muted">Lihat status pengajuan dengan Submit ID dan tanggal lahir.</p>
                <span class="btn btn-secondary mt-5 w-full">Cek Status</span>
            </a>
        @else
            <div class="card p-6" aria-disabled="true">
                <h2 class="text-xl font-semibold text-text-muted">Cek Status</h2>
                <p class="mt-2 text-sm text-text-muted">Menu cek status segera tersedia.</p>
            </div>
        @endif
    </section>
</x-layouts.public>
