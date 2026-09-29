@props([
    'title' => null,
    'description' => null,
    'icon' => 'speedometer2',
    'value' => null,
    'color' => 'primary',
    'href' => null,
])

@php
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'card stat-card h-100 text-decoration-none'.($href ? ' stat-card-link' : '')]) }}
>
    <div class="d-flex align-items-center gap-3">
        <div class="stat-icon bg-{{ $color }}-subtle text-{{ $color }}-emphasis mb-0">
            <i class="bi bi-{{ $icon }} fs-5"></i>
        </div>

        <div>
            <div class="stat-label mb-1">{{ $title }}</div>
            <div class="stat-value">{{ $value }}</div>
        </div>
    </div>

    @if ($description)
        <p class="text-body-secondary small mb-0 mt-3">{{ $description }}</p>
    @endif
</{{ $tag }}>
