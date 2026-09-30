@props([
    'name' => null,
    'email' => null,
    'size' => 36,
    'avatar' => null,
])

@php
    $initials = strtoupper(substr($name ?? 'U', 0, 2));
@endphp

<div {{ $attributes->merge(['class' => 'd-flex align-items-center gap-2']) }}>
    @if($avatar)
        <img src="{{ asset('storage/'.$avatar) }}" alt="{{ $name }}" class="rounded-circle flex-shrink-0" style="width: {{ $size }}px; height: {{ $size }}px; object-fit: cover;">
    @else
        <span class="user-image flex-shrink-0 rounded-circle d-inline-flex align-items-center justify-content-center bg-secondary text-white"
              style="width: {{ $size }}px; height: {{ $size }}px; font-size: {{ round($size * 0.36) }}px;">
            {{ $initials }}
        </span>
    @endif

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
