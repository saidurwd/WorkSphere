@props(['title', 'value', 'icon' => 'clipboard', 'style' => 'primary'])

@php
    $styles = [
        'success' => 'stat-icon-success',
        'warning' => 'stat-icon-warning',
        'danger' => 'stat-icon-danger',
        'info' => 'stat-icon-info',
    ];
    $iconClass = $styles[$style] ?? 'stat-icon-primary';
@endphp

<div class="stat-card">
    <div class="stat-icon {{ $iconClass }}">
        @switch($icon)
            @case('clipboard')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                @break
            @case('check-circle')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                @break
            @case('info')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                @break
            @case('warning')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-2.95l-7.93-13.5a2 2 0 00-3.48 0L3.33 16.05a2 2 0 001.74 2.95z" />
                </svg>
                @break
            @case('danger')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                @break
            @case('success')
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                @break
            @default
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4 8 4 8-4zm0 0l-8 4m8-4v10l-8 4m8-4l-8-4" />
                </svg>
                @break
        @endswitch
    </div>
    <div class="stat-label">{{ $title }}</div>
    <div class="stat-value">{{ $value }}</div>
</div>
