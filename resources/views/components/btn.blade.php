@props([
    'href' => null,
    'variant' => 'primary',
    'icon' => null,
    'size' => null,
    'type' => null,
])

@php
    /*
     * The element follows whether there is somewhere to go, not whether a
     * submit type was passed.
     *
     * It used to be `$type === 'submit' ? 'button' : 'a'`, so every call
     * without an explicit type rendered an anchor — including
     * `<x-btn data-bs-toggle="modal">`, which produced `<a class="btn">` with
     * no href: not focusable, no role, Enter does nothing, and the Bootstrap
     * behaviour attached to it never fires. An anchor with no href is not a
     * link and not a button, so it is neither one keyboard-reachable.
     *
     * `href` present means navigation, so an anchor. Otherwise a real button,
     * which is focusable, activatable with both Enter and Space, and takes a
     * type.
     */
    $classes = 'btn btn-'.$variant.($size ? ' btn-'.$size : '');
    $element = $href ? 'a' : 'button';
@endphp

<{{ $element }}
    @if ($href) href="{{ $href }}" @endif
    @if (! $href) type="{{ $type ?? 'button' }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}
>
    @if ($icon)
        <i class="bi bi-{{ $icon }}"></i>
    @endif

    {{ $slot }}
</{{ $element }}>
