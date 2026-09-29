@props(['title' => null, 'subtitle' => null, 'icon' => null])

<div {{ $attributes->merge(['class' => 'mb-4']) }}>
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div>
            <h1 class="h3 mb-1 d-flex align-items-center gap-2">
                @if ($icon)
                    <i class="bi bi-{{ $icon }} text-body-secondary"></i>
                @endif

                {{ $title }}
            </h1>

            @if ($subtitle)
                <p class="text-body-secondary mb-0">{{ $subtitle }}</p>
            @endif
        </div>

        @if (! $slot->isEmpty())
            <div class="d-flex align-items-center gap-2 flex-wrap">
                {{ $slot }}
            </div>
        @endif
    </div>

    <hr class="mt-3 mb-0 opacity-75">
</div>
