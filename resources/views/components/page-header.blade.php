@props(['title' => null, 'subtitle' => null, 'icon' => null])

<div {{ $attributes->merge(['class' => 'page-header mb-4']) }}>
    <div class="page-header-row">
        <div>
            <h1 class="page-title mb-0">{{ $title }}</h1>

            @if ($subtitle)
                <p class="page-description mb-0">{{ $subtitle }}</p>
            @endif
        </div>

        <div class="d-flex align-items-center gap-2">
            {{ $slot }}
        </div>
    </div>
</div>
