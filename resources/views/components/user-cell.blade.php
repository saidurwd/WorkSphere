@props([
    'name' => null,
    'email' => null,
    'size' => 36,
])

@php
    $initials = strtoupper(substr($name ?? 'U', 0, 2));
@endphp

<div {{ $attributes->merge(['class' => 'd-flex align-items-center gap-2']) }}>
    <span class="user-image flex-shrink-0"
          style="width: {{ $size }}px; height: {{ $size }}px; line-height: {{ $size }}px; font-size: {{ round($size * 0.36) }}px;">
        {{ $initials }}
    </span>

    @if ($name || $email)
        <div class="d-flex flex-column lh-sm">
            @if ($name)
                <span class="fw-semibold">{{ $name }}</span>
            @endif

            @if ($email)
                <span class="small text-body-secondary">{{ $email }}</span>
            @endif
        </div>
    @endif
</div>
