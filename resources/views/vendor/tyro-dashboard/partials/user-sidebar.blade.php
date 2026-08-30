<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="{{ route($dashboardRoute::name('index')) }}" class="sidebar-logo">
            @php
            $sidebarLogo = config('tyro-dashboard.branding.sidebar_logo');
            $sidebarLogoSrc = $sidebarLogo && !str_starts_with($sidebarLogo, 'http://') && !str_starts_with($sidebarLogo, 'https://')
                ? \Illuminate\Support\Facades\Storage::url($sidebarLogo)
                : $sidebarLogo;
            @endphp
            @if($sidebarLogo)
            <img src="{{ $sidebarLogoSrc }}" alt="{{ $branding['app_name'] ?? config('app.name', 'AsstTask Pro') }}" class="sidebar-logo-img">
            @else
            <div class="sidebar-logo-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            @endif
            <span class="sidebar-logo-text">{{ $branding['app_name'] ?? config('app.name', 'AsstTask Pro') }}</span>
        </a>
        @if(config('tyro-dashboard.collapsible_sidebar', false))
        <button class="sidebar-collapse-btn" onclick="toggleSidebarCollapse()" aria-label="Collapse sidebar">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
        </button>
        @endif
    </div>
    @if(config('tyro-dashboard.collapsible_sidebar', false))
    <button class="sidebar-expand-btn" onclick="toggleSidebarCollapse()" aria-label="Expand sidebar">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
    </button>
    @endif

    <nav class="sidebar-nav sidebar-accordion"
        data-sidebar-accordion
        data-sidebar-accordion-compact="{{ config('tyro-dashboard.branding.sidebar_accordion_compact', false) ? 'true' : 'false' }}"
        data-sidebar-accordion-open-sections="{{ config('tyro-dashboard.branding.sidebar_accordion_open_sections', 1) }}">
        <!-- Main Menu -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Menu</div>
            <a href="{{ route($dashboardRoute::name('index')) }}" class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('index')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>
            <a href="{{ route($dashboardRoute::name('profile')) }}" class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('profile*')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                My Profile
            </a>
            @if(config('tyro-dashboard.features.invitation_system', true))
            <a href="{{ route($dashboardRoute::name('invitations.index')) }}" class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('invitations.index')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                My Invitation Link
            </a>
            @endif
            @if(!empty($commonMenuItems))
            @foreach($commonMenuItems as $item)
            @php
            $showItem = true;
            if (!empty($item['roles'])) {
                $showItem = auth()->check() && auth()->user()->hasAnyRole($item['roles']);
            }
            if ($showItem && !empty($item['privileges'])) {
                $user = auth()->user();
                $showItem = $user && collect($item['privileges'])->contains(fn($priv) => $user->hasPrivilege($priv));
            }
            @endphp
            @if($showItem)
            <a href="{{ route($item['route'] ?? '#') }}" class="sidebar-link {{ request()->routeIs($item['route'] ?? '') ? 'active' : '' }}">
                @if(isset($item['icon']))
                {!! $item['icon'] !!}
                @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                @endif
                {{ $item['title'] ?? 'Menu Item' }}
            </a>
            @endif
            @endforeach
            @endif
            @if(!empty($userMenuItems))
            @foreach($userMenuItems as $item)
            @php
            $showItem = true;
            if (!empty($item['roles'])) {
                $showItem = auth()->check() && auth()->user()->hasAnyRole($item['roles']);
            }
            if ($showItem && !empty($item['privileges'])) {
                $user = auth()->user();
                $showItem = $user && collect($item['privileges'])->contains(fn($priv) => $user->hasPrivilege($priv));
            }
            @endphp
            @if($showItem)
            <a href="{{ route($item['route'] ?? '#') }}" class="sidebar-link {{ request()->routeIs($item['route'] ?? '') ? 'active' : '' }}">
                @if(isset($item['icon']))
                {!! $item['icon'] !!}
                @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                @endif
                {{ $item['title'] ?? 'Menu Item' }}
            </a>
            @endif
            @endforeach
            @endif
        </div>

        <!-- Task Management -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Task Management</div>
            <a href="{{ route('tasks.dashboard') }}" class="sidebar-link {{ request()->routeIs('tasks.dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Task Dashboard
            </a>
            <a href="{{ route('tasks.index') }}" class="sidebar-link {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Tasks
            </a>
            <a href="{{ route('projects.index') }}" class="sidebar-link {{ request()->routeIs('projects.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                Projects
            </a>
        </div>

        <!-- Meeting Management -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Meeting Management</div>
            @userCan('meeting.view')
            <a href="{{ route('meetings.dashboard') }}" class="sidebar-link {{ request()->routeIs('meetings.dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>
            <a href="{{ route('meetings.index') }}" class="sidebar-link {{ request()->routeIs('meetings.index') || request()->routeIs('meetings.create') || request()->routeIs('meetings.show') || request()->routeIs('meetings.edit') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                Meetings
            </a>
            <a href="{{ route('meetings.calendar') }}" class="sidebar-link {{ request()->routeIs('meetings.calendar') || request()->routeIs('meetings.calendar.events') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                Calendar
            </a>
            <a href="{{ route('meetings.action-items.index') }}" class="sidebar-link {{ request()->routeIs('meetings.action-items.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Action Items
            </a>
            <a href="{{ route('meetings.reports.decisions') }}" class="sidebar-link {{ request()->routeIs('meetings.reports.decisions') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Decisions
            </a>
            @enduserCan

            @userCan('meeting.manage_templates')
            <div style="padding: 0.25rem 1.25rem; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground);">Settings</div>
            <a href="{{ route('meetings.types.index') }}" class="sidebar-link {{ request()->routeIs('meetings.types.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                Meeting Types
            </a>
            <a href="{{ route('meetings.tags.index') }}" class="sidebar-link {{ request()->routeIs('meetings.tags.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.782.782 2.046.782 2.828 0l4.268-4.268c.782-.782.782-2.046 0-2.828L12.14 3.659A2.25 2.25 0 0010.548 3H9.568z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                </svg>
                Tags
            </a>
            @enduserCan

            @userCan('meeting.view_reports')
            <a href="{{ route('meetings.reports.index') }}" class="sidebar-link {{ request()->routeIs('meetings.reports.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-2.25A1.125 1.125 0 013 15.375v-2.25zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125v-8.25zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
                Reports
            </a>
            @enduserCan
        </div>

        <!-- Asset Management -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Asset Management</div>
            <div style="padding: 0.25rem 1.25rem; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground);">Foundation</div>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'departments') }}" class="sidebar-link {{ request()->is('*resources/departments*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V7l7-4 7 4v14" />
                </svg>
                Departments
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'locations') }}" class="sidebar-link {{ request()->is('*resources/locations*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s-7-5.5-7-11a7 7 0 0114 0c0 5.5-7 11-7 11z" />
                    <circle cx="12" cy="10" r="2.5" />
                </svg>
                Locations
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'employees') }}" class="sidebar-link {{ request()->is('*resources/employees*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Employees
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'asset_categories') }}" class="sidebar-link {{ request()->is('*resources/asset_categories*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                Asset Categories
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'asset_sub_categories') }}" class="sidebar-link {{ request()->is('*resources/asset_sub_categories*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                Asset Sub Categories
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'vendors') }}" class="sidebar-link {{ request()->is('*resources/vendors*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6" />
                </svg>
                Vendors
            </a>

            <div style="padding: 0.5rem 1.25rem 0.25rem; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground);">Assets</div>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'assets') }}" class="sidebar-link {{ request()->is('*resources/assets*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4 8 4 8-4zm0 0l-8 4m8-4v10l-8 4m8-4l-8-4" />
                </svg>
                Assets
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'asset_documents') }}" class="sidebar-link {{ request()->is('*resources/asset_documents*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Asset Documents
            </a>
            <a href="{{ route('gate-passes.index') }}" class="sidebar-link {{ request()->routeIs('gate-passes.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                Gate Pass
            </a>

            <div style="padding: 0.5rem 1.25rem 0.25rem; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground);">Procurement</div>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'purchase_orders') }}" class="sidebar-link {{ request()->is('*resources/purchase_orders*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Purchase Orders
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'purchase_order_details') }}" class="sidebar-link {{ request()->is('*resources/purchase_order_details*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                PO Details
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'goods_receipts') }}" class="sidebar-link {{ request()->is('*resources/goods_receipts*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14l4-4V7a2 2 0 00-2-2H5a2 2 0 00-2 2z" />
                </svg>
                Goods Receipts
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'goods_receipt_details') }}" class="sidebar-link {{ request()->is('*resources/goods_receipt_details*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                GRN Details
            </a>

            <div style="padding: 0.5rem 1.25rem 0.25rem; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground);">Assignment &amp; Usage</div>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'asset_assignments') }}" class="sidebar-link {{ request()->is('*resources/asset_assignments*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Asset Assignments
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'asset_transfers') }}" class="sidebar-link {{ request()->is('*resources/asset_transfers*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 3l4 4-4 4M7 21l-4-4 4-4M21 7H7m-4 10h14" />
                </svg>
                Asset Transfers
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'software_products') }}" class="sidebar-link {{ request()->is('*resources/software_products*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2M15 3v2M9 19v2M15 19v2M5 9h2m10 0h2M5 15h2m10 0h2M7 7h10v10H7z" />
                </svg>
                Software Products
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'software_licenses') }}" class="sidebar-link {{ request()->is('*resources/software_licenses*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                Software Licenses
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'software_installations') }}" class="sidebar-link {{ request()->is('*resources/software_installations*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 3v2M15 3v2M9 19v2M15 19v2M5 9h2m10 0h2M5 15h2m10 0h2M7 7h10v10H7z" />
                </svg>
                Software Installations
            </a>

            <div style="padding: 0.5rem 1.25rem 0.25rem; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground);">Maintenance &amp; Audit</div>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'maintenance_requests') }}" class="sidebar-link {{ request()->is('*resources/maintenance_requests*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a4 4 0 010 5.4l-5 5a4 4 0 01-5.7-5.6l5-5a4 4 0 015.7 0z" />
                </svg>
                Maintenance Requests
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'maintenance_history') }}" class="sidebar-link {{ request()->is('*resources/maintenance_history*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Maintenance History
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'asset_audits') }}" class="sidebar-link {{ request()->is('*resources/asset_audits*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 11l3 3 8-8m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Asset Audits
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'asset_audit_details') }}" class="sidebar-link {{ request()->is('*resources/asset_audit_details*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 11l3 3 8-8m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Audit Details
            </a>

            <div style="padding: 0.5rem 1.25rem 0.25rem; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground);">Disposal &amp; Access</div>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'asset_disposals') }}" class="sidebar-link {{ request()->is('*resources/asset_disposals*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6" />
                </svg>
                Asset Disposals
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'permissions') }}" class="sidebar-link {{ request()->is('*resources/permissions*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                Permissions
            </a>
            @userCan('role.manage')
            <a href="{{ route($dashboardRoute::name('resources.index'), 'role_permissions') }}" class="sidebar-link {{ request()->is('*resources/role_permissions*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                Role Permissions
            </a>
            @enduserCan
            @userCan('activity.view')
            <a href="{{ route($dashboardRoute::name('resources.index'), 'activity_logs') }}" class="sidebar-link {{ request()->is('*resources/activity_logs*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Activity Logs
            </a>
            @enduserCan
        </div>

        <!-- Residence Identification -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Residence Identification</div>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'estates') }}" class="sidebar-link {{ request()->is('*resources/estates*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6" />
                </svg>
                Estates
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'estate_divisions') }}" class="sidebar-link {{ request()->is('*resources/estate_divisions*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                Estate Divisions
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'estate_residence_types') }}" class="sidebar-link {{ request()->is('*resources/estate_residence_types*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                Residence Types
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'estate_staff') }}" class="sidebar-link {{ request()->is('*resources/estate_staff*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Estate Staff
            </a>
        </div>

        <!-- Media -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Media</div>
            <a href="{{ route($dashboardRoute::name('media')) }}" class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('media*')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                </svg>
                Media Library
            </a>
        </div>

        @php
        // Filter resources to only those user can access
        $accessibleResources = [];
        foreach ($allResources ?? config('tyro-dashboard.resources', []) as $key => $resource) {
            $canAccess = true;
            if (isset($resource['roles']) && !empty($resource['roles'])) {
                $canAccess = false;
                $user = auth()->user();
                if ($user && method_exists($user, 'tyroRoleSlugs')) {
                    $userRoles = $user->tyroRoleSlugs();
                    foreach ($resource['roles'] as $role) {
                        if (in_array($role, $userRoles)) {
                            $canAccess = true;
                            break;
                        }
                    }
                    if (!$canAccess && isset($resource['readonly']) && !empty($resource['readonly'])) {
                        foreach ($resource['readonly'] as $role) {
                            if (in_array($role, $userRoles)) {
                                $canAccess = true;
                                break;
                            }
                        }
                    }
                }
            }
            if ($canAccess) {
                $accessibleResources[$key] = $resource;
            }
        }
        @endphp

        @if(!empty($accessibleResources))
        <div class="sidebar-section">
            <div class="sidebar-section-title">Resources</div>
            @foreach($accessibleResources as $key => $resource)
            <a href="{{ route($dashboardRoute::name('resources.index'), $key) }}" class="sidebar-link {{ request()->is('*resources/'.$key.'*') ? 'active' : '' }}">
                @if(isset($resource['icon']))
                {!! $resource['icon'] !!}
                @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                </svg>
                @endif
                {{ $resource['title'] }}
            </a>
            @endforeach
        </div>
        @endif

        @if(!config('tyro-dashboard.disable_examples', false) && !app()->environment('production'))
        <div class="sidebar-section">
            <div class="sidebar-section-title">Examples</div>
            <a href="{{ route($dashboardRoute::name('components')) }}" class="sidebar-link {{ (request()->routeIs($dashboardRoute::pattern('components')) || request()->routeIs($dashboardRoute::pattern('examples.components'))) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2H6a2 2 0 01-2-2V6z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 6a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2V6z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 15a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2H6a2 2 0 01-2-2v-3z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 15a2 2 0 012-2h3a2 2 0 012 2v3a2 2 0 01-2 2h-3a2 2 0 01-2-2v-3z" />
                </svg>
                Dashboard Components
            </a>
            <a href="{{ route($dashboardRoute::name('widgets')) }}" class="sidebar-link {{ (request()->routeIs($dashboardRoute::pattern('widgets')) || request()->routeIs($dashboardRoute::pattern('examples.widgets'))) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 5h6v6H5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 13h6v6h-6z" />
                </svg>
                Widgets
            </a>
            @if(class_exists('HasinHayder\\TyroDashboardComponents\\TyroDashboardComponentsServiceProvider'))
            <a href="{{ route($dashboardRoute::name('x-components')) }}" class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('x-components')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Form Components
            </a>
            @endif
        </div>
        @endif
    </nav>
</aside>
