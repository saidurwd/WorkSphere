@props([
    'href' => null,
    'icon' => null,
    'label' => null,
    'variant' => 'outline-secondary',
    'size' => 'sm',
    'type' => null,
    'disabled' => false,
])

@php
    $classes = 'btn btn-'.$variant.($size ? ' btn-'.$size : '').' d-inline-flex align-items-center justify-content-center';
    $element = $href ? 'a' : 'button';
@endphp

<{{ $element }}
    @if ($href) href="{{ $href }}" @endif
    @if ($type) type="{{ $type }}" @endif
    @if ($disabled) disabled aria-disabled="true" @endif
    @if ($label) title="{{ $label }}" aria-label="{{ $label }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if ($icon)
        <i class="bi bi-{{ $icon }}"></i>
    @endif

    @unless ($slot->isEmpty())
        <span class="visually-hidden">{{ $label ?? $slot }}</span>
    @endunless
</{{ $element }}>
