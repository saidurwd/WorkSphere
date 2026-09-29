@props(['type' => 'info', 'message' => null, 'dismissible' => false])

@php
    $icons = [
        'success' => 'bi-check-circle-fill',
        'danger' => 'bi-exclamation-triangle-fill',
        'error' => 'bi-exclamation-triangle-fill',
        'warning' => 'bi-exclamation-circle-fill',
        'info' => 'bi-info-circle-fill',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'alert alert-'.$type.' d-flex align-items-start gap-2 mb-3']) }} role="alert">
    <i class="bi {{ $icons[$type] ?? $icons['info'] }}"></i>

    <div class="flex-grow-1">
        @if ($message)
            {{ $message }}
        @else
            {{ $slot }}
        @endif
    </div>

    @if ($dismissible)
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    @endif
</div>
