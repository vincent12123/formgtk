<x-layouts.public title="Perbaiki Data PTK">
    <div class="mb-6">
        <a href="{{ route('cek-status.hasil') }}" class="btn-ghost -ml-1 inline-flex text-sm font-semibold">&larr; Status pengajuan</a>
        <h1 class="mt-2 text-2xl font-bold md:text-[32px]">Perbaiki Data PTK</h1>
        <p class="mt-2 text-text-muted">
            Submit ID <span class="font-mono font-semibold text-primary">{{ $submission->submit_id }}</span> tidak berubah.
            Setelah disimpan, status kembali <strong class="text-text">Menunggu</strong> validasi admin.
        </p>
    </div>

    <div class="mb-6 rounded-sm border border-danger/30 bg-danger/10 p-4" data-catatan-revisi>
        <p class="text-sm font-semibold text-danger">Catatan revisi dari admin</p>
        <p class="mt-1 whitespace-pre-line text-[15px] text-text">{{ $submission->catatan_revisi }}</p>
    </div>

    @include('public.partials.ptk-form', [
        'values' => $values,
        'action' => route('perbaikan.update'),
        'method' => 'PUT',
        'submitLabel' => 'Simpan Perbaikan',
        'schools' => $schools,
        'existingFiles' => [
            'foto_ktp' => filled($submission->foto_ktp_path),
            'sk_penugasan' => filled($submission->sk_penugasan_path),
        ],
    ])
</x-layouts.public>
