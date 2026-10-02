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
        /*
         * `is-invalid` draws a red border and an icon and announces nothing, so
         * without these attributes a screen-reader user submits a twenty-field
         * form, hears no error, and cannot tell which field was rejected.
         *
         * Both ids are derived from the field name rather than hand-written, so
         * the label, the field and the message cannot drift apart, and neither
         * can collide with the label's own `for` target.
         */
        $hasError = $errors->has($name);
        $errorId = $name.'-error';
        $helpId = $name.'-help';

        $describedBy = collect([$help ? $helpId : null, $hasError ? $errorId : null])
            ->filter()
            ->implode(' ');

        $shared = [
            'name' => $name,
            'id' => $name,
            'class' => 'form-control'.($hasError ? ' is-invalid' : ''),
            'aria-invalid' => $hasError ? 'true' : 'false',
        ];

        if ($describedBy !== '') {
            $shared['aria-describedby'] = $describedBy;
        }
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
            {{-- The label above carries `for="{{ $name }}"`, so the input needs that id to be associated with it at all. --}}
            <input type="checkbox" id="{{ $name }}" value="1" {{ old($name, $value) ? 'checked' : '' }} {{ $attributes->only(['disabled'])->merge(['class' => 'form-check-input'.($hasError ? ' is-invalid' : ''), 'aria-invalid' => $hasError ? 'true' : 'false']) }}>
            @if ($label)
                <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>
            @endif
        </div>
    @else
        <input type="{{ $type }}" value="{{ old($name, $value) }}" {{ $attributes->only(['placeholder', 'disabled', 'readonly', 'step', 'min', 'max', 'maxlength', 'minlength', 'autocomplete', 'data-quick-capture'])->merge($shared) }}>
    @endif

    @if ($help)
        <div id="{{ $helpId }}" class="form-text">{{ $help }}</div>
    @endif

    @error($name)
        <div id="{{ $errorId }}" class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
