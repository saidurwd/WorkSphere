@props([
    'name',
    'label' => null,
    'value' => null,
    'placeholder' => 'Select',
    'create' => false,
    'help' => null,
])

<div {{ $attributes->merge(['class' => 'mb-3']) }}>
    @if ($label)
        <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    @endif

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        class="form-select{{ $errors->has($name) ? ' is-invalid' : '' }}"
        data-tom-select
        data-tom-select-placeholder="{{ $placeholder }}"
        @if ($create) data-tom-select="true" @endif
    >
        <option value="">{{ $placeholder }}</option>

        @foreach ((array) \Illuminate\Support\Arr::wrap($slot) as $option)
            {!! $option !!}
        @endforeach
    </select>

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
