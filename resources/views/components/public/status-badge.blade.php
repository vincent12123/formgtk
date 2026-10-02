@props(['status'])

{{-- Badge status: pil, latar warna status 12%, teks warna status (design-system §5, sama dengan dashboard) --}}
@php
    $kelas = match ($status->getColor()) {
        'warning' => 'bg-warning/12 text-warning',
        'info' => 'bg-info/12 text-info',
        'success' => 'bg-success/12 text-success',
        'danger' => 'bg-danger/12 text-danger',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-3 py-1 text-[13px] font-semibold', $kelas]) }} data-status="{{ $status->value }}">
    {{ $status->getLabel() }}
</span>
