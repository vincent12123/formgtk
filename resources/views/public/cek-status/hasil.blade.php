@php
    use App\Enums\PtkStatus;
    use App\Support\Nik;
    use App\Support\Tanggal;

    $perluRevisi = $submission->status === PtkStatus::PerluRevisi;
@endphp

<x-layouts.public title="Status Pengajuan">
    <section class="mx-auto max-w-2xl">
        <a href="{{ route('cek-status.create') }}" class="btn-ghost -ml-1 inline-flex text-sm font-semibold">&larr; Cek pengajuan lain</a>
        <h1 class="mt-2 text-2xl font-bold md:text-[32px]">Status Pengajuan</h1>

        @if (session('pesanBerhasil'))
            <div class="mt-4 rounded-sm border border-success/30 bg-success/10 px-4 py-3 text-sm text-success" role="status">
                {{ session('pesanBerhasil') }}
            </div>
        @endif

        @if (session('pesanUmum'))
            <div class="alert-danger mt-4" role="alert">{{ session('pesanUmum') }}</div>
        @endif

        <div class="card mt-6 p-5 md:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-border pb-4">
                <div>
                    <p class="text-[13px] font-semibold tracking-wide text-text-muted">SUBMIT ID</p>
                    <p class="font-mono text-lg font-bold text-primary">{{ $submission->submit_id }}</p>
                </div>
                <x-public.status-badge :status="$submission->status" />
            </div>

            <dl class="mt-4 grid grid-cols-1 gap-x-6 gap-y-3 text-[15px] sm:grid-cols-[auto_1fr]">
                <dt class="text-text-muted">Nama</dt>
                <dd class="font-semibold">{{ $submission->nama }}</dd>

                <dt class="text-text-muted">NIK</dt>
                <dd class="font-mono" data-nik>{{ Nik::mask($submission->nik) }}</dd>

                <dt class="text-text-muted">Sekolah</dt>
                <dd>{{ $submission->school->name }}</dd>

                <dt class="text-text-muted">Tanggal Kirim</dt>
                <dd data-tanggal-kirim>{{ Tanggal::format($submission->created_at) }}</dd>
            </dl>

            @if ($perluRevisi)
                <div class="mt-5 rounded-sm border border-danger/30 bg-danger/10 p-4" data-catatan-revisi>
                    <p class="text-sm font-semibold text-danger">Catatan revisi dari admin</p>
                    <p class="mt-1 whitespace-pre-line text-[15px] text-text">{{ $submission->catatan_revisi }}</p>
                </div>

                <a href="{{ route('perbaikan.edit') }}" class="btn btn-primary mt-5 w-full sm:w-auto" data-perbaiki>Perbaiki Data</a>
            @endif
        </div>
    </section>
</x-layouts.public>
