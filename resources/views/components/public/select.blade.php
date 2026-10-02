@props(['name', 'options', 'placeholder'])

{{-- Dropdown pilihan tetap dari config/ptk.php, terikat ke form.{name} --}}
<select id="{{ $name }}" name="{{ $name }}" class="field-input" x-model="form.{{ $name }}"
        :aria-invalid="errors.{{ $name }} ? 'true' : 'false'" aria-describedby="{{ $name }}_error">
    <option value="">{{ $placeholder }}</option>
    @foreach ($options as $option)
        <option value="{{ $option }}">{{ $option }}</option>
    @endforeach
</select>
