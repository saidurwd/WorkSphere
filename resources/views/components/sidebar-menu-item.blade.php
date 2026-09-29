@props(['node', 'depth' => 0])

@php
    $isAdmin = auth()->user()?->hasAnyRole(config('authorization.admin_roles', [])) ?? false;

    $children = collect($node['children'] ?? [])
        ->reject(fn (array $child): bool => ($child['admin'] ?? false) && ! $isAdmin)
        ->values();

    $hasChildren = $children->isNotEmpty();

    $isActive = collect($node['active'] ?? [$node['route'] ?? ''])
        ->filter()
        ->contains(fn (string $pattern): bool => request()->routeIs($pattern));

    $isBranchActive = $hasChildren && $children->contains(
        fn (array $child): bool => collect($child['active'] ?? [$child['route'] ?? ''])
            ->filter()
            ->contains(fn (string $pattern): bool => request()->routeIs($pattern))
    );
@endphp

<li class="nav-item {{ $hasChildren && $isBranchActive ? 'menu-open' : '' }}">
    @if ($hasChildren)
        <a href="#" class="nav-link">
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
        <a href="{{ route($node['route']) }}"
           class="nav-link {{ $isActive ? 'active' : '' }}"
           @if ($isActive) aria-current="page" @endif>
            <i class="nav-icon bi bi-{{ $node['icon'] }}"></i>
            <p>{{ $node['label'] }}</p>
        </a>
    @endif
</li>
