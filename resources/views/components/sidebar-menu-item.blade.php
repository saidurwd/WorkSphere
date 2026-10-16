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
        {{-- A branch is a disclosure, not a link. `href="#"` is never followed;
             clicking toggles the group. `aria-expanded` is rendered server-side for
             the initial state (no-JS accessibility) and rebound reactively via
             Alpine so assistive technology reflects toggles afterwards. --}}
        <a href="#" class="nav-link"
           aria-expanded="{{ $isBranchActive ? 'true' : 'false' }}"
           :aria-expanded="open"
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
