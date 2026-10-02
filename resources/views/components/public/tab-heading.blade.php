@props(['id', 'step', 'title', 'subtitle'])

<div class="mb-6 flex flex-wrap items-start justify-between gap-3 border-b border-border pb-4">
    <div>
        <h2 id="{{ $id }}" class="text-xl font-semibold md:text-2xl">{{ $title }}</h2>
        <p class="mt-1 text-sm text-text-muted">{{ $subtitle }}</p>
    </div>
    <span class="rounded-full bg-primary-light px-3 py-1 text-[13px] font-semibold text-primary">Langkah {{ $step }} dari 4</span>
</div>
