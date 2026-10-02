@props(['node', 'depth' => 0])

@php
    /**
     * Rendering only. Every decision — whether this node is visible, whether it is
     * the current location, where it points — belongs to `App\Support\NavigationMenu`,
     * where it can be tested without rendering a page.
     *
     * @var \App\Support\NavigationMenu $menu
     */
    $menu = app(\App\Support\NavigationMenu::class);

    $children = $node['children'] ?? [];
    $hasChildren = $children !== [];

    $url = $menu->url($node);
    $isCurrent = $menu->isCurrent($node);
    $isBranchActive = $hasChildren && $menu->isActive($node);
@endphp

<li class="nav-item {{ $isBranchActive ? 'menu-open' : '' }}">
    @if ($hasChildren)
        {{-- A branch is a disclosure, not a link. `href="#"` with `aria-expanded`
             keeps it keyboard-operable; the previous tree had no aria state at all,
             so a screen reader could not tell an expanded group from a collapsed
             one. --}}
        <a href="#" class="nav-link" aria-expanded="{{ $isBranchActive ? 'true' : 'false' }}">
            <i class="nav-icon bi bi-{{ $node['icon'] }}"></i>
            <p>
                {{ $node['label'] }}
                <i class="nav-arrow bi bi-chevron-right"></i>
            </p>
        </a>

        <ul class="nav nav-treeview">
            @foreach ($children as $child)
                <x-sidebar-menu-item :node="$child" :depth="$depth + 1" />
            @endforeach
        </ul>
    @else
        <a href="{{ $url }}"
           class="nav-link {{ $isCurrent ? 'active' : '' }}"
           @if ($isCurrent) aria-current="page" @endif>
            <i class="nav-icon bi bi-{{ $node['icon'] }}"></i>
            <p>{{ $node['label'] }}</p>
        </a>
    @endif
</li>
