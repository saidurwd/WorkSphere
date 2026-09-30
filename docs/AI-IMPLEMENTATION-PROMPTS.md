# AI Implementation Prompts

> Deliverable 06. Copy-paste-ready prompts for an AI coding agent, one per roadmap phase.
> Source of truth: `ARCHITECTURE-ASSESSMENT.md`, `FUNCTIONAL-GAP-ANALYSIS.md`,
> `TODO-MODULE-SPECIFICATION.md`, `DATABASE-ARCHITECTURE.md`, `IMPLEMENTATION-ROADMAP.md`.
>
> **One prompt = one agent session = one reviewable, revertible change set.**

---

## 0. How To Use This Document

1. **Never paste more than one prompt into an agent at a time.** The phases are ordered by
   dependency, not by convenience.
2. **Respect the gate.** Each prompt ends with *Definition of Done*. Do not start the next
   phase until that gate passes on the real database, not just "it compiled".
3. **Leave the `CURRENT CONTEXT` block intact** even when you believe it is stale. If it is
   wrong, the agent's first job is to verify and correct it, and to say so in its report.
4. **Work on a branch.** One phase per branch: `phase/02-core-hardening`.
5. **The agent must report, not assume.** Every prompt ends with *Expected Output* — require
   that report, and check it before merging.

### The Universal Preamble

Every prompt below starts from the same ground rules. Keep this text, and the section
below it, with every prompt:

```text
ROLE
You are a senior Laravel engineer working inside an existing production Laravel
application (WorkSphere: Laravel 13.29, PHP 8.3/8.4, nwidart/laravel-modules 13,
MySQL + SQLite for tests, AdminLTE 4 + Bootstrap 5 + Alpine 3 + vanilla JS,
PHPUnit 12). You are extending an existing codebase, not building a new one.
Your default instinct is to reuse what exists.

ABSOLUTE RULES
1. INSPECT BEFORE MODIFYING. Read every file you touch and its callers first.
   Never edit a file you have not read in full.
2. DO NOT OVERWRITE WORKING FUNCTIONALITY without a stated justification.
3. REUSE existing services, models, Blade components and conventions before
   creating new ones. `Modules/Meetings` is the reference implementation layout.
4. MIGRATIONS ONLY for schema changes. Never hand-edit a database. Never edit a
   migration that has already run in production — add a new one.
5. NEVER drop or rename a column/table without a dedicated reversible migration,
   an impact analysis, and an explicit written approval.
6. NEVER modify production data manually.
7. ADD TESTS for every behaviour change. Update existing tests only when the
   behaviour change is intentional and stated.
8. LARAVEL BEST PRACTICES. Thin controllers. Form Requests for validation (exactly
   one exists in this codebase today — add more, do not inline-validate).
   Policies/Gates for authorization. Events -> ShouldQueue Listeners -> ShouldQueue
   Jobs for asynchronous work. DB::transaction for multi-step writes.
9. NO UNNECESSARY ABSTRACTION. No repository layer. No interface with one
   implementation. No service class with one method.
10. PORTABLE MIGRATIONS. No DB ENUM columns, no ->after(), no raw ALTER TABLE,
    no information_schema queries, no driver-specific SQL without a driver guard
    that skips cleanly on SQLite.
11. TRANSACTIONS for multi-step database writes. No N+1 queries. Index every
    column used in a where() clause in a controller.
12. NEVER log sensitive data (passwords, tokens, SMTP credentials, file contents).
13. After every PHP change run: vendor/bin/pint --dirty --format agent
14. After every PHP change run the narrowest relevant test:
    php artisan test --compact <path>
15. If you discover something the prompt did not tell you — a latent bug, a
    dangerous migration, an unenforced permission — DO NOT silently fix it.
    Report it in your final output under "UNEXPECTED FINDINGS".

REPORTING
End your response with:
- FILES CHANGED (path + one-line reason each)
- DATABASE CHANGES (migration names, or "none")
- TESTS WRITTEN / UPDATED
- TESTS RUN (command + result)
- ACCEPTANCE CRITERIA: a checklist of pass/fail, each with evidence
- UNEXPECTED FINDINGS (anything you found that was not in scope)
- WHAT I DID NOT DO and why
```

### Reference Layout (paste alongside any prompt)

```text
REFERENCE LAYOUT (verified — do not re-derive, but do verify)
- Modules/Meetings/         richest, most correct module. Copy its structure.
- Modules/Tasks/            notifications via Events -> Listeners -> Jobs -> Mail
- Modules/Obligations/      sends mail synchronously (a known defect, not a pattern)
- Modules/Projects/         thin scaffold
- All 68 migrations live in database/migrations/ (Module migrations dirs are empty)
- All ~120 routes live in routes/web.php (module routes/*.php are comment-only stubs)
- All views live in resources/views/{tasks,meetings,obligations,projects}
- 31 reusable Blade components under resources/views/components/ (x-form/input,
  x-form/select, x-form/textarea, x-badge, x-btn, x-icon-btn, x-modal, x-alert,
  x-datatable, x-detail-card, x-detail-list, x-empty-state, x-pagination, x-stat,
  x-progress-list, x-tom-select, x-flatpickr, x-confirm-dialog, ...)
- config/navigation.php is the single declarative menu tree
- resources/js/app.js:53-88 has a data-confirm SweetAlert2 interceptor to reuse
- Database: MySQL in dev/prod, SQLite :memory: for tests (phpunit.xml already sets this)
- Tests run with: php artisan test --compact   /   vendor/bin/phpunit
```

---

## Phase 0 — System Discovery *(complete; re-runnable as a verification pass)*

