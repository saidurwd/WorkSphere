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

<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark" aria-label="Main navigation"
       :class="{ 'sidebar-open': $store.sidebar.open }">
    <div class="sidebar-brand px-3 py-3 border-bottom">
        <a href="{{ route('dashboard.index') }}" class="d-flex align-items-center gap-2 text-decoration-none">
            <i class="bi bi-diagram-3 fs-4"></i>
            <span class="fw-light">{{ config('app.name', 'Laravel') }}</span>
        </a>
    </div>

    <div class="sidebar-search px-3 py-2">
        <label class="visually-hidden" for="sidebar-search-input">Search menu</label>
        <div class="input-group input-group-sm">
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

    <nav class="navbar navbar-vertical navbar-expand-lg">
        <ul class="navbar-nav flex-column" role="menu">
            @foreach ($nodes as $node)
                {{-- Section headings come from the config and are emitted only
                   when something survived filtering underneath them, so a
                   heading never appears as a rule with nothing under it. --}}
                @if (($node['section'] ?? null) && $node['section'] !== $section)
                    @php($section = $node['section'])
                    <li class="nav-section-title">{{ $section }}</li>
                @endif

                <x-sidebar-menu-item :node="$node" />
            @endforeach
        </ul>
    </nav>
</aside>
