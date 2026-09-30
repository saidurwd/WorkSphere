# Implementation Roadmap

> Phases 0–15. **Do not implement all at once.**
> Each phase is small, testable, reversible and independently verifiable.
> Copy-paste prompts for each phase are in `AI-IMPLEMENTATION-PROMPTS.md`.

---

## Phase Overview

| Phase | Name | Depends on | Risk | Gate to pass |
|---|---|---|---|---|
| 0 | System Discovery | — | None | ✅ Complete |
| 1 | Architecture & Gap Analysis | 0 | None | ✅ Complete |
| 2 | **Shared/Core Hardening** | 1 | Medium | Fresh SQLite migrate passes; policies exist; tests green |
| 3 | To-Do Database Architecture | 2 | Low | Migrations reversible + tested |
| 4 | To-Do Backend | 3 | Medium | Policy + Form Requests + service tests |
| 5 | To-Do UI | 4 | Medium | All views use existing components |
| 6 | To-Do Notifications & Recurrence | 4 | Medium | All sends queued; recurrence tests pass |
| 7 | Cross-Module Integration | 4, 5 | Medium | `work_items` view feeds dashboard |
| 8 | Task/Meeting/Obligation Improvements | 2, 7 | High | No regression in existing tests |
| 9 | Global Search | 2, 4 | Medium | Permission-aware results |
| 10 | Dashboard & Reporting | 7, 8, 9 | Medium | Role-aware widgets |
| 11 | Security Hardening | 2 | Low | OWASP checklist pass |
| 12 | API | 2, 4 | Medium | OpenAPI published |
| 13 | Testing | all | Low | Coverage floor met |
| 14 | Performance Optimisation | 8, 10 | Low | Measured, not guessed |
| 15 | Production Readiness | all | Medium | Runbook complete |

**The critical path is Phase 2.** Everything else is blocked on it.

---

## Phase 2 — Shared / Core Hardening

**Objective:** Build the safety net. Without this, every later phase is unverifiable
and unmaintainable.

**Why first:** three Critical gaps make the platform unshippable — no authorization
(IDOR on Meetings and Obligations), no test database (migrations cannot run on SQLite),
and non-functional audit logging.

### Tasks

| # | Task | Gap | Effort |
|---|---|---|---|
| 2.1 | Add the two missing `use` imports in `LoginRequest` | GAP-007 | 5 min |
| 2.2 | Fix `User::tasks()/responsibleTasks()/projects()` imports; add `User::employee` | GAP-043 | 15 min |
| 2.3 | Fix `$fillable` on `User` (`status`) and `Role` (`description`); add `preventSilentlyDiscardingAttributes()` in local/testing | GAP-044 | 20 min |
| 2.4 | Fix `SecurityEventController` `$events` shadowing | GAP-045 | 5 min |
| 2.5 | **Make all migrations SQLite-portable** | GAP-004 | 6–10 h |
| 2.6 | Fix `role_permissions` FK ordering + `HAZIRA` guard | DB-01, DB-02 | 30 min |
| 2.7 | Verify `migrate:fresh` on empty MySQL **and** SQLite | DB-01 | 30 min |
| 2.8 | Build `app/Enums/`: `Priority`, `WorkItemStatus`, `RecurrenceFrequency`, `NotificationChannel`, `Role` | GAP-020 | 1 h |
| 2.9 | `User::can(string $permission): bool` + cached permission lookup | GAP-002 | 1.5 h |
| 2.10 | `app/Policies/` for Task, Project, Meeting, Obligation, User, Role + registration | GAP-001 | 3 h |
| 2.11 | Wire `$this->authorize()` into **every** mutating action in all 4 modules | GAP-003 | 4 h |
| 2.12 | `AuditLogger` + `ActivityLogger` services; register model observers | GAP-006 | 3 h |
| 2.13 | Add `ip_address` + `user_agent` to `tyro_audit_logs`; make it append-only | GAP-006 | 1 h |
| 2.14 | Add indexes to `activity_logs`, `obligation_renewals`, `obligation_documents`, `notification_rules`, `escalation_rules`, `tasks` | GAP-013 | 1 h |
| 2.15 | Soften `cascadeOnDelete` on ownership columns → `nullOnDelete` | GAP-012 | 1 h |
| 2.16 | Fix module `RouteServiceProvider` API prefix (`/api/api/` bug) | GAP-017 | 15 min |
| 2.17 | Migrate routes into `Modules/*/routes/web.php`, module by module | GAP-017 | 2 h |
| 2.18 | Move module migrations into `Modules/*/database/migrations/` | GAP-017 | 1 h |
| 2.19 | Establish a test baseline: auth, RBAC, policies, task/meeting/obligation CRUD | GAP-005 | 6 h |
| 2.20 | Delete dead code: `PermissionController`, `config/menu.php`, unwired `approval_workflows` | GAP-042 | 1 h |

