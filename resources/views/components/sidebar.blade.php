@php
    $isAdmin = auth()->user()?->hasAnyRole(config('authorization.admin_roles', [])) ?? false;

    $menu = collect(config('navigation.menu', []))
        ->reject(fn (array $node): bool => ($node['admin'] ?? false) && ! $isAdmin)
        ->values();
@endphp

<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="{{ route('dashboard.index') }}" class="brand-link text-decoration-none">
            <i class="bi bi-diagram-3 brand-image opacity-75 ms-3 me-2"></i>
            <span class="brand-text fw-light">{{ config('app.name', 'Laravel') }}</span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <div class="sidebar-search" data-lte-toggle="sidebar-search" role="search">
            <label class="visually-hidden" for="sidebar-search-input">Search menu</label>
            <div class="input-group">
                <span class="input-group-text border-0 bg-transparent">
                    <i class="bi bi-search"></i>
                </span>
                <input id="sidebar-search-input"
                       type="search"
                       class="form-control"
                       placeholder="Search menu..."
                       autocomplete="off"
                       aria-label="Search menu">
            </div>
        </div>

        <nav class="mt-2" aria-label="Main navigation">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                @foreach ($menu as $node)
                    <x-sidebar-menu-item :node="$node" />
                @endforeach
            </ul>

            <p class="sidebar-search-empty text-center text-body-secondary small px-3" data-lte-search-empty hidden>
                No menu items found.
            </p>
        </nav>
    </div>
</aside>
