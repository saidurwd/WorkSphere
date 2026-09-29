@props(['variant' => 'secondary', 'icon' => null])

<span {{ $attributes->merge(['class' => 'badge text-bg-'.$variant]) }}>
    @if ($icon)
        <i class="bi bi-{{ $icon }}"></i>
    @endif

    {{ $slot }}
</span>
