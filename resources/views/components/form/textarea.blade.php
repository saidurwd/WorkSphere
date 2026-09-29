@props(['name', 'label' => null, 'value' => null, 'rows' => 4, 'help' => null, 'required' => false])

<div {{ $attributes->merge(['class' => 'mb-3']) }}>
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        class="form-control{{ $errors->has($name) ? ' is-invalid' : '' }}"
        @disabled($attributes->get('disabled'))
        @readonly($attributes->get('readonly'))
    >{{ old($name, $value) }}</textarea>

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
