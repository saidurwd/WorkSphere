@props(['name', 'label' => null, 'value' => null, 'type' => 'text', 'help' => null, 'required' => false, 'rows' => 4])

<div {{ $attributes->merge(['class' => 'mb-3']) }}>
    @if ($label)
        <label for="{{ $name }}" class="form-label">
            {{ $label }}
            @if ($required)
                <span class="text-danger">*</span>
            @endif
        </label>
    @endif

    @php
        $shared = [
            'name' => $name,
            'id' => $name,
            'class' => 'form-control'.($errors->has($name) ? ' is-invalid' : ''),
        ];
    @endphp

    @if ($type === 'textarea')
        <textarea {{ $attributes->only(['placeholder', 'disabled', 'readonly'])->merge($shared)->merge(['rows' => $rows]) }}>{{ old($name, $value) }}</textarea>
    @elseif ($type === 'select')
        <select {{ $attributes->only(['disabled', 'multiple'])->merge($shared) }}>
            @if ($slot->isEmpty())
                <option value="">{{ $label ? 'Select '.strtolower($label) : 'Select' }}</option>
            @endif
            {{ $slot }}
        </select>
    @elseif ($type === 'checkbox')
        <div class="form-check">
            <input type="checkbox" value="1" {{ old($name, $value) ? 'checked' : '' }} {{ $attributes->only(['disabled'])->merge(['class' => 'form-check-input']) }}>
            @if ($label)
                <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>
            @endif
        </div>
    @else
        <input type="{{ $type }}" value="{{ old($name, $value) }}" {{ $attributes->only(['placeholder', 'disabled', 'readonly', 'step', 'min', 'max'])->merge($shared) }}>
    @endif

    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