```text
OBJECTIVE
Re-verify the current state of the application and report any drift from the
discovery documents, WITHOUT changing a single line of code.

CURRENT CONTEXT
Five discovery documents were produced from a full inspection:
docs/ARCHITECTURE-ASSESSMENT.md, docs/FUNCTIONAL-GAP-ANALYSIS.md,
docs/DATABASE-ARCHITECTURE.md, docs/TODO-MODULE-SPECIFICATION.md,
docs/IMPLEMENTATION-ROADMAP.md.

TASK
Read-only verification. For each claim below, confirm or refute with file:line evidence:
1. Zero Policies, zero Gates, zero $this->authorize() across app/, Modules/,
   bootstrap/, config/, routes/.
2. MeetingController and ObligationController perform no object-level
   authorization on show/edit/update/destroy.
3. tyro_audit_logs has zero write call sites.
4. app/Http/Requests/Auth/LoginRequest.php recordFailure() is missing two imports.
5. app/Models/User.php tasks()/responsibleTasks()/projects() reference Task/Project
   without imports; User::employee does not exist; users.status and Role.description
   are absent from $fillable.
6. SecurityEventController overwrites $events with the paginator.
7. `DB_CONNECTION=sqlite php artisan migrate` fails. Record the exact failing
   migration and line.
8. `php artisan migrate:fresh` on an empty MySQL database fails on the
   role_permissions FK ordering and/or the duplicate HAZIRA column.
9. yajra/laravel-datatables is installed and never invoked.
10. laravel/sanctum is installed and no code path issues a token.
11. No `Modules/*/database/migrations/*.php` and no real `Modules/*/routes/*.php`.
12. 53 permissions are seeded in database/seeders/ProjectPermissionSeeder.php.
13. `vendor/bin/phpunit` result: how many pass, fail, error, skip.

DATABASE CHANGES
None. Read-only.

IMPLEMENTATION REQUIREMENTS
Change nothing. Do not fix anything. Do not create files.

ACCEPTANCE CRITERIA
- A table of the 13 claims: CONFIRMED / REFUTED / PARTIALLY, each with file:line.
- A list of claims that are now STALE, with the code that superseded them.
- Confirmation that no file was modified (`git status` is clean).

EXPECTED OUTPUT
The verification table, the stale-claim list, and the confirmation of a clean
working tree.
```

---

## Phase 2 — Shared / Core Hardening  ⛔ *critical path; blocks everything*

```text
OBJECTIVE
Build the platform safety net. Without this, every later phase is unverifiable.
This phase closes the 7 Critical gaps and makes the test suite meaningful.

FILES TO INSPECT FIRST
app/Models/User.php, app/Models/Role.php
app/Http/Requests/Auth/LoginRequest.php
app/Http/Controllers/Admin/SecurityEventController.php
app/Providers/AppServiceProvider.php, app/Providers/EventServiceProvider.php
bootstrap/app.php, routes/web.php
database/seeders/ProjectPermissionSeeder.php
database/migrations/**  (all 68)
Modules/*/app/Http/Controllers/*.php
Modules/*/app/Providers/RouteServiceProvider.php
phpunit.xml, tests/**

TASK — execute in this exact order, verifying after each step
 2.1  Add the two missing `use` imports in LoginRequest (App\Models\LoginLog,
      App\Services\LoginLogService). Add a test asserting a failed login records a
      LoginLog row instead of fatalling.                              [GAP-007]
 2.2  Fix User::tasks()/responsibleTasks()/projects() imports; add the missing
      User::employee relation. Add tests.                             [GAP-043]
 2.3  Add `status` to User::$fillable, `description` to Role::$fillable. Enable
      Model::preventSilentlyDiscardingAttributes() in the local and testing
      environments only.                                              [GAP-044]
 2.4  Fix the $events variable shadowing in SecurityEventController. Add a test.
                                                                     [GAP-045]
 2.5  MAKE ALL MIGRATIONS SQLITE-PORTABLE. Remove every DB ENUM column (use
      `string` + a PHP enum cast), remove every ->after(), replace every raw
      DB::statement('ALTER TABLE ... ENUM') with a portable rebuild or a string
      column, replace every information_schema query with Schema::hasTable/
      hasColumn/hasIndex. Guard anything genuinely driver-specific so it skips
      cleanly on SQLite. Work file by file, one concern per change.
                                                                     [GAP-004]
 2.6  Fix the role_permissions FK ordering (DB-01) and guard the HAZIRA column
      with Schema::hasColumn (DB-02).
 2.7  VERIFY: `DB_CONNECTION=sqlite php artisan migrate:fresh` succeeds, AND
      `php artisan migrate:fresh` on an empty MySQL database succeeds.
 2.8  Create app/Enums/: Priority, WorkItemStatus, RecurrenceFrequency,
      NotificationChannel, Role. Backed string enums. Each exposes cases(),
      values() and a label(). Existing ENUM vocabularies must map onto these
      without changing stored values.                                [GAP-020]
 2.9  Implement User::can(string $permission): bool — a cached lookup across
      user_roles -> role_permissions -> permissions. Cache must be
      request-scoped and invalidated on role/permission change.  [GAP-002]
 2.10 Create app/Policies/ for Task, Project, Meeting, Obligation, User, Role.
      Register every one in AppServiceProvider::$policies. Keep the rule
      granularity of the existing ad-hoc abort(403) calls in the controllers —
      you are formalising existing behaviour, not inventing new policy.
                                                                     [GAP-001]
 2.11 Wire `$this->authorize()` into EVERY mutating action in Tasks, Meetings,
      Obligations and Projects. This is the IDOR fix — Meetings and Obligations
      currently have no object-level check at all.               [GAP-003]
 2.12 Create app/Services/AuditLogger.php and app/Services/ActivityLogger.php.
      Register model observers for create/update/delete on the audited models.
      Every write records actor, action, subject, old value, new value.
                                                                     [GAP-006]
 2.13 Add ip_address + user_agent to tyro_audit_logs. Make AuditLog
      append-only: override delete() to throw, add no updated_at.
 2.14 Add the missing indexes (see DATABASE-ARCHITECTURE.md §2.3). Additive only.
                                                                     [GAP-013]
 2.15 Soften cascadeOnDelete to nullOnDelete (+ make nullable) on ownership
      columns: tasks.user_id, meetings.organizer_id, obligations.owner_user_id,
      meeting_minutes_approvals.approver_id, task_remarks.user_id. Write and test
      a data backfill first.                                          [GAP-012]
 2.16 Fix the module RouteServiceProvider `api.api` prefix bug.           [GAP-017]
 2.17 Migrate routes into Modules/*/routes/web.php ONE MODULE AT A TIME. After
      each module run `php artisan route:list` and diff against the previous
      output. Named routes and URLs must be byte-identical. Delete the central
      block only after the module's routes are verified.
 2.18 Move module migrations into Modules/*/database/migrations/ — pure file
      moves, no content edits, timestamps preserved so ordering is unchanged.
 2.19 Establish the test baseline: authentication, RBAC, policy enforcement,
      and happy-path + key-failure-mode CRUD for Task, Meeting, Obligation.
                                                                     [GAP-005]
 2.20 Delete dead code with zero references: PermissionController (unrouted,
      views absent), config/menu.php (unused), and record the approval_workflows
      decision as a Phase 15 item rather than dropping tables here. [GAP-042]

DATABASE CHANGES
- 5 new portable-migration-fix migrations for 2.5/2.6.
- 1 migration adding ip_address + user_agent to tyro_audit_logs.
- 1 migration adding the §2.3 indexes.
- 1 migration softening ownership FKs to nullOnDelete.
- Every migration must be independently revertible with a working down().

IMPLEMENTATION REQUIREMENTS
- Do NOT use spatie/laravel-permission. The Role/Permission/RolePermission/UserRole
  tables already exist; build a thin policy layer on them.
- Do NOT redesign any domain model. You are formalising existing behaviour.
- Route moves are PURE MOVES. Any URL, name or middleware change is a defect.
- Each of 2.1-2.20 must be an independently revertible change.

SECURITY REQUIREMENTS
- `authorize()` on every mutating action, including bulk actions and console-triggered
  HTTP entry points.
- Password hashing stays on the `hashed` cast. No Hash::make() in app code.
- The existing login throttle (5/60s, keyed on transliterated email + IP) must keep
  working, and failure logging must now actually function.

TESTING REQUIREMENTS
- phpunit.xml targets SQLite :memory:. Prove migrate:fresh works there.
- Policy tests: a non-assignee receives 403 on a Task AND a Meeting AND an
  Obligation. A user with no permission receives 403. `view_all`-style permission
  sees everything.
- Audit test: AuditLog::count() increases after a task update, with the old and
  new values captured.
- User::can('tasks.update') enforcement test.
- Route parity test: assert the full `route:list` name->URI map is unchanged
  before and after the 2.17 move (capture it as a fixture).

BACKWARD COMPATIBILITY
No URL, route name, view, seeded permission or stored column value may change.
Any change here is a regression, not a feature.

ACCEPTANCE CRITERIA
- DB_CONNECTION=sqlite php artisan migrate:fresh  -> success
- php artisan migrate:fresh on empty MySQL       -> success
- vendor/bin/phpunit -> all green, ZERO errors, ZERO skips
- `php artisan route:list` output identical to the pre-change baseline
- A feature test proves 403 for a non-assignee on Task, Meeting and Obligation
- A feature test proves the audit row is written on update
- vendor/bin/pint --dirty --format agent clean

EXPECTED OUTPUT
FILES CHANGED grouped by task 2.1-2.20, the route-parity diff, the phpunit summary,
and an explicit statement of any behaviour that changed for an end user (expected
answer: none beyond new 403s where IDOR existed).
```

---

## Phase 3 — To-Do Database Architecture

```text
OBJECTIVE
Create the To-Do tables and the shared platform tables. PURELY ADDITIVE.
Nothing that already exists is altered or dropped.

FILES TO INSPECT FIRST
docs/DATABASE-ARCHITECTURE.md §4 (authoritative schema — implement it literally)
Modules/Meetings/database/migrations/ (naming + style reference)
database/seeders/ProjectPermissionSeeder.php
Modules/Meetings/database/factories/, Modules/Meetings/database/seeders/
modules_statuses.json

TASK
1. Create migrations, in this order, exactly as specified in
   DATABASE-ARCHITECTURE.md:
   - create_todos_table                 (§4.1)
   - create_todo_watchers_table        (§4.2)
   - create_todo_checklist_items_table  (§4.3)
   - create_todo_links_table            (§4.4)
   - create_comments_table              (§4.5  shared platform)
   - create_attachments_table           (§4.6  shared platform)
   - create_tags_and_taggables_tables   (§4.7  shared platform)
   - create_reminders_table             (§4.8  shared platform)
   - create_notification_logs_table     (§4.9  consolidated)
   - create_notification_preferences_table (§4.10)
   - create_notifications_table         (§4.11  Laravel Notifications)
   - add_polymorphic_columns_to_activity_logs (§4.12)
2. add_ip_and_user_agent_to_tyro_audit_logs (if Phase 2 did not already do it)
3. create_work_items_view — the read-only SQL VIEW from §4.13, created via
   DB::statement ONLY on MySQL and skipped cleanly on SQLite.
4. Register `Todos` in modules_statuses.json.
5. Seed the 12 `todos.*` permissions from TODO-MODULE-SPECIFICATION.md §4 into
   database/seeders/ProjectPermissionSeeder.php (additive — do not remove any
   existing permission string).
6. Create Todos/database/factories/TodoFactory.php and a small TodoSeeder for
   local development.

DATABASE CHANGES
Only CREATE TABLE / CREATE INDEX. One new migration per concern. No ALTER on an
existing table except the additive columns named in §4.12.

IMPLEMENTATION REQUIREMENTS
- `status`, `priority`, `visibility` are `string` + PHP enum casts. NO DB ENUM.
- todos.due_date is NULLABLE — unlike tasks. This is deliberate.
- creator_id is restrictOnDelete; assignee_id and department_id are nullOnDelete.
- Checklist progress is COMPUTED, never stored.
- attachments default disk is `local` (private), NOT `public`.
- notification_logs.dedupe_key carries a UNIQUE index — this is what makes every
  reminder command safely re-runnable.
- Guard every migration with Schema::hasTable/hasColumn/hasIndex for idempotency.
- Follow DATABASE-ARCHITECTURE.md §5.1 Option A: do NOT attempt to convert the
  existing ENUM columns. New work is portable; working tables are left alone.

SECURITY REQUIREMENTS
- No attachment may be reachable by direct URL. The private disk plus an
  authorised download controller is the only access path (the controller lands in
  Phase 5).
- No PII defaults, no seeded credentials in TodoSeeder.

TESTING REQUIREMENTS
- A test asserting every index in §4.1-§4.10 exists (Schema::hasIndex, or an
  information-schema query explicitly skipped on SQLite).
- A test asserting `migrate:rollback` then `migrate` is clean on SQLite.
- Factory tests: TodoFactory creates a row passing validation, and a default
  (title-only) To-Do.

BACKWARD COMPATIBILITY
Additive only. Prove it: run `migrate:rollback --step=11` and confirm no
pre-existing table was touched.

ACCEPTANCE CRITERIA
- migrate:fresh succeeds on BOTH sqlite and mysql
- migrate:rollback then migrate is clean on sqlite
- `git diff --stat` shows no modification to any pre-existing migration file
- Every index in §4.1-§4.10 exists (test proves it)
- No existing table altered or dropped

EXPECTED OUTPUT
FILES CHANGED, the migration list with names, the index-verification test result,
and the rollback test result.
```

---

## Phase 4 — To-Do Backend

```text
OBJECTIVE
Models, policies, form requests, services, events, listeners, jobs, recurrence
engine and reports for the To-Do module. NO UI in this phase.

FILES TO INSPECT FIRST
docs/TODO-MODULE-SPECIFICATION.md  (§3 status, §4 permission, §5 recurrence,
                                   §6 notifications, §10 reporting, §12 file layout)
Modules/Meetings/app/**            (reference structure)
Modules/Tasks/app/Services/TaskNotificationService.php
app/Enums/**                       (created in Phase 2)
app/Services/AuditLogger.php, app/Services/ActivityLogger.php

TASK
1. `php artisan make:module Todos` — then reshape it to match
   docs/TODO-MODULE-SPECIFICATION.md §12 exactly. Keep the Meetings layout.
2. Models: Todo, TodoWatcher, TodoChecklistItem, TodoLink.
   - Todo casts status -> WorkItemStatus, priority -> Priority, visibility ->
     VisibilityEnum, recurrence_rule -> array, SoftDeletes.
   - Relations: assignee, creator, department, watchers, checklistItems, links,
     reminders, tags, comments, attachments.
   - Scopes: scopeForUser, scopeActive, scopeOverdue, scopeDueBetween,
     scopeRecurring, scopeStatus.
   - A TodoScope global scope mirroring TodoPolicy::view so the list query and the
     object check can never disagree.
3. TodoPolicy implementing §4.1 EXACTLY. Register in AppServiceProvider. The
   `view` rule is an explicit method — do not fall back to `can()`.
4. Form Requests: StoreTodoRequest, UpdateTodoRequest, AssignTodoRequest,
   CompleteTodoRequest, RecurrenceTodoRequest, StoreTodoCommentRequest.
   Validation uses the enum cases and Rule::enum — never a duplicated
   `in:pending,in_progress,...` string. ZERO inline validation in the module.
5. Services, each method wrapped in DB::transaction and each writing an
   activity_logs row:
   - TodoService: create, update, assign, complete, reopen, archive, restore, destroy
   - TodoLinkService: attach, detach, resolveReverse (bidirectional navigation)
   - TodoRecurrenceService: advance(), nextOccurrence(), skip(), plus the shared
     RecurrenceService in app/Services/ that §5.4 describes
   - TodoReportService: aggregate queries only (§10) — never load rows into PHP
6. Events (§6.2 table, 12 events) -> ShouldQueue Listeners -> ShouldQueue Jobs.
   ZERO synchronous mail anywhere.
7. TodoNotification (database + mail channels) with a single class handling all
   types, plus TodoNotificationService::shouldNotify() consulting
   notification_preferences instead of returning true.          [GAP-024]
8. Every send writes a notification_logs row with a dedupe_key.  [GAP-023]
9. Events are dispatched from the service, never from the controller.

DATABASE CHANGES
None. Phase 3's schema is final for this phase.

IMPLEMENTATION REQUIREMENTS
- Status transitions validated against §3.2. Illegal transitions throw, they do
  not silently no-op.
- `Waiting` requires `waiting_on` — reject the transition without it.
- `Completed` sets completed_at + completed_by; `reopen` clears both.
- `Archived` stores the prior status in `archived_from` and restores it.
- Recurrence is rule-on-row, materialise-on-completion (§5.1). NO
  todo_occurrences table in v1.
- respect max_occurrences, end_date and skip_dates.
- Do not create a ConfigurableStatus abstraction. The status model is a PHP enum
  by design (§3.3) — do not build a transition table.

SECURITY REQUIREMENTS
- Every service method that mutates receives an already-authorised actor; the
  service must NOT re-implement permission logic (that is the policy's job) but
  it MUST accept an actor parameter so jobs/commands can act on a user's behalf.
- Never trust `assignee_id` from the request payload without the policy gate.
- No user-supplied array key reaches the polymorphic link resolution unchecked —
  map linkable_type through an explicit allow-list.

TESTING REQUIREMENTS
- Unit: TodoService — every legal transition, and every ILLEGAL transition
  asserted to throw.
- Unit: TodoRecurrenceService — each frequency, interval > 1, by_weekday,
  by_month_day, skip_dates, max_occurrences, end_date boundary.
- Feature: TodoPolicy — non-assignee 403; cross-department Team To-Do is
  invisible in list AND 403 on show; todos.view_all sees everything;
  todos.update_own does not permit editing someone else's.
- Feature: every mutation writes an activity_logs row with old + new values.
- Feature: a complete notification test with Notification::fake() + Mail::fake()
  proving the job is queued and never run inline.
- Test: no query count growth as comment count rises (N+1 guard).

BACKWARD COMPATIBILITY
Nothing outside Modules/Todos changes, except AppServiceProvider policy
registration and App\Enums additions.

ACCEPTANCE CRITERIA
- No inline $request->validate() anywhere in Modules/Todos
- Every mutation writes an activity_logs row (test proves it for create, update,
  assign, status change, archive, delete)
- Illegal transitions throw
- Zero synchronous Notification::send() or Mail::to()->send() outside a Job
- vendor/bin/phpunit green; pint clean

EXPECTED OUTPUT
FILES CHANGED grouped by layer, the test list with pass counts, and a written
confirmation that every §3.2 transition is covered by a test.
```

---

## Phase 5 — To-Do UI

```text
OBJECTIVE
Build the To-Do module's UI using ONLY the 31 existing Blade components. No new
CSS framework, no new JS library, no SPA.

FILES TO INSPECT FIRST
docs/TODO-MODULE-SPECIFICATION.md §8 (screens, components, UX standards, navigation)
resources/views/components/**            (all 31 components — reuse, never duplicate)
resources/views/tasks/**                 (list/detail/form patterns to mirror)
resources/js/app.js:53-88                (data-confirm SweetAlert2 interceptor)
config/navigation.php
app/Http/Controllers/DashboardController.php:166-189  (duplicated status->badge match)

TASK
1. TodoController (resource) + TodoCalendarController + TodoReportController +
   TodoChecklistController + TodoWatcherController + TodoLinkController +
   TodoNotificationLogController. Server-side yajra DataTables for every list.
   Routes in Modules/Todos/routes/web.php with a `todo` route-model-binding
   param that triggers policy 404-vs-403 correctly.
2. Views per §8.1: index, create, edit, show, _form, _row, calendar, reports/*
   — all in Modules/Todos/resources/views/.
3. QUICK CAPTURE: a title-only input on the index page that creates a To-Do on
   Enter with no page navigation. This is the single most important interaction
   in the module — get it right.
4. Extract App\Support\StatusBadge::variant(WorkItemStatus|string): string and
   use it in EVERY module, replacing the duplicated match arms.        [GAP-020]
5. show page renders checklist, watchers, links, comments, attachments and the
   activity timeline, all via the shared polymorphic tables.
6. Empty / loading / error states using x-empty-state and x-alert. Distinct copy
   for inbox-empty, filtered-empty and never-created.
7. Bulk actions: complete, reassign, archive, tag — multi-select with a sticky
   action bar, each server-authorized individually.
8. Append the §8.4 node to config/navigation.php. It is a config edit only.
9. Accessibility pass: aria-label on every icon-only control, aria-live on filter
   results, full keyboard reachability, WCAG AA contrast on badges, no
   colour-only status encoding.
10. REMOVE client-side DataTables double-pagination everywhere (server-side only).
                                                                   [GAP-038]
11. Ensure no user-supplied content is ever rendered with {!! !!}.

DATABASE CHANGES
None.

IMPLEMENTATION REQUIREMENTS
- Every page uses the existing components. If a component seems to be missing a
  feature, EXTEND the component — do not build a parallel one.
- Escape everything. `description`, comment bodies, file names and tag names go
  through {{ }} only.
- Authorize on the server for every action, including bulk — never trust a
  hidden field or a client-side check.
- Views live in the module, not in resources/views/.

SECURITY REQUIREMENTS
- Attachment downloads go through an authorised controller on the private disk.
  NO direct public URLs, NO guessable paths.
- File uploads validated for MIME AND extension, with a size cap, through one
  shared upload service (closes GAP-011's To-Do side).
- Confirmation dialogs for delete/archive/bulk are UX only — the server must
  re-validate.

TESTING REQUIREMENTS
- Feature tests rendering every screen with zero Blade errors (assert 200 +
  no exception).
- Test that a user who cannot view a To-Do gets 403/404, not a 200 with an
  empty page.
- Test that bulk actions authorize PER ITEM and skip (or 403 on) unauthorized ones.

ACCEPTANCE CRITERIA
- Every page renders with zero Blade errors
- No new CSS framework or JS library introduced
- No double pagination anywhere in the To-Do module
- Zero {!! !!} on user data
- npm run build succeeds
- Lighthouse accessibility on the index page meets AA for contrast and labels

EXPECTED OUTPUT
FILES CHANGED, the list of components reused, the accessibility checklist with
evidence, and confirmation that StatusBadge was applied across all modules.
```

---

## Phase 6 — To-Do Notifications, Reminders & Recurrence Runtime

```text
OBJECTIVE
Wire the shared reminder pipeline and the recurrence scheduler. Every send
queued, every command idempotent, timezone corrected.

FILES TO INSPECT FIRST
docs/TODO-MODULE-SPECIFICATION.md §5 (recurrence) and §6 (notifications)
bootstrap/app.php                        (current schedule block)
Modules/Tasks/app/Console/Commands/**
Modules/Meetings/app/Console/Commands/**
Modules/Obligations/app/Services/{NotificationService,EscalationService}.php
Modules/Tasks/app/Services/TaskNotificationService.php

TASK
1. `reminders:dispatch` — every minute. Selects
   status = Pending AND remind_at <= now() in chunks of 200, dispatches jobs,
   marks sent. Idempotent via the reminders unique index and notification_logs
   dedupe_key.
2. `todos:overdue`, `todos:due-soon`, `todos:generate`, `todos:skip` commands.
3. EVERY send is dispatched to a ShouldQueue job. Zero synchronous mail in any
   request or scheduler.                              [GAP-022, GAP-022]
4. dedupe_key on every notification log write, format
   `{type}:{subject_id}:{discriminator}` (e.g. `todo.overdue:412:2026-10-01`).
5. Wire notification_preferences into shouldNotify() so a user can genuinely
   disable a type.                                            [GAP-024]
6. Change the navbar to read the `notifications` table instead of the three
   log tables.                                                [GAP-021]
7. Consolidate: the five module-specific daily commands become reminder-driven.
   Keep the old commands callable until Phase 8 confirms parity — do not delete
   them in this phase.
8. Add ->everyMinute()->withoutOverlapping()->onOneServer()->timezone(...) to
   every schedule entry.                                       [GAP-046]
9. Fix the app timezone from UTC to the business timezone (+06:00) and document
   the change in the diff.                                     [GAP-046]

DATABASE CHANGES
None new. Consumes notification_logs, notification_preferences, reminders,
notifications from Phase 3.

IMPLEMENTATION REQUIREMENTS
- Commands must be safely re-runnable. This is a hard requirement, not a nicety:
  a cron that fires twice must not double-send.
- Jobs must be idempotent on retry, not just on re-dispatch.
- Exit codes are advisory — one bad row must not fail the whole run.  [GAP-046]
- Chunk everything that could grow unbounded.

SECURITY REQUIREMENTS
- Logs record subject id and notification type only. NEVER log email bodies,
  message content or user PII.
- Command output must not print recipient addresses to stdout in production.

TESTING REQUIREMENTS
- Dedupe test: run `todos:overdue` twice; assert exactly one notification_logs
  row and one queued job per To-Do.                            [dedupe_key]
- No-inline-send test: Mail::fake() + Queue::fake() around an HTTP request;
  assert nothing was sent and jobs were pushed.
- Preference test: a user with enabled=false for `todo_overdue` receives
  nothing; enabled=true receives it.
- Recurrence generation test: completing a recurring To-Do creates exactly one
  next occurrence with the right date.
- Timezone test: a reminder scheduled at a local time fires at the correct UTC
  instant.

BACKWARD COMPATIBILITY
The five existing daily commands remain registered. The navbar query change must
render identically for users with no notifications.

ACCEPTANCE CRITERIA
- Re-running todos:overdue produces no duplicate emails (test proves it)
- No mail is sent inside any HTTP request (test proves it)
- A user can disable a notification type and stops receiving it
- Every schedule entry has withoutOverlapping + onOneServer + an explicit timezone

EXPECTED OUTPUT
FILES CHANGED, the schedule block after the change, and the results of the four
idempotency/no-inline-send tests.
```

---

## Phase 7 — Cross-Module Integration

```text
OBJECTIVE
Make the To-Do a first-class citizen alongside Tasks, Meetings and Obligations.
Make every module's detail page link to and from a To-Do.

FILES TO INSPECT FIRST
docs/DATABASE-ARCHITECTURE.md §4.13            (work_items view)
docs/TODO-MODULE-SPECIFICATION.md §7.2          (todo_links)
Modules/Tasks/app/Models/Task.php
Modules/Meetings/app/Models/{Meeting,MeetingActionItem}.php
Modules/Obligations/app/Models/Obligation.php
Modules/Tasks/app/Http/Controllers/TaskController.php

TASK
1. Wire the `work_items` view into the dashboard query layer. READ-ONLY — never
   write to it, never join to it for a mutation. On SQLite (where the view is not
   created) the query layer must fall back to a UNION of the base tables so tests
   exercise the same code path.
2. Meeting action item -> To-Do link (one click from the action item).
3. Obligation -> To-Do link.
4. Task -> To-Do link.
5. A unified "My Work" page: Tasks + To-Dos + Action Items + Obligations in one
   permission-filtered list.                                     [GAP-034]
6. Global tags working across To-Dos and Meetings (shared `taggables`).
7. Unified comments and attachments across modules — the shared platform tables,
   with Meetings and To-Dos both writing to them.                [GAP-048]
8. Extract RecurrenceService into app/Services/ and move Meetings and
   Obligations onto it. The To-Do module is already its first consumer.
                                                               [GAP-049]

DATABASE CHANGES
None new. Optional additive indexes only if a query plan proves a need.

IMPLEMENTATION REQUIREMENTS
- Navigation must work in BOTH directions: a To-Do lists its related Task, and
  that Task lists the To-Do. Resolve through TodoLinkService, not by adding new
  nullable FK columns to three tables.
- No duplicated concept may survive: if Meetings still write comments to
  meeting_discussions while To-Dos write to `comments`, you have not finished
  this task.
- Do NOT migrate existing meeting tag rows in this phase — dual-write first,
  migrate in Phase 8. Report the migration plan.

SECURITY REQUIREMENTS
- Every link rendered must pass its target's policy. A To-Do pointing at a Task
  the viewer cannot see must not leak the Task's title.
- The unified list must apply the SAME permission filter as the individual
  module lists. A user must not see a row here that they cannot open there.

TESTING REQUIREMENTS
- Test that a cross-module link is navigable in both directions.
- Test that a link to an unauthorized record renders nothing (no title leak).
- Test that the SQLite fallback produces the same row set as the view path.

ACCEPTANCE CRITERIA
- Every module's detail page links to and from a To-Do, bidirectionally
- No duplicated comment/attachment/tag concept remains between Meetings and To-Dos
- SQLite and MySQL produce identical "My Work" results

EXPECTED OUTPUT
FILES CHANGED, the dual-write plan for meeting tags/documents, and a statement of
what remains duplicated (should be: only the legacy read paths).
```

---

## Phase 8 — Task / Meeting / Obligation Improvements  ⚠️ *first phase that changes user-visible behaviour*

```text
OBJECTIVE
Close the highest-value functional gaps in the three existing modules.
THIS PHASE IS HIGH RISK — it is the first one that changes working behaviour.

FILES TO INSPECT FIRST
docs/FUNCTIONAL-GAP-ANALYSIS.md GAP-025 through GAP-032, GAP-040, GAP-041
Modules/Tasks/**, Modules/Meetings/**, Modules/Obligations/**
database/migrations/*tasks*, *meeting*, *obligation*
resources/views/{tasks,meetings,obligations}/**

TASK — Tasks (GAP-025, GAP-026)
 - parent_id + a scope + a subtask UI. Guard against cycles.
 - watchers (pivot), mirroring todo_watchers.
 - Widen `tasks.status` to match meeting_action_items (add on_hold, cancelled)
   and make due_date NULLABLE.                                 [GAP-026]
 - estimated_minutes / actual_minutes + a time_entries table.
 - Tags via the shared `taggables`.
 - Migrate task_remarks -> shared `comments` (dual-write, backfill, cut over).
 - Activity timeline per task.

TASK — Meetings (GAP-028, GAP-029, GAP-030)
 - Wire meeting_templates: CRUD + "schedule from template". The models and
   relations already exist and are entirely unwired.
 - Wire MeetingRecurrenceService + a cron for occurrence generation.
 - meeting_attachments -> shared `attachments`.
 - meeting_tags / meeting_tag_map -> shared `taggables` (backfill from Phase 7).
 - meetings.location_id FK -> locations, free text retained as fallback.
 - Tighten attachment cascade behaviour (all four nullable CASCADE parents).
 - Action items -> tasks: DECIDE migrate-vs-keep-separate FIRST and write that
   decision down before implementing.                           [GAP-027]

TASK — Obligations (GAP-031, GAP-032)
 - escalation_rules scoped by department_id / company_id.
 - approval_workflows: add subject_type/subject_id, OR delete. Make the decision
   explicit and justify it.                                     [GAP-031]
 - obligation_activity_logs -> shared activity_logs; add array casts on
   old_value / new_value so reads return arrays, not JSON strings.
 - obligation_documents -> shared `attachments`.
 - Queue ALL obligation sends.                                   [GAP-022]

TASK — Identity (GAP-040, GAP-041)
 - Admin CRUD for employees, companies, departments, locations. Models exist;
   there is not one route between them.
 - users.employee_id -> NOT NULL, after a verified backfill.
 - Password policy, session controls, account-status enforcement.

DATABASE CHANGES
Every change is its own reversible migration, in this order:
 status widening -> nullable due_date -> parent_id -> watchers -> time_entries ->
 meetings.location_id -> escalation scope -> approval subject -> users.employee_id
 -> consolidation migrations (comments/attachments/tags/activity backfill).
Nothing is dropped in this phase. Consolidation is dual-write + backfill + cutover,
with drops deferred to Phase 15.

IMPLEMENTATION REQUIREMENTS
- Every schema change ships with a data backfill script that is safe to re-run.
- Status widening must not orphan existing rows: map the 3 old values to the
  5 new ones explicitly.
- Where a decision is genuinely open (action items vs tasks; approval_workflows),
  STOP, present both options with their cost, and ask before implementing.

SECURITY REQUIREMENTS
- The new Admin CRUD routes are the highest-privilege surface in the app.
  Restrict each to the permission that governs it; never to a bare role slug.
- users.employee_id NOT NULL must not lock anyone out — verify every existing
  user has an employee row before applying the constraint.

TESTING REQUIREMENTS
- The ENTIRE existing suite must stay green. No test may be deleted or weakened.
  If an existing test fails, stop and report before changing it.
- New test per behaviour change, including the migration backfill.
- A subtask cycle test (a task cannot become its own ancestor).
- Action-item/task reconciliation test if GAP-027 is migrated.

BACKWARD COMPATIBILITY
~120 existing routes must keep working with identical URLs, names and middleware.
Existing enum values, existing data, and existing screens must not change
meaning. Widening a status enum is additive; do not rename an existing value.

ACCEPTANCE CRITERIA
- Existing suite green, nothing deleted
- Rollback migration written and tested for every schema change
- Zero functional regression across the ~120 existing routes (smoke-tested)
- Every open decision (GAP-027, approval_workflows) is documented with a rationale
- pint clean

EXPECTED OUTPUT
FILES CHANGED, MIGRATIONS ADDED with a backfill note each, DECISIONS MADE with
justification, TEST RESULTS, and REGRESSION CHECKLIST against the 120 routes.
```

---

## Phase 9 — Global Search

```text
OBJECTIVE
Permission-aware search across every module. MySQL FULLTEXT. NO Elasticsearch.

FILES TO INSPECT FIRST
docs/FUNCTIONAL-GAP-ANALYSIS.md GAP-035
Modules/Meetings/app/Services/MeetingReportService.php   (aggregate-query pattern)
resources/views/layouts/**                               (navbar search entry point)
app/Http/Controllers/DashboardController.php

TASK
1. App\Search\SearchIndex — a registry of searchable entities. Each entry declares
   the model, the fields searched, the display formatter, the route, and the
   permission required to see a result. Adding an entity later must be a
   one-line registration.
2. FULLTEXT indexes on tasks, todos, meetings, obligations, task_projects.
   Guard with a driver check so SQLite tests skip cleanly.
3. SearchController with grouped, permission-filtered results. Group by module,
   show per-group counts, link to the record.
4. Filters: module, status, date range, owner.
5. Facets and counts.
6. Search-as-you-type, debounced, with an aria-live results region.
7. Log searches (search_logs, or reuse activity_logs) for relevance analysis.

DATABASE CHANGES
One additive migration adding FULLTEXT indexes, driver-guarded.

IMPLEMENTATION REQUIREMENTS
- NON-GOAL, explicitly: Elasticsearch, Meilisearch, Algolia. MySQL FULLTEXT is
  sufficient at this scale. Revisit only when measured latency demands it.
- Results MUST be permission-filtered using each entity's policy. A search hit
  on a record the user cannot open is an information leak.
- Fall back to LIKE on SQLite so tests exercise the same code path.
- Never pass a user-supplied sort column to orderBy — whitelist it.

SECURITY REQUIREMENTS
- Search input is escaped and never interpolated into raw SQL. Use bindings.
- Permission filtering happens in the QUERY, not in the Blade template. Hiding
  a row in the view is not authorization.
- Debounced client search must not leak counts for records the user cannot see.

TESTING REQUIREMENTS
- Test that a user without access to a module gets zero results from it.
- Test that an SQL-injection-shaped query string returns no results and does not
  error.
- Test an unknown sort column is rejected with a 422, not passed through.
- Test the SQLite LIKE fallback returns the same result set as the MySQL path.

ACCEPTANCE CRITERIA
- Search returns grouped, permission-filtered results across tasks, todos,
  meetings, obligations
- An unauthorized record never appears, including its count
- No Elasticsearch or external search dependency introduced

EXPECTED OUTPUT
FILES CHANGED, the SearchIndex registration example, and the permission-leak
test result.
```

---

## Phase 10 — Dashboard & Reporting

```text
OBJECTIVE
Role-aware enterprise dashboard and a shared reporting layer.

FILES TO INSPECT FIRST
app/Http/Controllers/DashboardController.php   (238 lines, 220 of aggregation)
Modules/Meetings/app/Services/MeetingReportService.php
Modules/Obligations/app/Http/Controllers/ObligationReportController.php
docs/TODO-MODULE-SPECIFICATION.md §10
resources/views/components/x-stat, x-progress-list, x-datatable

TASK
1. App\Dashboard\DashboardWidget interface + a widget registry. Each widget
   declares its permission(s), its data method, and its cache key.
2. Personal widgets: My To-Dos, My Tasks, Upcoming Meetings, Overdue,
   Today's Activities, Upcoming Deadlines.
3. Management widgets: Team Workload, Overdue Items, Completion Rate, Task
   Distribution, Department Performance.
4. Compliance widgets: Upcoming/Overdue Obligations, Critical Deadlines.
5. WIDGET VISIBILITY IS DRIVEN BY PERMISSIONS, never by hardcoded role slugs.
   `admin` and `super-admin` string comparisons must not appear in this code.
6. Per-widget caching keyed by user + permission set. Cache invalidation on the
   relevant data change.
7. Shared ReportService in app/Services/. Add Task reports — there are NONE
   today.                                                     [GAP-036]
8. CSV export for every report; PDF via the shared export service if Phase 8/9
   produced one, otherwise defer and say so.                    [GAP-050]

DATABASE CHANGES
None. Read-only aggregation over the work_items view and base tables.

IMPLEMENTATION REQUIREMENTS
- All aggregation in SQL. Never load rows into PHP to count them.
- DashboardController must shrink. Extract the query layer; do not just add more
  methods to a 238-line controller.
- Every widget query must be explainable in one sentence in a docblock.
- N+1 is a hard failure. Eager-load or withCount only.

SECURITY REQUIREMENTS
- Every widget and every report is permission-gated, individually. A user
  lacking the compliance permission must not receive the compliance widget data —
  not even a count.
- Exports respect the same permission filter as the on-screen report. Exporting
  is not a privilege escalation path.

TESTING REQUIREMENTS
- Test that widget visibility is permission-driven: revoke the permission, the
  widget disappears and its query never runs.
- Test that a manager sees management widgets and a staff user does not.
- Test every report's aggregation returns the same numbers as a hand-computed
  fixture dataset.

ACCEPTANCE CRITERIA
- Dashboard renders in < 500 ms at realistic data volume (measured, not assumed)
- Widget visibility is permission-driven; zero role-slug comparisons
- Every report exports to CSV
- DashboardController no longer contains inline aggregation

EXPECTED OUTPUT
FILES CHANGED, the measured dashboard timing (with the query count), the widget
registry listing, and the export test results.
```

---

## Phase 11 — Security Hardening

```text
OBJECTIVE
OWASP-aligned hardening pass. Measure, then fix.

FILES TO INSPECT FIRST
docs/FUNCTIONAL-GAP-ANALYSIS.md GAP-008, GAP-009, GAP-010, GAP-011, GAP-016
bootstrap/app.php
app/Http/Controllers/Admin/DatabaseBackupController.php
config/{session,sanctum,filesystems}.php
Modules/*/Http/Controllers/**   (every upload call site)
resources/views/errors/**

TASK
1. Named rate limiters via RateLimiter::for(): login, api, api-writes, admin,
   export, search. Apply them with `throttle:` on the relevant route groups.
                                                                   [GAP-009]
2. Security headers middleware: CSP, HSTS, X-Frame-Options,
   X-Content-Type-Options, Referrer-Policy.
3. Sanctum token expiration enforced — config/sanctum.php:53 is null, so tokens
   never expire. Add a token issuance path restricted to super-admin, and audit
   every issuance.
4. ALL uploads route through one shared service: private disk, MIME + extension
   allow-list, size caps, checksum, sanitised stored filename, no execution
   bits. Covers TaskController:359/401, UserController:74/141,
   TaskTransferController.                                        [GAP-011]
5. DatabaseBackupController: super-admin only (not `admin`), audited, encrypted
   at rest, with a retention policy.                             [GAP-008]
6. Document the credential rotation procedure. The SMTP password currently sits
   in .env and an untracked .env.backup — rotate it and record the procedure.
                                                                   [GAP-010]
7. Production env template: SESSION_SECURE_COOKIE=true, APP_DEBUG=false,
   LOG_LEVEL=info, HTTPS enforced.
8. Configure trustProxies correctly for the deployment topology.
9. Run `composer audit` and `npm audit`; report every finding and its
   disposition. Do not silently bump major versions.
10. FULL IDOR SWEEP: walk all ~120 routes and record, for each, which policy or
    gate governs it. Produce the table. Any route with no authorization decision
    is a finding — fix it or report it.
11. Review error pages for information disclosure (stack traces, DB messages,
    .env values).

DATABASE CHANGES
None, unless the backup feature needs a retention table — then additive only.

IMPLEMENTATION REQUIREMENTS
- Rate limits must be keyed per USER for authenticated routes, not per IP.
- The security headers middleware must not break AdminLTE, Alpine, TomSelect,
  flatpickr or DataTables. Test the app actually renders with CSP on.
- Do not remove existing security controls while adding new ones.

SECURITY REQUIREMENTS
This IS the security phase. Everything above is mandatory. Additionally:
- No secret in code, config, or committed files. No exceptions.
- Dependency findings must be reported with CVE, severity and disposition —
  fixed / accepted-with-rationale / deferred.

TESTING REQUIREMENTS
- Rate limiter test: the Nth+1 request within the window returns 429.
- Upload test: a disallowed MIME is rejected; a `.php` disguised as an image is
  rejected; a stored file is not reachable by direct URL.
- Backup test: an `admin` role user receives 403; a super-admin receives a file;
  an audit row exists either way.
- IDOR sweep test: for every route in the sweep table, an unauthorized user
  receives 403/404.

ACCEPTANCE CRITERIA
- The full 120-route IDOR sweep table is produced with zero unauthorized routes
- composer audit and npm audit findings are each dispositioned in writing
- Upload validation is centralised — no controller writes a file directly
- Security headers are present and the app still renders correctly

EXPECTED OUTPUT
FILES CHANGED, the complete IDOR sweep table, the dependency audit findings with
dispositions, and the rate-limiter test results.
```

---

## Phase 12 — REST API

```text
OBJECTIVE
A real, versioned REST API at /api/v1. Sanctum is installed and no code path
issues a token — fix that first.

FILES TO INSPECT FIRST
routes/api.php                                     (8 lines today)
Modules/*/routes/api.php                            (stubs; the api.api prefix bug)
config/sanctum.php:53                               (expiration is null)
app/Policies/**                                     (Phase 2 output)
Modules/Todos/app/Http/Requests/**                  (source of truth for validation)
docs/TODO-MODULE-SPECIFICATION.md §9

TASK
1. routes/api.php -> /api/v1 group. Confirm the Phase 2 prefix fix landed; the
   module RouteServiceProvider previously produced /api/api/…      [GAP-037]
2. API Resources: TodoResource, TodoCollection, TaskResource, MeetingResource,
   ObligationResource, CommentResource. Never expose recurrence_rule internals,
   deleted_at, or audit columns.
3. Read endpoints for all four modules first: GET /api/v1/{tasks,todos,meetings,
   obligations} and /{id}.
4. Write endpoints for To-Dos first (see §9.1), then the rest if budget allows.
5. Consistent envelope: collections `{data, meta:{current_page,last_page,per_page,
   total}}`; single resources `{data}`. Errors use a STABLE `code` string
   (validation_failed, not_found, forbidden, unauthenticated).
6. Filtering, sorting, pagination — sort columns whitelisted via the enum.
7. Rate limiting per token, plus throttle:api-writes on mutating routes.
8. Token issuance endpoint behind auth:sanctum, restricted to super-admin, with
   an enforced expiry and an audit row. There is currently no code that issues
   a token at all.
9. OpenAPI 3.1 spec GENERATED from the Form Requests and Resources. Do not
   hand-write it.
10. API feature tests: auth, authorization, validation, pagination, filtering,
    rate limiting.

DATABASE CHANGES
None.

IMPLEMENTATION REQUIREMENTS
- Web and API must share one policy layer. No web-only authorization shortcuts
  and no API-only ones.
- Resources define the response shape; never return a raw model.
- Every mutating endpoint reuses the same service the web controller uses.
  Do not duplicate business logic in an API controller.
- Version from day one. /api/v1 must be able to gain /api/v2 without breaking v1.

SECURITY REQUIREMENTS
- Sanctum token expiration must be enforced, not null.
- Rate limit per user/token, not per IP, once tokens are in use.
- Mass assignment: the API uses the same Form Requests as the web, so the same
  rules apply. No looser validation on the API.
- Error responses must not leak stack traces, SQL or model internals.

BACKWARD COMPATIBILITY
No existing route may change. The API is purely additive.

ACCEPTANCE CRITERIA
- /api/v1 read endpoints exist for all four modules, permission-filtered
- To-Do write endpoints exist and reuse the same services as the web layer
- Tokens expire; unauthenticated and expired-token requests return 401
- An API user cannot see a record the web UI would hide
- OpenAPI 3.1 spec publishes and matches the implemented routes
- Pint clean, API tests green

EXPECTED OUTPUT
FILES CHANGED, the endpoint table, the envelope and error-format samples, the
token issuance flow, and the API test results.
```

---

## Phase 13 — Testing Programme

```text
OBJECTIVE
A suite the team trusts. Close the gap between "8 tests" and a regression net.

FILES TO INSPECT FIRST
phpunit.xml, tests/**, Modules/*/tests/**
docs/FUNCTIONAL-GAP-ANALYSIS.md GAP-005
IMPLEMENTATION-ROADMAP.md Phase 13 coverage table

TASK
1. Wire Modules/*/tests into phpunit.xml. Today ONLY tests/ is registered, so
   every module's tests are silently ignored.