### Deliverables

`app/Enums/*`, `app/Policies/*`, `app/Services/{AuditLogger,ActivityLogger}.php`,
`app/Observers/*`, `User::can()`, permissions in `ProjectPermissionSeeder`, portable
migrations, a green test suite.

### Definition of Done

- `DB_CONNECTION=sqlite php artisan migrate:fresh` succeeds.
- `DB_CONNECTION=mysql php artisan migrate:fresh` succeeds on an empty DB.
- `vendor/bin/phpunit` — all green, **zero errors, zero skips**.
- A feature test proves a non-assignee receives 403 on a Task **and** a Meeting **and**
  an Obligation.
- A feature test proves `AuditLog::count()` increases after a task update.
- A test proves `User::can('tasks.update')` is enforced.

### Risks & Rollback

| Risk | Mitigation | Rollback |
|---|---|---|
| Portable-migration rewrite breaks MySQL | Test both drivers; MySQL in CI too | Each file is independently revertible |
| Policy wiring changes user-visible behaviour | Feature test per route | Revert the `authorize()` call per action |
| Softening cascades orphans data | Backfill script first; FKs nullable | `down()` restores `cascadeOnDelete` |
| Route migration breaks named routes | `route:list` diff before/after | `git revert` — routes are additive |

### Files Likely to Change

`app/Models/{User,Role}.php`, `app/Http/Requests/Auth/LoginRequest.php`,
`app/Http/Controllers/Admin/*.php`, `app/Providers/AppServiceProvider.php`,
`app/Providers/EventServiceProvider.php`, `app/Policies/**`, `app/Enums/**`,
`app/Services/**`, `bootstrap/app.php`, `routes/web.php`,
`Modules/*/routes/web.php`, `Modules/*/app/Providers/RouteServiceProvider.php`,
`database/migrations/**`, `database/seeders/ProjectPermissionSeeder.php`,
`tests/**`, `phpunit.xml`.

---

## Phase 3 — To-Do Database Architecture

**Objective:** Create the To-Do tables and the shared platform tables. **Purely additive
— nothing existing changes.**

### Tasks

1. `create_todos_table` — exactly as specified in `DATABASE-ARCHITECTURE.md` §4.1
2. `create_todo_watchers_table` (§4.2)
3. `create_todo_checklist_items_table` (§4.3)
4. `create_todo_links_table` (§4.4)
5. `create_comments_table` (§4.5)
6. `create_attachments_table` (§4.6)
7. `create_tags_and_taggables_tables` (§4.7)
8. `create_reminders_table` (§4.8)
9. `create_notification_preferences_table` (§4.10)
10. `create_notifications_table` (Laravel Notifications)
11. `add_polymorphic_columns_to_activity_logs` (§4.12)
12. `add_ip_and_user_agent_to_tyro_audit_logs`
13. `create_work_items_view` — guarded by driver; skipped on SQLite
14. Register `Todos` in `modules_statuses.json`
15. Seed `todos.*` permissions
16. `TodoFactory`, `UserFactory` coverage check, `TodoSeeder` (small dev dataset)

### Definition of Done

- `migrate:fresh` succeeds on both drivers.
- Every migration has a working `down()`; `migrate:rollback` then `migrate` is clean.
- A test asserts each index exists (via `Schema::hasIndex` or an information-schema
  query skipped on SQLite).
- No existing table altered or dropped.

### Risks & Rollback

Purely additive — `migrate:rollback` removes everything cleanly. The only risk is a
typo in an index name, which `down()` handles.

---

## Phase 4 — To-Do Backend

**Objective:** Models, policies, requests, services, events. No UI yet.

### Tasks

1. `php artisan make:module Todos` — verify against `Modules/Meetings` layout
2. `Todo` model — casts to `WorkItemStatus`/`Priority`, `HasTags`, `HasComments`,
   `HasAttachments`, `LogsActivity`, `SoftDeletes`
