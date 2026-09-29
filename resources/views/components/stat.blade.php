@props([
    'label' => null,
    'value' => null,
    'variant' => 'primary',
    'icon' => null,
    'href' => null,
])

@php
    $variant = $variant === 'destructive' ? 'danger' : $variant;
    $borderClass = 'border-'.$variant;
    $iconClass = 'text-'.$variant;
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'card h-100 border-0 border-top border-3 '.$borderClass.($href ? ' text-decoration-none' : '')]) }}
>
    <div class="card-body d-flex align-items-center gap-3">
        @if ($icon)
            <div class="stat-icon mb-0 bg-{{ $variant }}-subtle text-{{ $variant }}-emphasis">
                <i class="bi bi-{{ $icon }} fs-5"></i>
            </div>
        @endif

        <div>
            <div class="stat-label mb-1">{{ $label }}</div>
            <div class="stat-value {{ $iconClass }}">{{ $value }}</div>
        </div>
    </div>
</{{ $tag }}>
