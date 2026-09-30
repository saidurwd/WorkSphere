# Architecture Assessment — WorkSphere

> Phase 0/1 deliverable. Evidence-based assessment of the existing application.
> Generated from full inspection of migrations, models, controllers, routes, views,
> config and the test suite. **No code was modified.**

---

## 1. Executive Summary

WorkSphere is a **mature-featured but structurally fragile** Laravel 13 work-management
platform. Functionally it is far along: Tasks, Meetings, Obligations and Projects each
have full CRUD, notification pipelines, reporting and calendars. The **business domain
is well built**.

The **engineering platform underneath is not**. Three structural problems dominate:

1. **No authorization layer exists.** There are zero Policies, zero Gates, zero
   `$this->authorize()` calls in the entire codebase. A 53-permission RBAC graph is
   seeded into the database and **never read by any authorization decision**. Access
   control collapses to two hardcoded role slugs plus ad-hoc ownership `abort(403)` calls
   duplicated across six controllers — and Meetings and Obligations have **no
   object-level check at all**, so any authenticated user can edit any record by ID.

2. **There is effectively no test suite and no safety net for change.** 8 tests exist.
   1 passes, 1 fails, 2 error, 4 skip. Critically, **the migration set cannot run on
   SQLite** (raw `ALTER TABLE ... ENUM`, `->after()`, `information_schema` queries), so
   the standard in-memory test database is impossible. Every feature in this app is
   currently unverifiable by automated tests.

3. **The module architecture is nominal only.** `nwidart/laravel-modules` is installed and
   four modules exist, but every migration lives in `database/migrations`, every route
   lives in `routes/web.php`, and every module's `routes/web.php` and `routes/api.php`
   are comment-only stubs. Module boundaries exist on disk but not in the dependency graph.

**Recommendation: modular monolith — confirmed as the right target.** Continue with
`nwidart/laravel-modules`, but make the boundaries real (Phase 2) *before* adding the
To-Do module. Do not adopt spatie/laravel-permission; build a thin policy layer on the
`Role`/`Permission` tables that already exist.

---

## 2. Current Architecture

### 2.1 Stack

| Layer | Technology | Version |
|---|---|---|
| Framework | Laravel | 13.29.0 |
| Runtime | PHP | ^8.3 (8.4 target) |
| Modules | `nwidart/laravel-modules` | 13.0.0 |
| API auth | `laravel/sanctum` | 4.3.3 — **installed, entirely unused** |
| Tables | `yajra/laravel-datatables` | 13.0.0 — **installed, never invoked** |
| Database | MySQL (strict, utf8mb4) + a legacy `sqlsrv` connection | — |
| Queue / Cache / Session | `database` driver (Redis configured but unused) | — |
| Frontend | AdminLTE 4 + Bootstrap 5 + Alpine 3 + vanilla JS + SweetAlert2 + TomSelect + flatpickr + DataTables 3 + FullCalendar 6 | — |
| Tests | PHPUnit 12 | 8 tests |

**Not present** and deliberately so: Livewire, Inertia, Vue, React, Reverb/Pusher,
spatie/laravel-permission, Elasticsearch.

### 2.2 Module Layout

```
Modules/
├── Tasks/        Models, Controllers, Events(4), Listeners(3), Jobs(5), Mail(5),
│                 Services(1), Console(2)
├── Meetings/     Models(15), Controllers(11), Events(13), Listeners(9), Jobs(10),
│                 Mail(10), Services(8), Console(2)   ← richest module
├── Obligations/  Models(14), Controllers(11), Mail(2), Services(4), Console(1)
└── Projects/     Models(1), Controllers(1)            ← thin scaffold
```

Every module has `app/{Console,Events,Http,Jobs,Listeners,Mail,Models,Providers,Services}`,
`database/{factories,migrations,seeders}`, `resources/views`, `routes/{web,api}`,
`tests/{Feature,Unit}`.

**Reality check on what is wired:**