3. Relations: `assignee`, `creator`, `department`, `watchers`, `checklistItems`,
   `links`, `reminders`, `tags`, `comments`, `attachments`
4. Scopes: `scopeForUser`, `scopeActive`, `scopeOverdue`, `scopeDueBetween`,
   `scopeRecurring`
5. `TodoPolicy` per `TODO-MODULE-SPECIFICATION.md` §4.1; register in the provider
6. Form Requests: `StoreTodoRequest`, `UpdateTodoRequest`, `AssignTodoRequest`,
   `CompleteTodoRequest`, `RecurrenceTodoRequest`, `StoreTodoCommentRequest`
7. `TodoService` — `create`, `update`, `assign`, `complete`, `reopen`, `archive`,
   `restore`, `destroy`; each in a `DB::transaction` writing activity logs
8. `TodoLinkService` — attach/detach with reverse traversal
9. 12 Events + 12 Listeners (`ShouldQueue`)
10. Jobs: `SendTodoAssignedJob`, `SendTodoCompletedJob`, `SendTodoOverdueJob`,
    `SendTodoReminderJob`, `SendTodoMentionJob`
11. `TodoNotification` (Laravel Notification, `database` + `mail` channels)
12. `TodoRecurrenceService` per spec §5
13. `TodoReportService` — aggregate queries only
14. `modules_statuses.json` registration

### Definition of Done

- Unit tests for `TodoService` (every transition, including illegal ones) and
  `TodoRecurrenceService` (each frequency, skip, limit, end date).
- Feature tests for policy: non-assignee 403; cross-department Team To-Do invisible;
  `view_all` sees all.
- No inline validation anywhere in the module.
- Every mutation writes an `activity_logs` row.

---

## Phase 5 — To-Do UI

**Objective:** The module's UI, built entirely from the existing 31 Blade components.

### Tasks

1. `TodoController` (resource) with server-side yajra DataTables
2. Views: `index`, `create`, `edit`, `show`, `_form`, `_row`, `calendar`, `reports/*`
3. **Quick capture** — title-only input on the index page, creates on Enter
4. Shared `StatusBadge::variant()` helper; replace the duplicated `match` arms
5. Checklist, watchers, links, comments, attachments, activity timeline on `show`
6. Empty/loading/error states via `x-empty-state`, `x-alert`
7. Bulk actions: complete, reassign, archive, tag
8. Navigation entry in `config/navigation.php`
9. Accessibility pass: `aria-label`, `aria-live`, keyboard nav, WCAG AA contrast
10. Remove client-side DataTables double-pagination (server-side only)

### Definition of Done

- Every page renders with zero Blade errors.
- No new CSS framework or JS library.
- Zero raw `{{ }}`-escaping violations; descriptions escaped.
- Manual accessibility check + Lighthouse on the index page.
- `npm run build` succeeds.

---

## Phase 6 — To-Do Notifications & Recurrence

**Objective:** Wire the shared reminder pipeline and the recurrence engine.

### Tasks

1. `reminders:dispatch` command — every minute, chunk 200, idempotent
2. `todos:overdue`, `todos:due-soon`, `todos:generate`, `todos:skip` commands
3. All sends dispatched as jobs; **zero** synchronous mail
4. `dedupe_key` on every notification log
5. `notification_preferences` wired into `shouldNotify()`
6. Navbar reads the `notifications` table instead of three log tables
7. Replace the five module-specific daily commands with `reminders:dispatch`
   (keep them until Phase 8 confirms parity)
8. `->everyMinute()->withoutOverlapping()->onOneServer()` on all schedules
9. Fix app timezone from UTC to the business timezone

### Definition of Done

- Re-running `todos:overdue` produces no duplicate emails (dedupe test).
- No mail is sent inside an HTTP request (test with `Mail::fake()` + queue assertion).
- A user can disable a notification type and stops receiving it.

---

## Phase 7 — Cross-Module Integration

**Objective:** Make the To-Do a first-class citizen alongside Tasks, Meetings and
Obligations.

### Tasks

1. `work_items` view wired into the dashboard query layer
2. Meeting action item → To-Do link (one click)
3. Obligation → To-Do link
4. Task → To-Do link
5. Unified "My Work" page: Tasks + To-Dos + Action Items + Obligations in one list
6. Global tags working across To-Dos and Meetings
7. Unified comments and attachments across modules
8. `RecurrenceService` extracted to `app/Services/`; Meetings and Obligations migrate onto it

