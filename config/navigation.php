<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Sidebar Menu
    |--------------------------------------------------------------------------
    |
    | Drives the AdminLTE sidebar treeview. Each node accepts:
    |
    |   label     string        Text shown in the menu.
    |   icon      string        Bootstrap Icons name, without the `bi bi-` prefix.
    |   route     string|null   Named route. Required for leaf nodes; parents that
    |                            only expand a submenu leave this null.
    |   children  array         Nested nodes, rendered recursively.
    |   active    array         Route name patterns that mark this node (and its
    |                            ancestors) as the current location.
    |   admin     bool          Restrict the node to users holding an admin role.
    |
    */

    'menu' => [
        [
            'label' => 'Dashboard',
            'icon' => 'speedometer2',
            'route' => 'dashboard.index',
            'active' => ['dashboard.index', 'dashboard.ui-kit'],
        ],

        [
            'label' => 'Tasks',
            'icon' => 'check2-square',
            'active' => ['tasks.*'],
            'children' => [
                ['label' => 'Dashboard', 'icon' => 'speedometer2', 'route' => 'tasks.dashboard'],
                ['label' => 'My Tasks', 'icon' => 'list-task', 'route' => 'tasks.index', 'active' => ['tasks.index', 'tasks.show', 'tasks.create', 'tasks.edit']],
                ['label' => 'Task Transfers', 'icon' => 'arrow-left-right', 'route' => 'task-transfers.index', 'active' => ['task-transfers.*']],
                ['label' => 'Notification Logs', 'icon' => 'bell', 'route' => 'tasks.notification-logs.index'],
            ],
        ],

        [
            'label' => 'Projects',
            'icon' => 'folder2-open',
            'route' => 'projects.index',
            'active' => ['projects.*'],
        ],

        [
            'label' => 'Meetings',
            'icon' => 'journal-text',
            'active' => ['meetings.*'],
            'children' => [
                ['label' => 'Dashboard', 'icon' => 'speedometer2', 'route' => 'meetings.dashboard'],
                ['label' => 'Meetings', 'icon' => 'calendar-week', 'route' => 'meetings.index', 'active' => ['meetings.index', 'meetings.show', 'meetings.create', 'meetings.edit', 'meetings.print']],
                ['label' => 'Calendar', 'icon' => 'calendar3', 'route' => 'meetings.calendar'],
                ['label' => 'Action Items', 'icon' => 'list-check', 'route' => 'meetings.action-items.index', 'active' => ['meetings.action-items.*']],
                ['label' => 'Notification Logs', 'icon' => 'bell', 'route' => 'meetings.notification-logs.index'],

                [
                    'label' => 'Reports',
                    'icon' => 'bar-chart-line',
                    'active' => ['meetings.reports.*'],
                    'children' => [
                        ['label' => 'Overview', 'icon' => 'circle', 'route' => 'meetings.reports.index'],
                        ['label' => 'Meetings', 'icon' => 'circle', 'route' => 'meetings.reports.meetings'],
                        ['label' => 'Action Items', 'icon' => 'circle', 'route' => 'meetings.reports.actions'],
                        ['label' => 'Overdue Actions', 'icon' => 'circle', 'route' => 'meetings.reports.overdue'],
                        ['label' => 'Person Wise', 'icon' => 'circle', 'route' => 'meetings.reports.person-wise'],
                        ['label' => 'Department Wise', 'icon' => 'circle', 'route' => 'meetings.reports.department-wise'],
                        ['label' => 'Decisions', 'icon' => 'circle', 'route' => 'meetings.reports.decisions'],
                    ],
                ],

                [
                    'label' => 'Configuration',
                    'icon' => 'sliders',
                    'active' => ['meetings.types.*', 'meetings.tags.*'],
                    'children' => [
                        ['label' => 'Meeting Types', 'icon' => 'circle', 'route' => 'meetings.types.index', 'active' => ['meetings.types.*']],
                        ['label' => 'Tags', 'icon' => 'circle', 'route' => 'meetings.tags.index', 'active' => ['meetings.tags.*']],
                    ],
                ],
            ],
        ],

        [
            'label' => 'Obligations',
            'icon' => 'file-earmark-text',
            'active' => ['obligations.*'],
            'children' => [
                ['label' => 'Dashboard', 'icon' => 'speedometer2', 'route' => 'obligations.dashboard'],
                ['label' => 'Obligations', 'icon' => 'journal-check', 'route' => 'obligations.index', 'active' => ['obligations.index', 'obligations.show', 'obligations.create', 'obligations.edit', 'obligations.renew.*']],
                ['label' => 'My Tasks', 'icon' => 'list-task', 'route' => 'obligations.my-tasks'],
                ['label' => 'Calendar', 'icon' => 'calendar3', 'route' => 'obligations.calendar'],
                ['label' => 'Renewals', 'icon' => 'arrow-repeat', 'route' => 'obligations.renewals'],
                ['label' => 'Vendors', 'icon' => 'building', 'route' => 'obligations.vendors'],
                ['label' => 'Documents', 'icon' => 'folder', 'route' => 'obligations.documents'],
                ['label' => 'Notifications', 'icon' => 'bell', 'route' => 'obligations.notifications'],
                ['label' => 'Reports', 'icon' => 'bar-chart-line', 'route' => 'obligations.reports'],
            ],
        ],

        [
            'label' => 'Administration',
            'icon' => 'shield-lock',
            'admin' => true,
            'active' => ['admin.users.*', 'admin.roles.*', 'admin.activity-logs.*', 'admin.audit-logs.*', 'admin.login-logs.*', 'admin.security-events.*'],
            'children' => [
                ['label' => 'Users', 'icon' => 'people', 'route' => 'admin.users.index', 'active' => ['admin.users.*']],
                ['label' => 'Roles', 'icon' => 'person-badge', 'route' => 'admin.roles.index', 'active' => ['admin.roles.*']],
                ['label' => 'Activity Logs', 'icon' => 'journal-text', 'route' => 'admin.activity-logs.index'],
                ['label' => 'Audit Logs', 'icon' => 'shield-check', 'route' => 'admin.audit-logs.index'],
                ['label' => 'Login History', 'icon' => 'clock-history', 'route' => 'admin.login-logs.index'],
                ['label' => 'Security Events', 'icon' => 'shield-exclamation', 'route' => 'admin.security-events.index'],
                ['label' => 'Database Backups', 'icon' => 'database', 'route' => 'dashboard.database-backups.index'],
            ],
        ],
    ],

];
