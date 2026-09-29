@props([
    'title',
    'value',
    'icon' => 'activity',
    'variant' => 'primary',
    'href' => null,
    'footer' => null,
])

@php
    $variant = $variant === 'destructive' ? 'danger' : $variant;
@endphp

<div {{ $attributes->merge(['class' => 'small-box text-bg-'.($variant === 'light' || $variant === 'dark' ? $variant : $variant)]) }}>
    <div class="inner">
        <h3>{{ $value }}</h3>
        <p>{{ $title }}</p>
    </div>

    <i class="bi bi-{{ $icon }} small-box-icon" aria-hidden="true"></i>

    @if ($href)
        <a href="{{ $href }}" class="small-box-footer link-light">
            {{ $footer ?? 'View more' }} <i class="bi bi-arrow-right ms-1"></i>
        </a>
    @endif
</div>
