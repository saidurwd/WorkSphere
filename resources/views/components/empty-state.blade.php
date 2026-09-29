@props(['title' => null, 'description' => null, 'icon' => 'inbox', 'action' => null])

<div {{ $attributes->merge(['class' => 'text-center py-5']) }}>
    <i class="bi bi-{{ $icon }} display-4 text-body-secondary opacity-50 d-block mb-3"></i>

    @if ($title)
        <h3 class="h6 mb-1">{{ $title }}</h3>
    @endif

    @if ($description)
        <p class="text-body-secondary mb-3">{{ $description }}</p>
    @endif

    @if ($action)
        <div class="d-flex justify-content-center gap-2">{{ $action }}</div>
    @endif
</div>
