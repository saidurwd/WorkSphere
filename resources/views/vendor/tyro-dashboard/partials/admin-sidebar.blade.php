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
            <img src="{{ $sidebarLogoSrc }}" alt="{{ $branding['app_name'] ?? config('app.name', 'Laravel') }}" class="sidebar-logo-img">
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
            <a href="{{ route('dashboard.asset-management.index') }}" class="sidebar-link {{ request()->routeIs('dashboard.asset-management.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4 8 4 8-4zm0 0l-8 4m8-4v10l-8 4m8-4l-8-4" />
                </svg>
                Asset Dashboard
            </a>
            <a href="{{ route($dashboardRoute::name('profile')) }}" class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('profile*')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                My Profile
            </a>
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

        <!-- Compliance & Obligation -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Compliance & Obligation</div>
            <div style="padding: 0.25rem 1.25rem; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground);">Management</div>
            <a href="{{ route('obligations.dashboard') }}" class="sidebar-link {{ request()->routeIs('obligations.dashboard') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                Dashboard
            </a>
            <a href="{{ route('obligations.index') }}" class="sidebar-link {{ request()->routeIs('obligations.index') || request()->routeIs('obligations.create') || request()->routeIs('obligations.show') || request()->routeIs('obligations.edit') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Obligations
            </a>
            <a href="{{ route('obligations.my-tasks') }}" class="sidebar-link {{ request()->routeIs('obligations.my-tasks') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                My Tasks
            </a>
            <a href="{{ route('obligations.calendar') }}" class="sidebar-link {{ request()->routeIs('obligations.calendar') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                Calendar
            </a>
            <a href="{{ route('obligations.renewals') }}" class="sidebar-link {{ request()->routeIs('obligations.renewals') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Renewals
            </a>
            <a href="{{ route('obligations.vendors') }}" class="sidebar-link {{ request()->routeIs('obligations.vendors') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6" />
                </svg>
                Vendors
            </a>
            <a href="{{ route('obligations.documents') }}" class="sidebar-link {{ request()->routeIs('obligations.documents') && !request()->routeIs('obligations.documents.store') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Documents
            </a>
            <a href="{{ route('obligations.notifications') }}" class="sidebar-link {{ request()->routeIs('obligations.notifications') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
                Notifications
            </a>
            <a href="{{ route('obligations.reports') }}" class="sidebar-link {{ request()->routeIs('obligations.reports') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v2.25c0 .621-.504 1.125-1.125 1.125h-2.25A1.125 1.125 0 013 15.375v-2.25zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v8.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125v-8.25zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
                Reports
            </a>
            <!-- Settings -->
            <div style="padding: 0.25rem 1.25rem; font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted-foreground);">Settings</div>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'companies') }}" class="sidebar-link {{ request()->is('*resources/companies*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6" />
                </svg>
                Companies
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'obligation_types') }}" class="sidebar-link {{ request()->is('*resources/obligation_types*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                Obligation Types
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'obligation_categories') }}" class="sidebar-link {{ request()->is('*resources/obligation_categories*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                </svg>
                Obligation Categories
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'notification_rules') }}" class="sidebar-link {{ request()->is('*resources/notification_rules*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                </svg>
                Notification Rules
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'escalation_rules') }}" class="sidebar-link {{ request()->is('*resources/escalation_rules*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                Escalation Rules
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'approval_workflows') }}" class="sidebar-link {{ request()->is('*resources/approval_workflows*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Workflows
            </a>
            <a href="{{ route($dashboardRoute::name('resources.index'), 'approval_workflow_steps') }}" class="sidebar-link {{ request()->is('*resources/approval_workflow_steps*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Workflow Steps
            </a>
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

        @hasanyrole('admin', 'super-admin')
        <!-- Admin Menu -->
        <div class="sidebar-section">
            <div class="sidebar-section-title">Administration</div>
            @userCan('user.manage')
            <a href="{{ route($dashboardRoute::name('users.index')) }}" class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('users.*')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
                Users
            </a>
            @enduserCan
            @if(config('tyro-dashboard.features.show_roles_menu', true))
            @userCan('role.manage')
            <a href="{{ route($dashboardRoute::name('roles.index')) }}" class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('roles.*')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Roles
            </a>
            @enduserCan
            @endif
            @if(config('tyro-dashboard.features.show_privileges_menu', true))
            @userCan('privilege.manage')
            <a href="{{ route($dashboardRoute::name('privileges.index')) }}" class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('privileges.*')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
                Privileges
            </a>
            @enduserCan
            @endif
            @if(config('tyro-dashboard.features.invitation_system', true))
            @userCan('invitation.manage')
            <a href="{{ route($dashboardRoute::name('invitations.admin.index')) }}" class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('invitations.admin.*')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
                Invitation Links
            </a>
            @enduserCan
            @endif

            @php
            $showAuditLogsMenu = false;
            if (config('tyro-dashboard.features.audit_logs', true) && config('tyro.audit.enabled', true) && class_exists('\HasinHayder\Tyro\Models\AuditLog')) {
            try {
            $showAuditLogsMenu = \Illuminate\Support\Facades\Schema::hasTable(config('tyro.tables.audit_logs', 'tyro_audit_logs'));
            } catch (\Throwable $e) {
            $showAuditLogsMenu = false;
            }
            }
            @endphp

            @if($showAuditLogsMenu)
            @userCan('audit.view')
            <a href="{{ route($dashboardRoute::name('audits.index')) }}" class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('audits.*')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Audit Logs
            </a>
            @enduserCan
            @endif

            @if(config('tyro-dashboard.features.system_settings', true))
            @userCan('system.manage')
            <a href="{{ route($dashboardRoute::name('settings.system.index')) }}" class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('settings.system.*')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2l7 4v6c0 5-3.5 9.5-7 10-3.5-.5-7-5-7-10V6l7-4z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v20" />
                </svg>
                System Settings
            </a>
            <a href="{{ route('meetings.notification-logs.index') }}" class="sidebar-link {{ request()->routeIs('meetings.notification-logs.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
                Meeting Notification Log
            </a>
            @enduserCan
            @endif

            @if(config('tyro-dashboard.features.checkpoints', true) && class_exists(\HasinHayder\TyroCheckpoint\TyroCheckpointServiceProvider::class))
            @userCan('checkpoint.manage')
            <a href="{{ route($dashboardRoute::name('checkpoints.index')) }}" class="sidebar-link {{ request()->routeIs($dashboardRoute::pattern('checkpoints.*')) ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
                Checkpoints
            </a>
            @enduserCan
            @endif

            @userCan('database.backup')
            <a href="{{ route('dashboard.database-backups.index') }}" class="sidebar-link {{ request()->routeIs('dashboard.database-backups.*') ? 'active' : '' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                </svg>
                Database Backup
            </a>
            @enduserCan

            @if(!empty($adminMenuItems))
            @foreach($adminMenuItems as $item)
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
        @endhasanyrole

        @hasanyrole('admin', 'super-admin')
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
        @endhasanyrole

        @hasanyrole('admin', 'super-admin')
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
        @endhasanyrole
    </nav>
</aside>