@props([
    'name',
    'options' => [],
    'label' => null,
    'value' => null,
    'placeholder' => null,
    'help' => null,
    'required' => false,
    'create' => false,
])

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
        /* See x-form.input: the error has to be announced and pointed at, not just drawn. */
        $hasError = $errors->has($name);
        $errorId = $name.'-error';
        $helpId = $name.'-help';

        $describedBy = collect([$help ? $helpId : null, $hasError ? $errorId : null])
            ->filter()
            ->implode(' ');
    @endphp

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        class="form-select{{ $hasError ? ' is-invalid' : '' }}"
        aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        data-tom-select
        @if ($create) data-tom-select="true" @endif
        @if ($placeholder) data-tom-select-placeholder="{{ $placeholder }}" @endif
        @disabled($attributes->get('disabled'))
    >
        @if ($placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optionValue => $optionLabel)
            @php
                $selectedValue = old($name, $value);
                $isSelected = is_array($selectedValue)
                    ? in_array((string) $optionValue, array_map('strval', $selectedValue), true)
                    : (string) $selectedValue === (string) $optionValue;
            @endphp
            <option value="{{ $optionValue }}" @selected($isSelected)>{{ $optionLabel }}</option>
        @endforeach

        {{ $slot }}
    </select>

    @if ($help)
        <div id="{{ $helpId }}" class="form-text">{{ $help }}</div>
    @endif

    @error($name)
        <div id="{{ $errorId }}" class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