| Aspect | Where it actually lives |
|---|---|
| Migrations (68 files) | **All** in `database/migrations/`. Every `Modules/*/database/migrations/` is empty. |
| Routes (~120) | **All** in `routes/web.php`, which imports `Modules\*\Http\Controllers\*` directly (lines 14–41). |
| Module `routes/web.php` | Comment-only stub, e.g. `Modules/Tasks/routes/web.php:5-10` says routes stay in `routes/web.php` "until this file takes ownership". |
| Module `routes/api.php` | Comment-only stub — and the module `RouteServiceProvider` registers `prefix('api')` inside the module, which would yield `/api/api/…` if ever used. |
| Views | **All** in `resources/views/{tasks,meetings,obligations,projects}`. Module `resources/views` are empty. |

So: **the modules are a folder convention, not an architectural boundary.** Controllers
in a module reach into `App\Models` and each other freely.

### 2.3 Request Lifecycle

```
routes/web.php (auth) ──► EnsureUserIsAdmin (admin routes only)
        │
        ▼
Controller ──inline $request->validate()──► Eloquent
        │                                        │
        │                                   Event::dispatch (Tasks, Meetings only)
        │                                        ▼
        │                              ShouldQueue Listener ──► ShouldQueue Job
        │                                                          ▼
        │                                              Mail::to()->send(Mailable)
        │                                                          ▼
        │                                              *NotificationLog::create()
        ▼
Blade view (server-side ->paginate(), then client-side DataTables applied over it)
```

Obligations deviate: `NotificationService` and `EscalationService` call
`Mail::to()->send()` **synchronously**, inside `obligations:process`, a daily 08:00
scheduled command. A slow SMTP server stalls the scheduler.

### 2.4 Data Model Shape

68 migrations, ~60 tables, all on the default `mysql` connection except `tblAccountInfo`
(on `sqlsrv`).

```
users ──┬── user_roles ── roles ── role_permissions ── permissions
        ├── tasks.user_id (NOT NULL, CASCADE DELETE)
        ├── tasks.responsible_user_id (nullable)
        ├── task_projects.user_id
        ├── obligations.owner_user_id / backup_ / reviewer_ / approver_ / created_by
        ├── meetings.organizer_id (CASCADE) / chairperson_id / approved_by
        ├── meeting_participants.user_id (CASCADE)
        └── login_logs, activity_logs, tyro_audit_logs

employees ── departments.head_of_department_id ;  users.employee_id (nullable)
locations  ── employees.location_id ; obligations.location_id

tasks ──┬── task_remarks
        ├── task_transfers
        ├── task_projects (via project_id)
        ├── obligations (obligation_id)
        └── meeting_action_items.task_id

meetings ──┬── meeting_participants / _agendas / _discussions / _decisions
           ├── meeting_action_items ──► tasks
           ├── meeting_attachments / _recurrences / _tags / _tag_map
           ├── meeting_versions / _templates / _template_agendas
           └── meeting_minutes_approvals

obligations ──┬── obligation_responsibilities / _renewals / _documents
              ├── obligation_activity_logs   ← the only live audit trail
              ├── notification_rules / notification_logs (global)
              ├── escalation_rules (by obligation_type_id only)
              └── tasks
```

---

## 3. Strengths — Do Not Change

These are genuinely good and should be preserved verbatim.

1. **Domain depth is real.** Meetings have agenda → discussion → decision → action item →
   task linkage, minutes workflow (`draft/prepared/submitted/under_review/approved/
   published`) with `meeting_minutes_approvals`, meeting templates, tagging, versioning,
   recurrence rules, action-item↔task linking, and 7 report endpoints. Obligations have
   risk levels, escalation rules, renewals with vendor/cost/invoice references, documents,
   multi-user responsibilities with escalation levels, and approval workflows. This is
   genuine enterprise modelling, not scaffolding.

2. **The notification pipeline shape is right.** Domain event → `ShouldQueue` listener →
   `ShouldQueue` job → Mailable → delivery log row. Correct separation, correctly queued,
   with an audit row per send. Tasks and Meetings both do this consistently. The *shape*
   is right; only Obligations bypasses it.

