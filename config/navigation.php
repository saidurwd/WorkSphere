<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Sidebar Menu
    |--------------------------------------------------------------------------
    |
    | The single declarative tree behind `resources/views/components/sidebar`.
    |
    | ORDER IS BY HOW PEOPLE WORK, NOT BY WHICH TABLE IT IS IN. A user opening
    | this application has a question — "what is mine, what is late, what is due"
    | — and the menu answers it top-down: my workspace, then the things I own, then
    | the things I administer. Reading order follows the order of a working day.
    |
    | Each node accepts:
    |
    |   label       string        Text shown in the menu.
    |   icon        string        Bootstrap Icons name, without the `bi bi-` prefix.
    |   route       string|null   Named route. Required for leaves; a parent that
    |                              only expands a submenu leaves this null.
    |   children    array         Nested nodes, rendered recursively.
    |   active      array         Route name patterns that mark this node as the
    |                              current location. A leaf with no `active` uses
    |                              its own route name.
    |   permission  string|null   Permission required to SEE the node.
    |   permissions array         Permission set, ANY of which shows the node.
    |   admin       bool          Restrict to users holding an admin role.
    |
    | Three rules that the previous tree broke, and that are now enforced by
    | `NavigationStructureTest`:
    |
    | 1. Every leaf's label is distinct from its parent's. "Obligations >
    |    Obligations" reads as a stutter and makes the parent unclickable in
    |    meaning rather than only in appearance.
    | 2. Icons are meaningful. A third-level leaf used to carry `circle`, which is
    |    the absence of an icon, not a choice of one.
    | 3. Every screen is reachable. Four screens had routes, tests, and no way to
    |    be found: My Work, Task Reports, Meeting Templates and the identity
    |    Reference Data. Phase 8 built the last two and nothing ever linked them.
    |
    */

    'menu' => [
        // ---- My workspace ---------------------------------------------------
        // Ungated on purpose: these answer "what is mine", and every user has an
        // answer even when every module below is hidden from them.

        [
            'label' => 'Dashboard',
            'icon' => 'grid-1x2',
            'route' => 'dashboard.index',
            'active' => ['dashboard.index', 'dashboard.ui-kit'],
        ],

        [
            'label' => 'My Work',
            'icon' => 'person-workspace',
            'route' => 'my-work',
        ],

        [
            'label' => 'Projects',
            'icon' => 'folder2-open',
            'route' => 'projects.index',
            'active' => ['projects.*'],
            'permission' => 'project.view',
        ],

        // ---- Work tracking --------------------------------------------------

        [
            'label' => 'To-Dos',
            'icon' => 'check-square',
            'section' => 'Work tracking',
            'active' => ['todos.*'],
            'permission' => 'todos.view',
            'children' => [
                ['label' => 'My To-Dos', 'icon' => 'inbox', 'route' => 'todos.index', 'active' => ['todos.index', 'todos.show', 'todos.create', 'todos.edit']],
                ['label' => 'Inbox', 'icon' => 'download', 'route' => 'todos.inbox'],
                ['label' => 'Calendar', 'icon' => 'calendar3', 'route' => 'todos.calendar'],
                ['label' => 'Reports', 'icon' => 'bar-chart', 'route' => 'todos.reports', 'permission' => 'report.view'],
            ],
        ],

        [
            'label' => 'Tasks',
            'icon' => 'check2-square',
            'active' => ['tasks.*', 'task-transfers.*'],
            'permission' => 'task.view',
            'children' => [
                ['label' => 'Overview', 'icon' => 'speedometer2', 'route' => 'tasks.dashboard'],
                ['label' => 'All Tasks', 'icon' => 'list-task', 'route' => 'tasks.index', 'active' => ['tasks.index', 'tasks.show', 'tasks.create', 'tasks.edit']],
                ['label' => 'Transfers', 'icon' => 'arrow-left-right', 'route' => 'task-transfers.index', 'permission' => 'task.transfer'],
                // Diagnostic, but reachable before this reorganisation and therefore
                // still reachable. Removing a working entry is a regression.
                ['label' => 'Notification Logs', 'icon' => 'bell', 'route' => 'tasks.notification-logs.index', 'active' => ['tasks.notification-logs.*']],
                [
                    'label' => 'Reports',
                    'icon' => 'bar-chart-line',
                    'active' => ['reports.tasks', 'reports.workload'],
                    'permission' => 'report.view',
                    'children' => [
                        ['label' => 'Completion', 'icon' => 'check2-circle', 'route' => 'reports.tasks'],
                        ['label' => 'Workload', 'icon' => 'people', 'route' => 'reports.workload'],
                    ],
                ],
            ],
        ],

        // ---- Governance -----------------------------------------------------

        [
            'label' => 'Meetings',
            'icon' => 'journal-text',
            'section' => 'Governance',
            'active' => ['meetings.*'],
            'permission' => 'meeting.view',
            'children' => [
                ['label' => 'Overview', 'icon' => 'speedometer2', 'route' => 'meetings.dashboard'],
                ['label' => 'Register', 'icon' => 'calendar-week', 'route' => 'meetings.index', 'active' => ['meetings.index', 'meetings.show', 'meetings.create', 'meetings.edit', 'meetings.print']],
                ['label' => 'Calendar', 'icon' => 'calendar3', 'route' => 'meetings.calendar'],
                ['label' => 'Action Items', 'icon' => 'list-check', 'route' => 'meetings.action-items.index', 'active' => ['meetings.action-items.*']],
                ['label' => 'Notification Logs', 'icon' => 'bell', 'route' => 'meetings.notification-logs.index', 'active' => ['meetings.notification-logs.*']],
                // Wired in Phase 8 and reachable only by typing the URL until now.
                ['label' => 'Templates', 'icon' => 'clipboard-check', 'route' => 'meetings.templates.index', 'active' => ['meetings.templates.*'], 'permission' => 'meeting.manage_templates'],
                [
                    'label' => 'Reports',
                    'icon' => 'bar-chart-line',
                    'active' => ['meetings.reports.*'],
                    'permission' => 'meeting.view_reports',
                    'children' => [
                        ['label' => 'Overview', 'icon' => 'grid', 'route' => 'meetings.reports.index'],
                        ['label' => 'Meetings', 'icon' => 'calendar-week', 'route' => 'meetings.reports.meetings'],
                        ['label' => 'Action Items', 'icon' => 'list-check', 'route' => 'meetings.reports.actions'],
                        ['label' => 'Overdue Actions', 'icon' => 'exclamation-triangle', 'route' => 'meetings.reports.overdue'],
                        ['label' => 'Person Wise', 'icon' => 'person', 'route' => 'meetings.reports.person-wise'],
                        ['label' => 'Department Wise', 'icon' => 'diagram-3', 'route' => 'meetings.reports.department-wise'],
                        ['label' => 'Decisions', 'icon' => 'check2-square', 'route' => 'meetings.reports.decisions'],
                    ],
                ],
                // `Setup` rather than `Configuration`: it is a pair of dropdowns,
                // not a settings surface, and the previous name implied pages that
                // do not exist.
                [
                    'label' => 'Setup',
                    'icon' => 'sliders',
                    'children' => [
                        ['label' => 'Meeting Types', 'icon' => 'tags', 'route' => 'meetings.types.index', 'active' => ['meetings.types.*'], 'permission' => 'meeting.manage_types'],
                        ['label' => 'Tags', 'icon' => 'tag', 'route' => 'meetings.tags.index', 'active' => ['meetings.tags.*'], 'permission' => 'meeting.manage_tags'],
                    ],
                ],
            ],
        ],

        [
            'label' => 'Obligations',
            'icon' => 'file-earmark-text',
            'active' => ['obligations.*'],
            'permission' => 'obligation.view',
            'children' => [
                ['label' => 'Overview', 'icon' => 'speedometer2', 'route' => 'obligations.dashboard'],
                ['label' => 'Register', 'icon' => 'journal-check', 'route' => 'obligations.index', 'active' => ['obligations.index', 'obligations.show', 'obligations.create', 'obligations.edit', 'obligations.renew.*']],
                ['label' => 'My Tasks', 'icon' => 'list-task', 'route' => 'obligations.my-tasks'],
                ['label' => 'Calendar', 'icon' => 'calendar3', 'route' => 'obligations.calendar'],
                ['label' => 'Renewals', 'icon' => 'arrow-repeat', 'route' => 'obligations.renewals'],
                ['label' => 'Vendors', 'icon' => 'building', 'route' => 'obligations.vendors'],
                ['label' => 'Documents', 'icon' => 'folder', 'route' => 'obligations.documents', 'permission' => 'obligation.manage_documents'],
                ['label' => 'Reports', 'icon' => 'bar-chart-line', 'route' => 'obligations.reports', 'permission' => 'obligation.view_reports'],
            ],
        ],

        // ---- System ---------------------------------------------------------
        // Its own section rather than a fourth group under Administration. These are
        // cross-cutting concerns — the health of the application, its configuration,
        // its background work — and an operator diagnosing an incident looks for
        // them together, not interleaved with the user list. Each screen carries its
        // OWN permission, so seeing the health of the system does not imply being
        // able to change how it behaves.

        [
            'label' => 'System',
            'icon' => 'gear-wide-connected',
            'section' => 'System',
            'admin' => true,
            'active' => ['admin.system.*'],
            'children' => [
                ['label' => 'Health', 'icon' => 'heart-pulse', 'route' => 'admin.system.health.index', 'permission' => 'system.health'],
                ['label' => 'Settings', 'icon' => 'sliders', 'route' => 'admin.system.settings.index', 'permission' => 'system.settings'],
                ['label' => 'Queue & Jobs', 'icon' => 'list-task', 'route' => 'admin.system.queue.index', 'permission' => 'system.queue'],
                ['label' => 'Scheduled Tasks', 'icon' => 'calendar-week', 'route' => 'admin.system.schedule.index', 'permission' => 'system.schedule'],
                ['label' => 'Feature Flags', 'icon' => 'toggle2-on', 'route' => 'admin.system.flags.index', 'permission' => 'system.flags'],
                ['label' => 'API Tokens', 'icon' => 'key', 'route' => 'admin.system.tokens.index', 'permission' => 'system.tokens'],
            ],
        ],

        // ---- Administration -------------------------------------------------
        // Grouped by WHAT IS BEING ADMINISTERED, not by which table. The previous
        // tree put people, four log tables and the database backup in one flat
        // list, which is neither a reading order nor a mental model — and it hid
        // the identity Reference Data entirely, even though Phase 8 built CRUD for
        // employees, companies, departments and locations and wired no route to it.

        [
            'label' => 'Administration',
            'icon' => 'shield-lock',
            'section' => 'Administration',
            'admin' => true,
            'active' => ['admin.*', 'dashboard.database-backups.*'],
            'children' => [
                [
                    'label' => 'People',
                    'icon' => 'people',
                    'children' => [
                        ['label' => 'Users', 'icon' => 'person', 'route' => 'admin.users.index', 'active' => ['admin.users.*'], 'permission' => 'user.manage'],
                        ['label' => 'Roles', 'icon' => 'person-badge', 'route' => 'admin.roles.index', 'active' => ['admin.roles.*'], 'permission' => 'role.manage'],
                        // Employees, Companies, Departments and Locations are one
                        // controller behind a `{resource}` parameter, so each needs
                        // its own node — they are four screens, not one.
                        ['label' => 'Employees', 'icon' => 'id-badge', 'route' => 'admin.reference.index', 'active' => ['admin.reference.*'], 'permission' => 'user.manage', 'params' => ['resource' => 'employees']],
                        ['label' => 'Companies', 'icon' => 'building', 'route' => 'admin.reference.index', 'active' => ['admin.reference.*'], 'permission' => 'user.manage', 'params' => ['resource' => 'companies']],
                        ['label' => 'Departments', 'icon' => 'diagram-3', 'route' => 'admin.reference.index', 'active' => ['admin.reference.*'], 'permission' => 'user.manage', 'params' => ['resource' => 'departments']],
                        ['label' => 'Locations', 'icon' => 'geo-alt', 'route' => 'admin.reference.index', 'active' => ['admin.reference.*'], 'permission' => 'user.manage', 'params' => ['resource' => 'locations']],
                    ],
                ],

                [
                    'label' => 'Logs',
                    'icon' => 'journal-text',
                    'children' => [
                        ['label' => 'Activity Logs', 'icon' => 'list-check', 'route' => 'admin.activity-logs.index', 'active' => ['admin.activity-logs.*'], 'permission' => 'activity.view'],
                        ['label' => 'Audit Logs', 'icon' => 'shield-check', 'route' => 'admin.audit-logs.index', 'active' => ['admin.audit-logs.*']],
                        ['label' => 'Login History', 'icon' => 'clock-history', 'route' => 'admin.login-logs.index', 'active' => ['admin.login-logs.*']],
                        ['label' => 'Security Events', 'icon' => 'shield-exclamation', 'route' => 'admin.security-events.index', 'active' => ['admin.security-events.*']],
                    ],
                ],

                [
                    'label' => 'Infrastructure',
                    'icon' => 'server',
                    'children' => [
                        ['label' => 'Database Backups', 'icon' => 'database', 'route' => 'dashboard.database-backups.index', 'active' => ['dashboard.database-backups.*'], 'permission' => 'database.backup'],
                    ],
                ],
            ],
        ],
    ],

];
