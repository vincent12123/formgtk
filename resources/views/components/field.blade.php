@props([
    'name',
    'label',
    'hint' => null,
    'required' => true,
    'for' => null,
])

{{-- Pembungkus field: label di atas, petunjuk, dan pesan error di bawah (design-system §5) --}}
<div {{ $attributes }}>
    <label for="{{ $for ?? $name }}" class="field-label">
        {{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif
    </label>

    {{ $slot }}

    @if ($hint)
        <p id="{{ $name }}_hint" class="field-hint">{{ $hint }}</p>
    @endif

    <p id="{{ $name }}_error" class="field-error" x-cloak x-show="errors.{{ $name }}" x-text="errors.{{ $name }}" role="alert"></p>
</div>