3. **Security fundamentals are mostly correct:**
   - Password hashing via the `hashed` cast — no `Hash::make` anywhere in app code.
   - Session fixation handled (`regenerate()` on login, `invalidate()` + `regenerateToken()`
     on logout).
   - Login throttling: 5 attempts / 60 s, keyed on transliterated email + IP, dispatches
     `Lockout`.
   - `session.serialization => 'json'` and `cache.serializable_classes => false` — gadget-chain
     deserialization closed off. Deliberate and good.
   - All Blade output escaped with `{{ }}`; no `{!! !!}` on user data found.
   - All raw SQL is static literals with `?` placeholders — fully parameterised.
   - CSRF active on all web routes with **no** exceptions configured.
   - Backup filename whitelisting (`[a-zA-Z0-9_\-.]`) blocks path traversal.
   - Enum filters are whitelisted via `in_array(...)` rather than passed through to `where()`.

4. **The Blade component library is genuinely reusable** — 31 components including a
   single `form/input.blade.php` that handles text/textarea/select/checkbox with
   automatic `is-invalid` + `@error`, plus `badge`, `modal`, `datatable`, `alert`,
   `empty-state`, `pagination`, `stat`. This is a real design system, not per-page markup.

5. **`config/navigation.php`** is a clean declarative menu tree consumed generically by
   `sidebar.blade.php` — adding a To-Do section is a config edit, not a view rewrite.

6. **Meeting and obligation reporting services** (`MeetingReportService`,
   `ObligationReportController`) do proper aggregate queries rather than loading rows
   into PHP.

7. **Legacy integration is isolated.** `tblAccountInfo` (sqlsrv) is cleanly separated and,
   importantly, **entirely unreferenced** — it did not contaminate the domain.

---

## 4. Weaknesses

### 4.1 Critical

| # | Issue | Evidence |
|---|---|---|
| W1 | **No authorization layer at all.** 0 Gates, 0 Policies, 0 `$this->authorize()`, 0 `@can`, 0 `Gate::allows/denies` across `app/`, `Modules/`, `bootstrap/`, `config/`, `routes/`. No `app/Policies/`. No `AuthServiceProvider`. | repo-wide grep |
| W2 | **The 53 seeded permissions are decorative.** `ProjectPermissionSeeder` seeds 53 permission strings; `admin/roles/show.blade.php` renders them; nothing enforces them. | `database/seeders/ProjectPermissionSeeder.php:14-67` |
| W3 | **IDOR on Meetings and Obligations.** `MeetingController` / `ObligationController` have **no object-level authorization** on `show/edit/update/destroy`. Any authenticated user can act on any record by ID, even if the list query scopes it away. | `Modules/Meetings/.../MeetingController.php`, `Modules/Obligations/.../ObligationController.php` |
| W4 | **Audit logging is non-functional.** `tyro_audit_logs` has **zero write call sites** in the whole repo — `AuditLogController` only reads. `activity_logs` is written by exactly one seeder loop (`FoundationSeeder.php:102-111`). `admin.audit-logs` and `admin.activity-logs` will always render empty. | repo-wide grep for `AuditLog::create` |
| W5 | **`LoginRequest::recordFailure()` is fatally broken** — missing `use App\Models\LoginLog;` and `use App\Services\LoginLogService;`. Resolves to `App\Http\Requests\Auth\LoginLog` → fatal on **every failed login**. Security-event logging is dead exactly when it matters. | `app/Http/Requests/Auth/LoginRequest.php:88-97` |
| W6 | **Migrations cannot run on SQLite.** Verified: `DB_CONNECTION=sqlite php artisan migrate` fails at `2026_07_09_070842_alter_departments_head_and_status.php:25` (raw `ALTER TABLE … ENUM`). Root causes: `ENUM()` columns, `->after()`, `DB::statement` ALTERs, `information_schema` queries. **This blocks the standard in-memory test database and therefore all automated testing.** | verified by running migrate |
| W7 | **`create_role_permissions` FK ordering bug.** `2026_07_09_000026` applies `constrained('roles')`, but `roles` is not created until `2026_09_29_152053` — 3 months later. A fresh migrate on an empty MySQL DB fails. Works today only because `roles` already exists in the live DB. | migration timestamps |

### 4.2 High

