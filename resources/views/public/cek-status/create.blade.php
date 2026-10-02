<x-layouts.public title="Cek Status">
    <section class="mx-auto max-w-lg">
        <a href="{{ route('beranda') }}" class="btn-ghost -ml-1 inline-flex text-sm font-semibold">&larr; Beranda</a>
        <h1 class="mt-2 text-2xl font-bold md:text-[32px]">Cek Status Pengajuan</h1>
        <p class="mt-2 text-text-muted">Masukkan Submit ID dan tanggal lahir yang Bapak/Ibu isi saat mengirim data.</p>

        <form method="POST" action="{{ route('cek-status.store') }}" class="card mt-6 space-y-5 p-5 md:p-6" novalidate>
            @csrf

            @php($pesan = $pesanUmum ?? session('pesanUmum'))
            @if ($pesan)
                <div class="alert-danger" role="alert" data-pesan>{{ $pesan }}</div>
            @endif

            <div>
                <label for="submit_id" class="field-label">Submit ID <span class="text-danger" aria-hidden="true">*</span></label>
                <input id="submit_id" name="submit_id" type="text" value="{{ old('submit_id') }}" autocomplete="off"
                       placeholder="PTK-20261002-083045-1234" class="field-input font-mono uppercase"
                       aria-invalid="{{ $errors->has('submit_id') ? 'true' : 'false' }}" aria-describedby="submit_id_error">
                @error('submit_id')
                    <p id="submit_id_error" class="field-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <div x-data="tanggalInput(@js(old('tanggal_lahir', '')))">
                <label for="tanggal_lahir_tampil" class="field-label">Tanggal Lahir <span class="text-danger" aria-hidden="true">*</span></label>
                <input id="tanggal_lahir" name="tanggal_lahir" type="text" x-ref="input" class="field-input"
                       aria-invalid="{{ $errors->has('tanggal_lahir') ? 'true' : 'false' }}">
                @error('tanggal_lahir')
                    <p class="field-error" role="alert">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-full">Cek Sekarang</button>
        </form>
    </section>
</x-layouts.public>