2. Database factory coverage for EVERY model. Any model without a factory gets
   one.
3. Policy test suite: one test per policy action, covering allowed and denied.
   Generate the matrix; do not hand-pick cases.
4. N+1 detection test using DB::listen — assert query count does not grow as
   the related record count rises, on every list screen.
5. Notification tests with Notification::fake() + Mail::fake() + Queue::fake():
   idempotency, queueing, preferences, dedupe.
6. CI: `php artisan migrate:fresh --env=testing` then `phpunit` on every push,
   on BOTH sqlite and mysql.

COVERAGE TARGETS
- Policies / authorization ....... 100% of actions
- To-Do module ................... full CRUD + lifecycle + recurrence + notifications
- Tasks / Meetings / Obligations . happy path + key failure modes
- Notifications .................. idempotency, queueing, preferences
- Database ...................... relationship + constraint integrity
- API ............................ auth, authz, validation, pagination
- Regression ..................... smoke test across all ~120 routes

DATABASE CHANGES
None. Factories and seeders only.

IMPLEMENTATION REQUIREMENTS
- Every test uses RefreshDatabase and factories. No raw INSERTs, no DB::table().
- No test may be skipped or marked incomplete to make the suite green. A skip is
  a finding, not a fix — report it instead.
- No test may depend on execution order.
- Read the project's testing-best-practices skill before writing.