### Definition of Done

Every module's detail page links to and from a To-Do; navigation works in both
directions; no duplicated concept remains.

---

## Phase 8 — Task / Meeting / Obligation Improvements

**Objective:** Close the highest-value functional gaps in the existing modules.
**Risk: High — this is the first phase that changes working behaviour.**

### Tasks — Tasks (2 h–3 d)

- Parent/subtask hierarchy (`parent_id` + scope)
- Watchers
- Widen status to match `meeting_action_items`; nullable `due_date`
- `estimated_minutes` / `actual_minutes` + `time_entries`
- Tags via the shared `taggables`
- Migrate `task_remarks` → shared `comments`
- Activity timeline per task

### Tasks — Meetings (2–3 d)

- Wire `meeting_templates` (CRUD + "schedule from template")
- Wire `MeetingRecurrenceService` + a cron for occurrence generation
- `meeting_attachments` → shared `attachments`
- `meeting_tags`/`meeting_tag_map` → shared `taggables`
- `location_id` FK
- Tighten attachment cascade behaviour
- Action items → tasks (resolve GAP-027) — **decide migrate vs. keep separate first**

### Tasks — Obligations (1–2 d)

- `escalation_rules` scoped by department/company
- `approval_workflows` + `entity_type`/`entity_id` — **or delete** (GAP-022/C-042)
- `obligation_activity_logs` → shared `activity_logs`; add array casts
- `obligation_documents` → shared `attachments`
- Queue all sends (GAP-022)

### Tasks — Identity (1 d)

- Admin CRUD for `employees`, `companies`, `departments`, `locations` (GAP-041)
- `users.employee_id` NOT NULL after backfill
- Password policy, session controls, account status enforcement

### Definition of Done

Existing tests still green. New tests for every behaviour change. Rollback migration
written for each schema change. No functional regression in any of the ~120 existing
routes.

---

## Phase 9 — Global Search

**Objective:** Permission-aware search across all modules.

### Tasks

1. `App\Search\SearchIndex` — a registry of searchable entities
2. MySQL `FULLTEXT` on `tasks`, `todos`, `meetings`, `obligations`, `task_projects`
3. `SearchController` with grouped, permission-filtered results
4. Filters: module, status, date range, owner
5. Facets and counts
6. Search-as-you-type (debounced)
7. Search **logging** (`search_logs` or reuse `activity_logs`) for relevance analysis

### Non-goals

**No Elasticsearch, Meilisearch or Algolia.** MySQL FULLTEXT is sufficient at this
scale. Revisit only when measured latency demands it.

---

## Phase 10 — Dashboard & Reporting

**Objective:** Role-aware enterprise dashboard and shared reporting.

### Tasks

1. `DashboardWidget` interface + widget registry
2. Personal widgets: My To-Dos, My Tasks, Upcoming Meetings, Overdue, Today's
   Activities, Upcoming Deadlines
3. Management widgets: Team Workload, Overdue Items, Completion Rate, Task
   Distribution, Department Performance
4. Compliance widgets: Upcoming/Overdue Obligations, Critical Deadlines
5. Widget visibility driven by **permissions**, not hardcoded role slugs
6. Per-widget caching keyed by user + permission set
7. Shared `ReportService`; Task reports added (none exist today)
8. CSV export for every report; PDF via the shared export service

### Definition of Done

Dashboard renders in < 500 ms at realistic data volume; widget visibility is
permission-driven; every report exports.

---

## Phase 11 — Security Hardening

**Objective:** OWASP-aligned hardening pass.

### Tasks

1. Named rate limiters: `login`, `api`, `api-writes`, `admin`, `export`, `search`
2. Security headers middleware (CSP, HSTS, `X-Frame-Options`, `X-Content-Type-Options`,
   `Referrer-Policy`)
3. Sanctum token expiration enforced; `super-admin` token issuance with audit
4. All uploads → private disk, MIME + extension allow-list, size caps, checksum
5. `DatabaseBackupController`: `super-admin` only, audited, encrypted, retention policy
6. `.env` credential rotation; secrets documented
7. `SESSION_SECURE_COOKIE`, `APP_DEBUG=false`, `LOG_LEVEL=info` in production env
8. `trustProxies` configuration
9. Dependency audit (`composer audit`, `npm audit`)
10. Full IDOR sweep — every route re-tested for object-level authorization
11. Error pages reviewed for information disclosure

