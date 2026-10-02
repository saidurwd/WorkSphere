@php
    /**
     * Filtering, pruning and active-state all live in `App\Support\NavigationMenu`.
     * This component only draws what it is given, which is why the sidebar can be
     * tested as a data structure rather than as markup.
     */
    $menu = app(\App\Support\NavigationMenu::class);
    $nodes = $menu->forUser(auth()->user());

    $section = null;
@endphp

<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark" aria-label="Main navigation">
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
                @foreach ($nodes as $node)
                    {{-- Section headings come from the config and are emitted only
                         when something survived filtering underneath them, so a
                         heading never appears as a rule with nothing under it. --}}
                    @if (($node['section'] ?? null) && $node['section'] !== $section)
                        @php($section = $node['section'])
                        <li class="nav-header text-uppercase small fw-semibold text-body-secondary px-3 pt-3 pb-1">{{ $section }}</li>
                    @endif

                    <x-sidebar-menu-item :node="$node" />
                @endforeach
            </ul>

            <p class="sidebar-search-empty text-center text-body-secondary small px-3" data-lte-search-empty hidden>
                No menu items found.
            </p>
        </nav>
    </div>
</aside>
