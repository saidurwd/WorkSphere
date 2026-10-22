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
            'route' => 'filament.workspace.resources.projects.index',
            'active' => ['filament.workspace.resources.projects.*'],
            'permission' => 'project.view',
        ],

        // ---- Work tracking --------------------------------------------------

        [
            'label' => 'To-Dos',
            'icon' => 'check-square',
            'section' => 'Work tracking',
            'active' => ['filament.workspace.resources.todos.*', 'filament.workspace.pages.todo-*', 'filament.workspace.resources.todo-notification-logs.*'],
            'permission' => 'todos.view',
            'children' => [
                ['label' => 'My To-Dos', 'icon' => 'inbox', 'route' => 'filament.workspace.resources.todos.index', 'active' => ['filament.workspace.resources.todos.*']],
                ['label' => 'Calendar', 'icon' => 'calendar3', 'route' => 'filament.workspace.pages.todo-calendar'],
                // `todos.view_all`, NOT `report.view`: this screen reports on
                // every user's To-Dos, which is the `view_all` question, and
                // `report.view` is the permission for one's own completion summary.
                ['label' => 'Reports', 'icon' => 'bar-chart-line', 'route' => 'filament.workspace.pages.todo-reports', 'permission' => 'todos.view_all'],
            ],
        ],

        [
            'label' => 'Tasks',
            'icon' => 'check2-square',
            'active' => ['filament.workspace.resources.tasks.*', 'filament.workspace.pages.task-overview', 'filament.workspace.resources.task-transfers.*'],
            'permission' => 'task.view',
            'children' => [
                ['label' => 'Overview', 'icon' => 'speedometer2', 'route' => 'filament.workspace.pages.task-overview'],
                ['label' => 'All Tasks', 'icon' => 'list-task', 'route' => 'filament.workspace.resources.tasks.index', 'active' => ['filament.workspace.resources.tasks.index', 'filament.workspace.resources.tasks.view', 'filament.workspace.resources.tasks.create', 'filament.workspace.resources.tasks.edit']],
                ['label' => 'Transfers', 'icon' => 'arrow-left-right', 'route' => 'filament.workspace.resources.task-transfers.index', 'permission' => 'task.transfer'],
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
            'active' => ['filament.workspace.resources.meetings.*', 'filament.workspace.resources.meeting-*', 'filament.workspace.pages.meeting-*', 'meetings.print'],
            'permission' => 'meeting.view',
            'children' => [
                ['label' => 'Overview', 'icon' => 'speedometer2', 'route' => 'filament.workspace.pages.meeting-overview'],
                ['label' => 'Register', 'icon' => 'calendar-week', 'route' => 'filament.workspace.resources.meetings.index', 'active' => ['filament.workspace.resources.meetings.*', 'meetings.print']],
                ['label' => 'Calendar', 'icon' => 'calendar3', 'route' => 'filament.workspace.pages.meeting-calendar'],
                ['label' => 'Action Items', 'icon' => 'list-check', 'route' => 'filament.workspace.resources.meeting-action-items.index', 'active' => ['filament.workspace.resources.meeting-action-items.*']],
                ['label' => 'Decisions', 'icon' => 'check2-square', 'route' => 'filament.workspace.resources.meeting-decisions.index', 'active' => ['filament.workspace.resources.meeting-decisions.*']],
                ['label' => 'Templates', 'icon' => 'clipboard-check', 'route' => 'filament.workspace.resources.meeting-templates.index', 'active' => ['filament.workspace.resources.meeting-templates.*'], 'permission' => 'meeting.manage_templates'],
                ['label' => 'Reports', 'icon' => 'bar-chart-line', 'route' => 'filament.workspace.pages.meeting-reports', 'permission' => 'meeting.view_reports'],
                // `Setup` rather than `Configuration`: it is a pair of dropdowns,
                // not a settings surface.
                [
                    'label' => 'Setup',
                    'icon' => 'sliders',
                    'children' => [
                        ['label' => 'Meeting Types', 'icon' => 'tags', 'route' => 'filament.workspace.resources.meeting-types.index', 'active' => ['filament.workspace.resources.meeting-types.*'], 'permission' => 'meeting.manage_types'],
                        ['label' => 'Tags', 'icon' => 'tag', 'route' => 'filament.workspace.resources.meeting-tags.index', 'active' => ['filament.workspace.resources.meeting-tags.*'], 'permission' => 'meeting.manage_tags'],
                    ],
                ],
            ],
        ],

        [
            'label' => 'Obligations',
            'icon' => 'file-earmark-text',
            'active' => ['filament.workspace.resources.obligations.*', 'filament.workspace.resources.obligation-*', 'filament.workspace.resources.vendors.*', 'filament.workspace.pages.obligation-*', 'filament.workspace.pages.my-obligation-tasks'],
            'permission' => 'obligation.view',
            'children' => [
                ['label' => 'Overview', 'icon' => 'speedometer2', 'route' => 'filament.workspace.pages.obligation-overview'],
                ['label' => 'Register', 'icon' => 'journal-check', 'route' => 'filament.workspace.resources.obligations.index', 'active' => ['filament.workspace.resources.obligations.*']],
                ['label' => 'My Tasks', 'icon' => 'list-task', 'route' => 'filament.workspace.pages.my-obligation-tasks'],
                ['label' => 'Calendar', 'icon' => 'calendar3', 'route' => 'filament.workspace.pages.obligation-calendar'],
                ['label' => 'Renewals', 'icon' => 'arrow-repeat', 'route' => 'filament.workspace.resources.obligation-renewals.index'],
                ['label' => 'Vendors', 'icon' => 'building', 'route' => 'filament.workspace.resources.vendors.index'],
                ['label' => 'Documents', 'icon' => 'folder', 'route' => 'filament.workspace.resources.obligation-documents.index', 'permission' => 'obligation.manage_documents'],
                // The only report this module has, so a leaf rather than a branch
                // — but the label matches the other modules' "Reports" so the word
                // means the same thing wherever it appears.
                ['label' => 'Reports', 'icon' => 'bar-chart-line', 'route' => 'filament.workspace.pages.obligation-reports', 'permission' => 'obligation.view_reports'],
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
            'active' => ['filament.admin.pages.system.*', 'filament.admin.resources.feature-flags.*', 'filament.admin.resources.api-tokens.*'],
            'children' => [
                ['label' => 'System Health', 'icon' => 'heart-pulse', 'route' => 'filament.admin.pages.system.health', 'permission' => 'system.health'],
                ['label' => 'Settings', 'icon' => 'sliders', 'route' => 'filament.admin.pages.system.settings', 'permission' => 'system.settings'],
                ['label' => 'Queue & Jobs', 'icon' => 'list-task', 'route' => 'filament.admin.pages.system.queue', 'permission' => 'system.queue'],
                ['label' => 'Scheduled Tasks', 'icon' => 'calendar-week', 'route' => 'filament.admin.pages.system.schedule', 'permission' => 'system.schedule'],
                ['label' => 'Feature Flags', 'icon' => 'toggle2-on', 'route' => 'filament.admin.resources.feature-flags.index', 'permission' => 'system.flags'],
                ['label' => 'API Tokens', 'icon' => 'key', 'route' => 'filament.admin.resources.api-tokens.index', 'permission' => 'system.tokens'],
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
                ['label' => 'To-Do Notifications', 'icon' => 'check2-square', 'route' => 'filament.workspace.resources.todo-notification-logs.index', 'active' => ['filament.workspace.resources.todo-notification-logs.*'], 'permission' => 'todos.view_all'],
                ['label' => 'Task Notifications', 'icon' => 'list-task', 'route' => 'filament.workspace.resources.task-notification-logs.index', 'active' => ['filament.workspace.resources.task-notification-logs.*'], 'permission' => 'task.view_notification_logs'],
                ['label' => 'Meeting Notifications', 'icon' => 'journal-text', 'route' => 'filament.workspace.resources.meeting-notification-logs.index', 'active' => ['filament.workspace.resources.meeting-notification-logs.*'], 'permission' => 'meeting.view_notification_logs'],
                ['label' => 'Obligation Notifications', 'icon' => 'file-earmark-text', 'route' => 'filament.workspace.resources.obligation-notifications.index', 'active' => ['filament.workspace.resources.obligation-notifications.*'], 'permission' => 'obligation.view_notification_logs'],
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
            'active' => ['filament.admin.resources.*', 'dashboard.database-backups.*'],
            'children' => [
                [
                    'label' => 'People',
                    'icon' => 'people',
                    'children' => [
                        ['label' => 'Users', 'icon' => 'person', 'route' => 'filament.admin.resources.users.index', 'active' => ['filament.admin.resources.users.*'], 'permission' => 'user.manage'],
                        ['label' => 'Roles', 'icon' => 'person-badge', 'route' => 'filament.admin.resources.roles.index', 'active' => ['filament.admin.resources.roles.*'], 'permission' => 'role.manage'],
                        // Employees, Companies, Departments and Locations are one
                        // controller behind a `{resource}` parameter, so each needs
                        // its own node — they are four screens, not one.
                        ['label' => 'Employees', 'icon' => 'person-vcard', 'route' => 'filament.admin.resources.employees.index', 'active' => ['filament.admin.resources.employees.*'], 'permission' => 'user.manage'],
                        ['label' => 'Companies', 'icon' => 'building', 'route' => 'filament.admin.resources.companies.index', 'active' => ['filament.admin.resources.companies.*'], 'permission' => 'user.manage'],
                        ['label' => 'Departments', 'icon' => 'diagram-3', 'route' => 'filament.admin.resources.departments.index', 'active' => ['filament.admin.resources.departments.*'], 'permission' => 'user.manage'],
                        ['label' => 'Locations', 'icon' => 'geo-alt', 'route' => 'filament.admin.resources.locations.index', 'active' => ['filament.admin.resources.locations.*'], 'permission' => 'user.manage'],
                    ],
                ],

                [
                    'label' => 'Logs',
                    'icon' => 'journal-text',
                    'children' => [
                        ['label' => 'Activity Logs', 'icon' => 'list-check', 'route' => 'filament.admin.resources.activity-logs.index', 'active' => ['filament.admin.resources.activity-logs.*'], 'permission' => 'activity.view'],
                        ['label' => 'Audit Logs', 'icon' => 'shield-check', 'route' => 'filament.admin.resources.audit-logs.index', 'active' => ['filament.admin.resources.audit-logs.*']],
                        ['label' => 'Login History', 'icon' => 'clock-history', 'route' => 'filament.admin.resources.login-logs.index', 'active' => ['filament.admin.resources.login-logs.*']],
                        ['label' => 'Security Events', 'icon' => 'shield-exclamation', 'route' => 'filament.admin.resources.security-events.index', 'active' => ['filament.admin.resources.security-events.*']],
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
