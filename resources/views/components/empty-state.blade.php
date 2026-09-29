@props(['title' => null, 'description' => null, 'icon' => 'inbox', 'action' => null])

<div {{ $attributes->merge(['class' => 'empty-state text-center py-5']) }}>
    <i class="bi bi-{{ $icon }} empty-state-icon d-block mb-3"></i>

    @if ($title)
        <p class="empty-state-title mb-1">{{ $title }}</p>
    @endif

    @if ($description)
        <p class="empty-state-description mb-3">{{ $description }}</p>
    @endif

    @if ($action)
        {{ $action }}
    @endif
</div>
