@props([
    'name',
    'label' => null,
    'value' => null,
    'format' => 'Y-m-d',
    'help' => null,
    'inline' => false,
])

<div {{ $attributes->merge(['class' => $inline ? 'mb-3' : 'col mb-3']) }}>
    @if ($label)
        <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    @endif

    <input
        type="text"
        id="{{ $name }}"
        name="{{ $name }}"
        value="{{ old($name, $value instanceof \DateTimeInterface ? $value->format($format) : $value) }}"
        class="form-control{{ $errors->has($name) ? ' is-invalid' : '' }}"
        data-flatpickr="{{ $format === 'Y-m-d' ? 'Y-m-d' : $format }}"
        autocomplete="off"
    >

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