---

## Phase 12 — API

**Objective:** A real, versioned REST API.

### Tasks

1. `routes/api.php` → `/api/v1` group
2. API Resources: `TodoResource`, `TaskResource`, `MeetingResource`,
   `ObligationResource`, `CommentResource`
3. Read endpoints for all four modules
4. Write endpoints for To-Dos first
5. Consistent envelope + stable error codes
6. Filtering/sorting/pagination with whitelists
7. Rate limiting per token
8. Token issuance endpoint (auth:sanctum) — currently **no code issues tokens at all**
9. OpenAPI 3.1 spec generated from Form Requests + Resources
10. API feature tests covering auth, authorization, validation, pagination

### Non-goals

GraphQL. Mobile clients. Webhooks — deferred to a future phase.

---

## Phase 13 — Testing

**Objective:** A suite the team trusts.

### Coverage targets

| Area | Target |
|---|---|
| Policies / authorization | 100% of actions |
| To-Do module | Full CRUD + lifecycle + recurrence + notifications |
| Tasks / Meetings / Obligations | Happy path + key failure modes |
| Notifications | Idempotency, queueing, preferences |
| Database | Relationship + constraint integrity |
| API | Auth, authz, validation, pagination |
| Regression | Smoke test across all ~120 routes |

### Tasks

1. Wire `Modules/*/tests` into `phpunit.xml` (currently only `tests/` is registered)
2. Database factory coverage for every model
3. Policy test suite generated per action
4. N+1 detection test using `DB::listen`
5. Notification tests with `Notification::fake()` + `Mail::fake()`
6. CI: `migrate:fresh --env=testing` + `phpunit` on every push

---

## Phase 14 — Performance Optimisation

**Objective:** **Measure, then optimise.** No speculative infrastructure.

### Tasks

1. Baseline: query counts and response times per page
2. `EXPLAIN` every hot query; add indexes only where the plan is wrong
3. Eager-load audit across all controllers
4. `Model::preventLazyLoading()` in development
5. Cache dashboard widgets and reference data (`users`, `departments`, `locations`)
6. Move cache/session/queue to Redis **if** measurement justifies it
7. Chunk large reports; queue PDF generation
8. `composer dump-autoload -o`, `php artisan config:cache`, `route:cache`, `view:cache`

### Non-goals

Redis adoption, read replicas, or a search cluster **without** measurement.

---

## Phase 15 — Production Readiness

**Objective:** Ship it.

### Tasks

1. Delete remaining dead code (`tblAccountInfo` decision, `approval_workflows`)
2. Document the irreversible migrations; add a `migrate:rollback` guard note
3. Production env template: `APP_DEBUG=false`, `LOG_LEVEL=info`, secure cookies, HTTPS
4. Committed cron definition with locking and correct timezone
5. Health-check endpoint; queue/DB/failed-job monitoring
6. Backup and restore runbook (including the backup feature's own encryption)
7. Onboarding and operations documentation
8. Full regression pass; performance baseline recorded
9. Final security review
10. Reconcile this roadmap against reality — update docs

---

## Cross-Cutting Rules — Apply to Every Phase

1. **Inspect before modifying.** Read the file and its callers first.
2. **Do not overwrite working functionality without justification.**
3. **Reuse existing services and components** before creating new ones.
4. **Follow existing conventions** unless they are demonstrably problematic —
   `Modules/Meetings` is the reference layout.
5. **Migrations only** for schema changes. Never hand-edit the database.
6. **Never modify production data manually.**
7. **Never drop columns or tables** without a migration and an impact analysis.
8. **Maintain backward compatibility** wherever possible.
9. **Add tests for new functionality.** Update existing tests when behaviour
   intentionally changes.
10. **Laravel best practices.** Thin controllers, Form Requests, Policies/Gates,
    Jobs/Queues, Events/Listeners.
11. **No unnecessary abstractions.** No repositories, no service layers for
    single-method classes.
12. **Transactions for multi-step writes.**
13. **No sensitive data in logs.**
14. **`vendor/bin/pint --dirty`** after every PHP change.
15. **Every step small, testable, reversible, independently verifiable.**