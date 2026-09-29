@php
    $isAdmin = auth()->user()?->hasAnyRole(config('authorization.admin_roles', [])) ?? false;
@endphp

<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="{{ route('dashboard.index') }}" class="brand-link text-decoration-none">
            <i class="bi bi-diagram-3 brand-image opacity-75 ms-3 me-2"></i>
            <span class="brand-text fw-light">{{ config('app.name', 'Laravel') }}</span>
        </a>
    </div>

    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                @php
                    $sections = [
                        ['label' => 'Overview', 'items' => [
                            ['label' => 'Dashboard', 'icon' => 'speedometer2', 'route' => 'dashboard.index'],
                            ['label' => 'My Tasks', 'icon' => 'check2-square', 'route' => 'tasks.index'],
                            ['label' => 'Projects', 'icon' => 'folder2-open', 'route' => 'projects.index'],
                        ]],
                        ['label' => 'Meetings', 'items' => [
                            ['label' => 'Dashboard', 'icon' => 'camera-video', 'route' => 'meetings.dashboard'],
                            ['label' => 'Meetings', 'icon' => 'journal-text', 'route' => 'meetings.index'],
                            ['label' => 'Calendar', 'icon' => 'calendar3', 'route' => 'meetings.calendar'],
                            ['label' => 'Action Items', 'icon' => 'list-task', 'route' => 'meetings.action-items.index'],
                            ['label' => 'Reports', 'icon' => 'bar-chart-line', 'route' => 'meetings.reports.index'],
                            ['label' => 'Types', 'icon' => 'tags', 'route' => 'meetings.types.index'],
                            ['label' => 'Tags', 'icon' => 'bookmark', 'route' => 'meetings.tags.index'],
                            ['label' => 'Notification Logs', 'icon' => 'bell', 'route' => 'meetings.notification-logs.index'],
                        ]],
                        ['label' => 'Obligations', 'items' => [
                            ['label' => 'Dashboard', 'icon' => 'speedometer2', 'route' => 'obligations.dashboard'],
                            ['label' => 'Obligations', 'icon' => 'file-earmark-text', 'route' => 'obligations.index'],
                            ['label' => 'My Tasks', 'icon' => 'check2-square', 'route' => 'obligations.my-tasks'],
                            ['label' => 'Calendar', 'icon' => 'calendar3', 'route' => 'obligations.calendar'],
                            ['label' => 'Renewals', 'icon' => 'arrow-repeat', 'route' => 'obligations.renewals'],
                            ['label' => 'Vendors', 'icon' => 'building', 'route' => 'obligations.vendors'],
                            ['label' => 'Documents', 'icon' => 'folder', 'route' => 'obligations.documents'],
                            ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'obligations.notifications'],
                            ['label' => 'Reports', 'icon' => 'bar-chart-line', 'route' => 'obligations.reports'],
                        ]],
                        ['label' => 'Administration', 'items' => [
                            ['label' => 'Database Backups', 'icon' => 'database', 'route' => 'dashboard.database-backups.index', 'admin' => true],
                        ]],
                    ];
                @endphp

                @foreach ($sections as $section)
                    <li class="nav-item {{ $loop->first ? '' : 'mt-2' }}">
                        <p class="sidebar-section-title text-uppercase small fw-semibold opacity-50 px-3 mb-1">{{ $section['label'] }}</p>

                        <ul class="nav sidebar-menu flex-column">
                            @foreach ($section['items'] as $item)
                                @continue(($item['admin'] ?? false) && ! $isAdmin)
                                <li class="nav-item">
                                    <a href="{{ route($item['route']) }}" class="nav-link {{ request()->routeIs($item['route']) ? 'active' : '' }}">
                                        <i class="nav-icon bi bi-{{ $item['icon'] }}"></i>
                                        <p>{{ $item['label'] }}</p>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach

            </ul>
        </nav>
    </div>
</aside>
