@props([
    'title' => null,
    'label' => null,
    'description' => null,
    'icon' => null,
    'value' => null,
    'variant' => 'primary',
    'href' => null,
])

@php
    $title ??= $label;
    $variant = $variant === 'destructive' ? 'danger' : $variant;
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'card h-100 shadow-sm text-decoration-none'.($href ? ' card-link' : '')]) }}
>
    <div class="card-body d-flex align-items-center gap-3">
        @if ($icon)
            <span class="stat-icon text-bg-{{ $variant }} d-inline-flex align-items-center justify-content-center rounded-3 fs-5 flex-shrink-0">
                <i class="bi bi-{{ $icon }}"></i>
            </span>
        @endif

        <div class="min-w-0">
            <div class="text-body-secondary text-uppercase small fw-semibold">{{ $title }}</div>
            <div class="fs-4 fw-semibold lh-1 mb-0">{{ $value }}</div>
        </div>
    </div>

    @if ($description)
        <div class="card-footer bg-transparent border-0 pt-0 small text-body-secondary">
            {{ $description }}
        </div>
    @endif
</{{ $tag }}>
