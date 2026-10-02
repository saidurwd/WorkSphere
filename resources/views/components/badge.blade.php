@props(['variant' => 'secondary', 'icon' => null])

{{-- The classes come from StatusBadge rather than a `text-bg-` prefix here:
     `text-bg-secondary` renders white text on this theme's light `--bs-secondary`
     and is invisible. See StatusBadge::badgeClass(). --}}
<span {{ $attributes->merge(['class' => 'badge '.App\Support\StatusBadge::badgeClass($variant)]) }}>
    @if ($icon)
        <i class="bi bi-{{ $icon }}"></i>
    @endif

    {{ $slot }}
</span>