SECURITY REQUIREMENTS
- Authorization tests are a security control, not a formality: for each policy,
  at least one test proving a NON-privileged user is denied.
- No test may assert on hardcoded secret values or log sensitive data.

BACKWARD COMPATIBILITY
Do not weaken or delete any existing test. If behaviour changed intentionally in
Phase 8, the test is UPDATED with the reason recorded in the commit message.

ACCEPTANCE CRITERIA
- Modules/*/tests actually execute (prove with a deliberately failing test)
- vendor/bin/phpunit is green with ZERO skips
- Every model has a factory
- The N+1 test fails if an eager load is removed (prove it is real)
- CI runs migrate:fresh + phpunit on both drivers

EXPECTED OUTPUT
FILES CHANGED, the before/after test counts (total, passing, skipped), the policy
matrix coverage table, and the CI workflow definition.
```

---

## Phase 14 — Performance Optimisation

```text
OBJECTIVE
MEASURE FIRST, THEN OPTIMISE. No speculative infrastructure. No Redis without a
measurement that justifies it.

FILES TO INSPECT FIRST
IMPLEMENTATION-ROADMAP.md Phase 14
database/migrations/*                                (index inventory)
app/Http/Controllers/**, Modules/*/app/Http/Controllers/**
config/{cache,database,queue,filesystems}.php

TASK
1. BASELINE FIRST. Record query count and response time per page (dashboard,
   every list, every detail, every report) at realistic data volume. Record the
   numbers. Everything below is justified against this baseline.
2. EXPLAIN every hot query. Add an index ONLY where the plan is actually wrong.
3. Eager-load audit across all controllers. Fix genuine N+1s.
4. Model::preventLazyLoading() in the local environment (never production —
   it must not break the app for a missing with()).
5. Cache dashboard widgets and reference data (users, departments, locations)
   with explicit invalidation.
6. Move cache/session/queue to Redis IF AND ONLY IF measurement justifies it.
   Report the measurement either way.
7. Chunk large reports; queue PDF generation.
8. composer dump-autoload -o; config:cache, route:cache, view:cache — and
   verify each actually works in this app before committing them.

DATABASE CHANGES
Additive indexes ONLY, each justified by an EXPLAIN before and after. Record
the before/after plan for every index you add.

IMPLEMENTATION REQUIREMENTS
- NON-GOALS: read replicas, a search cluster, Redis adoption, CDN. All require
  measurement that this phase will produce.
- Every optimisation must be accompanied by the measurement that justifies it,
  and the improvement it produced.
- Do not trade correctness for speed. If an optimisation requires stale data or
  a race, say so and do not do it.

SECURITY REQUIREMENTS
- Cached data must not leak across users. Cache keys MUST include the user id
  and the permission set. A shared cache key on permission-gated data is a
  vulnerability, not a bug.
- Never cache authenticated HTML fragments in a shared store.

TESTING REQUIREMENTS
- The full suite must pass identically before and after. Performance changes
  that break a test are rejected.
- A test asserting the cache key includes the user id.
- Regression guard: the Phase 13 N+1 tests must still catch a removed eager load.

ACCEPTANCE CRITERIA
- A before/after performance table exists with real measurements
- Every index added is justified by an EXPLAIN diff
- Dashboard and list pages measurably improved, or the table honestly says "no
  change needed"
- Full test suite green, zero skips

EXPECTED OUTPUT
The before/after measurement table, the EXPLAIN diffs, the list of changes NOT
made because measurement did not justify them, and the Redis recommendation with
its evidence.
```

---

## Phase 15 — Production Readiness

```text
OBJECTIVE
Ship it. Close out the debt, document the irreversible decisions, and leave an
operable system behind.

FILES TO INSPECT FIRST
docs/IMPLEMENTATION-ROADMAP.md Phase 15
docs/DATABASE-ARCHITECTURE.md §5, §6
All documents in docs/
database/migrations/*drop_*, *tblAccountInfo*

TASK
1. Delete the remaining dead code: PermissionController, config/menu.php,
   meeting_versions, and the approval_workflows / _steps tables — ONLY after
   confirming zero references across app/, Modules/, routes/, config/, views/.
   Show the grep output that proves it.
2. tblAccountInfo: make an explicit keep-or-delete decision and record the
   rationale. Do not delete its migrations by default — that rewrites history for
   a table that is already harmless.
3. Document every irreversible migration. Add a guard note so `migrate:rollback`
   is never run past that point in production without an explicit human
   decision.                                                       [GAP-016]
4. Production env template: APP_DEBUG=false, LOG_LEVEL=info, SESSION_SECURE_COOKIE,
   HTTPS, correct timezone, correct queue/cache drivers.
5. Commit a cron definition with locking (withoutOverlapping, onOneServer), the
   correct timezone, and a supervisor config for the queue worker.
6. Health-check endpoint + monitoring for queue depth, failed jobs and DB
   connectivity.
7. Backup AND RESTORE runbook — including the backup feature's own encryption.
   An untested backup is not a backup.
8. Onboarding and operations documentation.
9. Full regression pass across all routes; record the performance baseline.
10. Final security review against the Phase 11 checklist.
11. RECONCILE THE DOCUMENTATION AGAINST REALITY. Every claim in the five
    discovery documents must either still be true or be corrected. A stale
    architecture document is worse than none.

DATABASE CHANGES
Dead-table drops only, each guarded and each preceded by a zero-reference proof.

IMPLEMENTATION REQUIREMENTS
- Every deletion is preceded by a grep proving zero references. Show the output.
- No destructive migration runs without a documented, tested restore path.
- Do not "just enable" debug mode to diagnose anything. Leave the app in its
  production configuration.

SECURITY REQUIREMENTS
- Final pass over every Phase 11 finding: resolved, or documented as accepted
  with a named owner and a rationale.
- Confirm no secret is committed and .env.example is complete.
- Confirm APP_DEBUG=false in the production template.

ACCEPTANCE CRITERIA
- Zero dead code remains, or each remaining item has a written rationale
- Cron definition committed and verified to fire
- Restore runbook exists AND has been executed at least once against a copy
- Full regression suite green on both drivers
- All five discovery documents reconciled against the final codebase

EXPECTED OUTPUT
FILES CHANGED, the zero-reference grep evidence, the final security checklist with
dispositions, the documented reconciliation of the discovery documents, and the
go-live / rollback decision record.
```

---

## Appendix A — Phase Dependency Graph

```text
Phase 0  Discovery ──────────► Phase 1  Gap analysis ──────────┐
                                                                │
Phase 2  Core hardening ◄────────────────────────────────────────┘   ⛔ BLOCKS EVERYTHING
   │
   ├──► Phase 3  To-Do DB ──► Phase 4  To-Do backend ──┬──► Phase 5  To-Do UI ──┐
   │                                                  │                          │
   │                                                  └──► Phase 6  Notify/Recur  │
   │                                                                  │           │
   │                                                                  ▼           ▼
   │                                              Phase 7  Cross-module ◄─────────┘
   │                                                     │
   ├──► Phase 9  Global search ◄──────────────────────────┤
   │                                                     ▼
   │                                     Phase 10  Dashboard & Reporting
   │                                                     │
   │                                        Phase 14 ◄──┤
   │                                                     │
   ├──► Phase 11  Security hardening                      │
   │                                                     │
   ├──► Phase 12  REST API ◄──────────────────────────────┤
   │                                                     │
   └──► Phase 8  Task/Meeting/Obligation ◄────────────────┘
                     │
                     ├──► Phase 13  Testing (needs ALL)
                     └──► Phase 15  Production readiness (needs ALL)
```

## Appendix B — Quick Reference: Which Document Answers What

| Question | Document |
|---|---|
| What exists today, what is broken? | `ARCHITECTURE-ASSESSMENT.md` |
| What is missing, ranked? | `FUNCTIONAL-GAP-ANALYSIS.md` (GAP-001…GAP-050) |
| How should the To-Do module work? | `TODO-MODULE-SPECIFICATION.md` |
| Exact column/index definitions? | `DATABASE-ARCHITECTURE.md` (§4 for new, §5 for changes) |
| What order, in what sequence? | `IMPLEMENTATION-ROADMAP.md` |
| **What prompt do I paste?** | **this file, one phase at a time** |

## Appendix C — Red Flags — Stop And Report, Do Not Fix

These are known, but if an agent encounters one of them mid-task it must **report,
not silently repair**, because a silent repair hides a regression:

1. A migration that has already run in production needing to be edited.
2. A `cascadeOnDelete` on an ownership column outside the Phase 2.15 list.
3. An existing test failing — that is a regression until proven otherwise.
4. A route name or URL changing during a Phase 2.17 route move.
5. Stored enum values that do not map onto the new `app/Enums` cases.
6. Any code path that would send mail synchronously.
7. Any permission string being removed from `ProjectPermissionSeeder`.
8. Any table being dropped without the zero-reference proof.
