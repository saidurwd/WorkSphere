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
    |   super_admin bool          Restrict to the `super-admin` role specifically,
    |                              for a screen whose controller checks the ROLE
    |                              rather than a permission.
    |   section     string        Heading emitted above the first node carrying it.
    |
    | ---- A node's `permission` MUST BE THE ONE ITS CONTROLLER CHECKS ----------
    |
    | A node gated on a permission the controller does not check advertises a
    | screen that 403s, and a node gated on nothing that the controller does check
    | hides a screen that works. Both were true here:
    |
    | - `To-Dos > Reports` was gated on `report.view`, but `TodoReportController`
    |   authorizes `todo.view_all`. A user with both was shown a link to a 403.
    | - `Tasks > Reports > Workload` was gated on the branch's `report.view`, but
    |   `ReportController::taskWorkload()` authorizes `task.view_all` — a
    |   deliberately different permission, because that report is about other
    |   people's work.
    | - `Obligations > Reports` was gated on `obligation.view_reports`, which
    |   `ObligationReportController` never checks at all.
    |
    | `NavigationStructureTest::test_every_nav_node_names_a_permission_its_route_actually_checks`
    | holds this shut from now on.
    |
    | ---- Four rules the previous tree broke, now enforced ---------------------
    |
    | 1. Every leaf's label is distinct from its parent's. "Obligations >
    |    Obligations" reads as a stutter and makes the parent unclickable in
    |    meaning rather than only in appearance.
    | 2. Icons are meaningful. A third-level leaf used to carry `circle`, which is
    |    the absence of an icon, not a choice of one.
    | 3. Every screen is reachable. Four screens had routes, tests, and no way to
    |    be found: My Work, Task Reports, Meeting Templates and the identity
    |    Reference Data. Phase 8 built the last two and nothing ever linked them.
    | 4. Reports are a BRANCH everywhere, never a leaf. Tasks had a Reports
    |    branch, To-Dos and Obligations a flat leaf, Meetings a branch with seven
    |    children — so the same word sat at two depths in the same menu, and
    |    whether "Reports" was expandable depended on which module you were in.
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
                // `todos.view_all`, NOT `report.view`: this screen reports on
                // every user's To-Dos, which is the `view_all` question, and
                // `report.view` is the permission for one's own completion summary.
                ['label' => 'Reports', 'icon' => 'bar-chart-line', 'route' => 'todos.reports', 'permission' => 'todos.view_all'],
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
                // No permission on the branch: the two children disagree about
                // what they need. Completion is a personal summary (`report.view`)
                // and Workload reports on other people (`task.view_all`). A gate
                // here would either hide Workload from a manager who has it, or
                // show Completion to someone who does not.
                [
                    'label' => 'Reports',
                    'icon' => 'bar-chart-line',
                    'active' => ['reports.tasks', 'reports.workload'],
                    'children' => [
                        ['label' => 'Completion', 'icon' => 'check2-circle', 'route' => 'reports.tasks', 'permission' => 'report.view'],
                        ['label' => 'Workload', 'icon' => 'people', 'route' => 'reports.workload', 'permission' => 'task.view_all'],
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
                // The only report this module has, so a leaf rather than a branch
                // — but the label matches the other modules' "Reports" so the word
                // means the same thing wherever it appears.
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
                ['label' => 'System Health', 'icon' => 'heart-pulse', 'route' => 'admin.system.health.index', 'permission' => 'system.health'],
                ['label' => 'Settings', 'icon' => 'sliders', 'route' => 'admin.system.settings.index', 'permission' => 'system.settings'],
                ['label' => 'Queue & Jobs', 'icon' => 'list-task', 'route' => 'admin.system.queue.index', 'permission' => 'system.queue'],
                ['label' => 'Scheduled Tasks', 'icon' => 'calendar-week', 'route' => 'admin.system.schedule.index', 'permission' => 'system.schedule'],
                ['label' => 'Feature Flags', 'icon' => 'toggle2-on', 'route' => 'admin.system.flags.index', 'permission' => 'system.flags'],
                ['label' => 'API Tokens', 'icon' => 'key', 'route' => 'admin.system.tokens.index', 'permission' => 'system.tokens'],
            ],
        ],

        // ---- Diagnostics ----------------------------------------------------
        // The four per-module notification delivery logs, together, because they
        // answer one question — "did the system try to tell someone, and did it
        // work?" — and an operator chasing a missing notification checks all four
        // in the same sitting. Scattered as `Notification Logs` beside `All Tasks`
        // and `Register`, they read as a working screen rather than a diagnostic,
        // and they sat at two different depths in two different modules.
        //
        // Each keeps its OWN permission rather than sharing one. A To-Do operator
        // diagnosing a failed assignment has no reason to read the obligation
        // delivery log, and one shared permission would hand them both.
        //
        // `admin` rather than a permission on the parent: the branch is a grouping,
        // not a capability. Each child states what it needs.

        [
            'label' => 'Diagnostics',
            'icon' => 'clipboard-data',
            'section' => 'Diagnostics',
            'admin' => true,
            'children' => [
                ['label' => 'To-Do Notifications', 'icon' => 'check2-square', 'route' => 'todos.notification-logs.index', 'active' => ['todos.notification-logs.*'], 'permission' => 'todos.view_all'],
                ['label' => 'Task Notifications', 'icon' => 'list-task', 'route' => 'tasks.notification-logs.index', 'active' => ['tasks.notification-logs.*'], 'permission' => 'task.view_notification_logs'],
                ['label' => 'Meeting Notifications', 'icon' => 'journal-text', 'route' => 'meetings.notification-logs.index', 'active' => ['meetings.notification-logs.*'], 'permission' => 'meeting.view_notification_logs'],
                ['label' => 'Obligation Notifications', 'icon' => 'file-earmark-text', 'route' => 'obligations.notifications', 'active' => ['obligations.notifications*'], 'permission' => 'obligation.view_notification_logs'],
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
                        ['label' => 'Employees', 'icon' => 'person-vcard', 'route' => 'admin.reference.index', 'active' => ['admin.reference.*'], 'permission' => 'user.manage', 'params' => ['resource' => 'employees']],
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
                        // `super_admin`, NOT `database.backup`: the controller
                        // authorizes `super-admin-only`, a ROLE check. The seeded
                        // `database.backup` permission gates nothing, so naming it
                        // here advertised the screen to every `admin` and refused it
                        // to all of them.
                        ['label' => 'Database Backups', 'icon' => 'database', 'route' => 'dashboard.database-backups.index', 'active' => ['dashboard.database-backups.*'], 'super_admin' => true],
                    ],
                ],
            ],
        ],
    ],

];
