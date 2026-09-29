@props([
    'title',
    'items' => [],
    'emptyMessage' => 'No records found.',
    'viewAllRoute' => null,
    'viewAllLabel' => null,
    'icon' => null,
    'variant' => null,
])

<div {{ $attributes->merge(['class' => 'card h-100']) }}>
    <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title mb-0 d-flex align-items-center gap-2">
            @if ($icon)
                <i class="bi bi-{{ $icon }} text-body-secondary"></i>
            @endif

            {{ $title }}
        </h3>

        @if ($viewAllRoute)
            <a href="{{ $viewAllRoute }}" class="btn btn-sm btn-outline-secondary">
                {{ $viewAllLabel ?? 'View All' }}
            </a>
        @elseif ($variant)
            <x-badge :variant="$variant">{{ $title }}</x-badge>
        @endif
    </div>

    @if (count($items))
        <div class="list-group list-group-flush">
            @foreach ($items as $item)
                <a href="{{ $item['url'] ?? '#' }}" class="list-group-item list-group-item-action">
                    <div class="d-flex align-items-center gap-3">
                        <span class="user-image flex-shrink-0" style="width: 36px; height: 36px; font-size: 0.8rem;">
                            {{ $item['avatar'] ?? strtoupper(substr($item['title'] ?? '', 0, 1)) }}
                        </span>

                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-truncate">{{ $item['title'] ?? '' }}</div>

                            @if (! empty($item['subtitle']))
                                <div class="small text-body-secondary text-truncate">{{ $item['subtitle'] }}</div>
                            @endif
                        </div>

                        @if (! empty($item['badge']))
                            <x-badge :variant="$item['badge']['variant'] ?? 'secondary'" class="flex-shrink-0">
                                {{ $item['badge']['text'] ?? '' }}
                            </x-badge>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    @else
        <div class="card-body">
            <p class="text-body-secondary text-center mb-0 py-3">{{ $emptyMessage }}</p>
        </div>
    @endif
</div>
