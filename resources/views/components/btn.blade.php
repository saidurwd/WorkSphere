@props([
    'href' => null,
    'variant' => 'primary',
    'icon' => null,
    'size' => null,
    'type' => null,
])

@php
    $classes = 'btn btn-'.$variant.($size ? ' btn-'.$size : '');
    $element = $type === 'submit' ? 'button' : 'a';
@endphp

<{{ $element }}
    @if ($element === 'a') href="{{ $href }}" @endif
    @if ($type) type="{{ $type }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if ($icon)
        <i class="bi bi-{{ $icon }}"></i>
    @endif

    {{ $slot }}
</{{ $element }}>
