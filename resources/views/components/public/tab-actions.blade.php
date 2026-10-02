@props(['next', 'back' => false])

<div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-border pt-6">
    @if ($back)
        <button type="button" class="btn btn-secondary" @click="prev()">Kembali</button>
    @else
        <p class="text-sm text-text-muted">Kolom bertanda <span class="text-danger">*</span> wajib diisi.</p>
    @endif

    <button type="button" class="btn btn-primary" @click="next()">{{ $next }}</button>
</div>