| # | Issue | Evidence |
|---|---|---|
| W8 | **`User::tasks()`, `User::responsibleTasks()`, `User::projects()` reference `Task::class` / `Project::class` without imports** → resolve to non-existent `App\Models\Task` / `App\Models\Project`. Any eager-load of these relations fatals. | `app/Models/User.php:72,77,82` |
| W9 | **`users.status` is not fillable.** `UserController::store()` sets `$userData['status']` then `User::create()` silently discards it. Validation demands it, DB accepts it, model drops it. Same class of bug: `Role.description`. | `app/Models/User.php:16` + `UserController.php:70,77` |
| W10 | **`User::employee` relation does not exist**, yet `UserController::show()` eager-loads it and `admin/users/show.blade.php:43-44` references it plus `route('employees.show')` — a route that does not exist. | `UserController.php:88`, `admin/users/show.blade.php:43` |
| W11 | **`SecurityEventController` overwrites `$events`** (line 32 assigns the distinct event list, line 34 overwrites with the paginator). The "All Events" filter renders model objects as `<option>` values. | `app/Http/Controllers/Admin/SecurityEventController.php:32,34` |
| W12 | **Silent mass-assignment discarding project-wide.** Neither `User` nor `Role` calls `preventSilentlyDiscardingAttributes()`, so W9-class bugs are invisible. | `app/Providers/AppServiceProvider.php` |
| W13 | **`tasks.user_id` is `cascadeOnDelete`** — deleting a user silently destroys their tasks. Same for `meetings.organizer_id`, `meeting_minutes_approvals.approver_id`, `obligations.owner_user_id`. Audit trails vanish with their subjects. | migrations |
| W14 | **Zero indexes on `activity_logs`** beyond the FK. `module_name`, `record_id`, `action` are all filter dimensions — every admin log screen full-scans. | `2026_07_09_000028` |
| W15 | **`obligation_renewals`, `obligation_documents`, `notification_rules`, `escalation_rules` have no indexes at all.** `tasks` has no standalone index on `status` or `due_date`. `meeting_recurrences` has no index on `next_occurrence` — the column the occurrence generator needs. | migrations |
| W16 | **`SESSION_SECURE_COOKIE` unset** → session cookie sent over plain HTTP. | `config/session.php:172`, `.env` |
| W17 | **Live SMTP credential in the working tree** (`.env:55-56`, plus an untracked `.env.backup`). Not committed, but a real Office 365 password on disk. | `.env` |
| W18 | **Uploaded files land on the `public` disk** despite `config/uploads.php` defining `UPLOADS_DISK`/`UPLOADS_DIRECTORY`. Only `TaskTransferController` honours the config. User-uploaded files are directly web-accessible. | `TaskController.php:359,401`, `UserController.php:74,141` |
| W19 | **Inconsistent file-upload validation** — tasks accept any type to 10 MB (no `mimes`), transfers allow-list extensions with no MIME check, avatars allow any image. | controllers cited |

### 4.3 Medium

