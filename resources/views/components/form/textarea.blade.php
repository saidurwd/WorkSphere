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

    @php
        /* See x-form.input: the error has to be announced and pointed at, not just drawn. */
        $hasError = $errors->has($name);
        $errorId = $name.'-error';
        $helpId = $name.'-help';

        $describedBy = collect([$help ? $helpId : null, $hasError ? $errorId : null])
            ->filter()
            ->implode(' ');
    @endphp

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        class="form-control{{ $hasError ? ' is-invalid' : '' }}"
        aria-invalid="{{ $hasError ? 'true' : 'false' }}"
        @if ($describedBy !== '') aria-describedby="{{ $describedBy }}" @endif
        @disabled($attributes->get('disabled'))
        @readonly($attributes->get('readonly'))
    >{{ old($name, $value) }}</textarea>

    @if ($help)
        <div id="{{ $helpId }}" class="form-text">{{ $help }}</div>
    @endif

    @error($name)
        <div id="{{ $errorId }}" class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
