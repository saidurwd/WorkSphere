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

<li class="nav-item"
    @if ($hasChildren) x-data="{ open: {{ $isBranchActive ? 'true' : 'false' }} }" @endif>
    @if ($hasChildren)
        {{-- A branch is a disclosure, not a link. `href="#"` with a static
             `aria-expanded` keeps the group's initial state discoverable to
             assistive technology; Alpine only animates the list, it does not
             re-bind the attribute. --}}
        <a href="#" class="nav-link"
           aria-expanded="{{ $isBranchActive ? 'true' : 'false' }}"
           x-on:click.prevent="open = !open">
            <span class="nav-link-icon">
                <i class="bi bi-{{ $node['icon'] }}"></i>
            </span>
            <span class="nav-link-title">{{ $node['label'] }}</span>

            <i class="bi bi-chevron-right ms-auto" :class="{ 'rotate': open }"></i>
        </a>

        <ul class="navbar-nav flex-column ms-3" x-show="open">
            @foreach ($children as $child)
                <x-sidebar-menu-item :node="$child" :depth="$depth + 1" />
            @endforeach
        </ul>
    @else
        <a href="{{ $url }}"
           class="nav-link {{ $isCurrent ? 'active' : '' }}"
           @if ($isCurrent) aria-current="page" @endif>
            <span class="nav-link-icon">
                <i class="bi bi-{{ $node['icon'] }}"></i>
            </span>
            <span class="nav-link-title">{{ $node['label'] }}</span>
        </a>
    @endif
</li>