| # | Issue | Evidence |
|---|---|---|
| W20 | **Three identical notification-log tables**: `notification_logs`, `meeting_notification_logs`, `task_notification_logs` — column-for-column identical except subject FKs. | migrations |
| W21 | **Three audit-log shapes**: `activity_logs` (`module_name`+`record_id`), `tyro_audit_logs` (polymorphic, no IP/UA), `obligation_activity_logs` (typed, +`user_agent`/`remarks`). Only the third is live. | migrations |
| W22 | **Two competing approval implementations**: generic `approval_workflows`+`_steps` (structurally unattachable — no `entity_type`/`entity_id`) vs. concrete `meeting_minutes_approvals` (actually used). The generic pair is dead code. | migrations, no route/service references |
| W23 | **Two competing task concepts**: `tasks` (3 statuses) vs. `meeting_action_items` (5 statuses, 4 priorities incl. `critical`). Linked by nullable `task_id`, so one unit of work can be represented twice. Different vocabularies for the same concept. | migrations |
| W24 | **Dead features**: `meeting_templates` + `meeting_template_agendas` (no route/controller), `meeting_recurrences` (`MeetingRecurrenceService::generateOccurrences()` never called, no cron), `meeting_versions` (`MeetingService::createVersion()` never called), `tblAccountInfo` (no model/controller/route), `PermissionController` (unrouted, its views don't exist), `config/menu.php` (entirely unused, all arrays empty), `employees`/`companies`/`departments`/`locations` (models exist, no CRUD anywhere). | grep |
| W25 | **Exactly one Form Request exists in the entire codebase** (`Auth/LoginRequest`). Zero in any module. All ~120 routes validate inline in controllers. | grep |
| W26 | **Fat controllers**: `TaskController` 403 lines, `DashboardController` 238 (220 lines of counting + chart aggregation + view-model mapping closures), `ObligationController` 259, `MeetingController` 240. `TaskController::dashboard()` 145 lines with a raw `join()`. | line counts |
| W27 | **Enum vocabularies declared three times each** — once in the migration `ENUM()`, once as an inline `in:` string list in the controller, once in a `in_array()` filter whitelist. No PHP enums, no `Rule::enum`, no shared constants. | e.g. `locations` |
| W28 | **Obligation notifications send synchronously** inside the scheduler — will block on SMTP timeouts. | `NotificationService.php`, `EscalationService.php` |
| W29 | **`TaskNotificationService::shouldNotify()` is a stub that always returns `true`.** No preferences table, no opt-out. | `Modules/Tasks/app/Services/TaskNotificationService.php:11-14` |
| W30 | **Laravel Notifications is entirely unused.** `Notifiable` imported on `User` but never exercised; no `notifications` table, no `Notification::route()`, no `ShouldBroadcast`, no broadcasting config, no `routes/channels.php`. "In-app notifications" are three log-table queries run on **every page render** by the navbar. | `resources/views/components/navbar.blade.php:8-30` |
| W31 | **`yajra/laravel-datatables` installed but never used.** Every list is server-paginated *then* client-side DataTables is applied over the 15 rows — **double pagination**, and the server renders a second `<x-pagination>` below the table. | grep for `DataTables::` = 0 |
| W32 | **No rate limiting** on any of the ~120 routes. `bootstrap/app.php` defines no `RateLimiter::for()`. Admin screens are unthrottled. | `bootstrap/app.php` |
| W33 | **Irreversible destructive migrations.** Three `drop_*` migrations with `FOREIGN_KEY_CHECKS=0` whose `down()` drops again instead of recreating — irreversible data loss. Plus `dropColumn('head_of_department')`. | `2026_09_29_09*` |
| W34 | **Duplicate `HAZIRA` column migration** — `2026_08_24_182838` adds a column already declared in the create migration, with no `hasColumn` guard. | migrations |
| W35 | **No cron is committed.** The 5 scheduled commands only fire if a server cron is configured out-of-band. `composer run dev` does not run `schedule:work`. No `->withoutOverlapping()`, `->onOneServer()` or `->timezone()` — app timezone is UTC but business data is `+06:00`. | `bootstrap/app.php:24-30` |
| W36 | **Queue `batching`/`failed` fall back to `sqlite`** while the app default is MySQL. | `config/queue.php:106,125` |
| W37 | **`REDIS_PREFIX` defaults to `'AssetTask Pro'`** and `LOG_LEVEL` defaults to `debug` in `config/logging.php` — leftover from a prior project. | `config/database.php:152` |

### 4.4 Low

- Typo in schema: `task_transfers.file_attache` (should be `file_attachment`).
- Naming mismatch: `obligations.category_id` → `obligation_categories`.
- Three coexisting "active" conventions: `status='active'` (string) / `active` (bool) / `is_active` (bool, meetings only).
- `permissions.permission_name` has no unique constraint or index despite being the natural key.
- `employees.email` is not unique. `roles.name` is not indexed (only `slug` is unique).
- `tblAccountInfo` has two column-identical indexes; `FILTERDATE` stored as `string(20)`.
- `RoleController::show()` loads `users` (no `Role::users()` relation) and reads `user_roles_count` that is never loaded.
- `routes/web.php` re-applies the `web` middleware group on top of the automatic one.
- `resources/views/layouts/print.blade.php` has no CSRF meta tag (low risk — print view).

---

## 5. Risks

| Risk | Likelihood | Impact | Mitigation |
|---|---|---|---|
| **Unauthorized data access / tampering.** Any authenticated user can edit any Meeting or Obligation by ID. | High (exploitable today) | Critical | Policies before any new module. Phase 2. |
| **Silent data loss on user deletion** via `cascadeOnDelete` on ownership columns (W13). | Medium | Critical | Change to `nullOnDelete` in a reversible migration. |
| **No regression safety net.** ~40k lines of application code, 8 tests, 1 passing. Any refactor is a leap of faith. | Certain | Critical | Make migrations SQLite-portable **first** — it unblocks everything. |
| **Additive schema work compounds the mess.** 4 more notification tables' worth of duplication if To-Do copies the existing patterns. | High | High | Fix the patterns before cloning them. |
| **Backup feature exfiltrates PII.** `DatabaseBackupController` gives any `admin`-role holder a full MySQL dump including `users.password`, `password_reset_tokens`, `sessions` — with no audit entry (because audit logging is dead, W4). | Medium | High | Gate on `super-admin`, audit the action, encrypt at rest. |
| **Scheduler failure pages ops daily.** `obligations:process` exits 1 on any single bad record, and sends mail synchronously (W28, W35). | Medium | Medium | Queue the sends; make exit codes advisory. |
| **Irreversible migrations already shipped.** `migrate:rollback` past `2026_09_29` loses ITAM/estate/gate-pass data permanently. | Low (already applied) | High | Document; never auto-rollback in production. |
| **Live credential on disk** (W17). | Certain | High | Rotate the password; move to a secret store. |

---

## 6. Technical Debt Inventory

| Debt | Size | Cost of addressing now | Cost later |
|---|---|---|---|
| No tests | ~40k LOC untested | High (must fix migrations first) | Exponential |
| No policies | Cross-cutting | High | Very high — every module inherits the flaw |
| Module boundaries nominal | 4 modules | Medium | High — code will keep bleeding across |
| Dead code | 8+ features/tables/controllers | Low (delete) | Low but confusing |
| Duplicated concepts | 3 audit shapes, 3 notification-log tables, 2 approval systems, 2 task models | High (needs migration plan) | Compounds |
| No API | Sanctum unused | Medium | High once mobile clients exist |
| Vocabulary drift | ENUMs × 3 declarations × 5 concepts | Low (PHP enums) | Medium — corrupts reporting |
| Non-portable migrations | 68 files, ~12 MySQL-specific | Medium | Blocks CI permanently |
| Fat controllers + inline validation | ~120 actions | Medium | Medium |

---

## 7. Recommended Target Architecture

**Modular monolith — confirmed.** The domain is already correctly decomposed and the
deployment is a single Laravel app. Microservices would add operational cost with no
benefit at this scale. The gap is that the *boundaries are declared but not enforced*.

### 7.1 Target Shape

```
app/                          Core platform (shared)
├── Models/                   User, Role, Permission, AuditLog, ActivityLog, Tag,
│                             Comment, Attachment, Notification*, Search*
├── Policies/                 TaskPolicy, TodoPolicy, MeetingPolicy, ObligationPolicy,
│                             ProjectPolicy + Gates
├── Services/                 AuditLogger, ActivityLogger, NotificationDispatcher,
│                             RecurrenceService, CommentService, AttachmentService
├── Observers/                Auto-audit observers (registered in AppServiceProvider)
└── Enums/                    Priority, WorkItemStatus, RecurrenceFrequency, Channel, Role

Modules/
├── Tasks/                    routes/, migrations/, views/, tests/  ← real
├── Todos/                    routes/, migrations/, views/, tests/  ← NEW
├── Meetings/                 routes/, migrations/, views/, tests/  ← real
├── Obligations/              routes/, migrations/, views/, tests/  ← real
└── Projects/                 routes/, migrations/, views/, tests/  ← real
```

### 7.2 Eight Decisions

1. **Modular monolith, enforced.** Migrate each module's routes and migrations into
   `Modules/*/routes/` and `Modules/*/database/migrations/`. Delete the central
   `routes/web.php` domain blocks as each module is migrated. Fix the module API prefix
   (`api.api`) before adding any API route.

2. **Policies, not a permission package.** Do **not** add `spatie/laravel-permission`.
   The `Role`/`Permission`/`RolePermission`/`UserRole` tables already exist. Add a
   `User::can(string $permission): bool` helper backed by a cached
   `role_permissions` lookup, one `Policies/` class per module model, and a Gate per
   action. Then the 53 seeded permissions finally enforce something.

3. **One audit spine, one activity spine.** Collapse to a single polymorphic
   `activity_logs` (add `subject_type`/`subject_id`, keep `old_value`/`new_value`/`ip`
   /`user_agent`) plus the existing polymorphic `tyro_audit_logs` (add `ip_address`,
   `user_agent`). Write via a single `AuditLogger`/`ActivityLogger` service called from
   model observers, not hand-rolled at each call site. Make audit append-only.

4. **One notification spine.** One polymorphic `notification_logs`
   (`subject_type`, `subject_id`) replacing the three identical tables. Introduce
   **Laravel Notifications** with a `notifications` table for the in-app channel, keep
   Mailables for email, and add a `notification_preferences` table so
   `shouldNotify()` stops returning a hardcoded `true`.

5. **Do not unify Tasks and To-Dos into one table.** They are genuinely different
   entities with different lifecycles. Share the *concepts* through traits/interfaces
   (`HasAssignee`, `HasPriority`, `HasDueDate`, `HasReminders`) and through PHP enums
   for priority/status, plus a common `work_items` **read view** for cross-module
   dashboards. Separate tables, shared vocabulary. See `TODO-MODULE-SPECIFICATION.md`.

6. **Unify vocabularies into PHP enums.** `Priority`, `WorkItemStatus`, `RecurrenceFrequency`,
   `NotificationChannel`. Replace the ENUM-in-migration + `in:`-string + `in_array()`
   triple declaration. Migrate the `tasks` status ENUM to include `cancelled`/`on_hold`
   to match `meeting_action_items`.

7. **Make migrations portable.** Replace `->after()` (drop it — cosmetic), replace raw
   `ALTER TABLE … ENUM` with `$table->enum()` rebuilds or string columns + PHP enums,
   replace `information_schema` queries with `Schema::hasTable/hasColumn`. This unblocks
   in-memory SQLite testing and CI. **Do this before writing any new migration.**

8. **REST API `/api/v1/*` with Eloquent API Resources.** Sanctum is already installed
   and unused. Start with read-only endpoints for Tasks, To-Dos, Meetings, Obligations,
   then add mutations. Versioned prefix, `throttle:` middleware, consistent envelope.
   Leave Elasticsearch out until measured scale demands it.

### 7.3 What Should NOT Change

Explicitly protect these:

- **The domain model.** Meetings' agenda→discussion→decision→action-item→task chain and
  the minutes workflow are good. Obligations' risk/escalation/renewal/responsibility
  modelling is good. Do not redesign.
- **The event→listener→job→mailable→log notification shape.** Extend it; do not replace it.
- **The Blade component library.** Extend it; do not introduce Livewire/Inertia/Vue.
- **AdminLTE 4 + Bootstrap 5 + Alpine + vanilla JS.** No SPA rewrite.
- **`config/navigation.php`** as the menu source of truth.
- **The `tblAccountInfo` isolation** on `sqlsrv`. Leave it orphaned and harmless, or
  delete the migrations — but do not let it touch the domain.
- **Database driver.** MySQL, not Postgres. `ENUM` removal happens via PHP enums at the
  application layer, not a driver migration.
- **Module naming and `modules_statuses.json`.** The four module names stay.

---

## 8. Sequencing Constraint

The single most important finding:

> **Migrations must be made SQLite-portable before any further feature work.**
> Without it there is no test database, therefore no tests, therefore no regression
> safety net, therefore every subsequent change — including the entire To-Do module —
> is unverifiable.

Phase 2 of the roadmap is gated on this. It is unglamorous and it is non-negotiable.