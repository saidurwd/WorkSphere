# Functional Gap Analysis

> Every gap below was verified by inspection. Nothing is assumed missing because it is
> "standard" — if it exists, it is marked ✅ and left alone.
> Priority: **Critical / High / Medium / Low**. Phase references are to
> `IMPLEMENTATION-ROADMAP.md`.

---

## Gap Register

| Gap ID | Area | Current State | Gap | Target State | Priority | Business Impact | Technical Impact | Recommendation | Dependencies | Phase |
|---|---|---|---|---|---|---|---|---|---|---|
| **GAP-001** | Security | No Gates, Policies or `authorize()` calls exist anywhere | No authorization layer | Policy per model + Gate per action, enforcing the 53 seeded permissions | **Critical** | Any user can act on any record; cannot sell to enterprise | Every module inherits the flaw; audit becomes undeliverable | Build `Policies/` + `User::can()` on existing RBAC tables | GAP-002 | 2 |
| **GAP-002** | Security | `permissions` table seeded with 53 strings, rendered on a page | Permissions are never enforced | Each permission string gates a named action | **Critical** | RBAC UI promises control it does not deliver | Seeder is currently dead weight | Wire permissions into Gates; retire unused permission strings | — | 2 |
| **GAP-003** | Security | `MeetingController`/`ObligationController` scope list queries only | No object-level authorization on `show/edit/update/destroy` | `authorize()` on every mutating action | **Critical** | Direct IDOR — edit any meeting/obligation by ID | Data-integrity breach | Add policies; call `authorize()` in every action | GAP-001 | 2 |
| **GAP-004** | Testing | 68 migrations use `ENUM`, `->after()`, raw `ALTER`, `information_schema` | Cannot migrate to SQLite | Portable migrations → in-memory test DB possible | **Critical** | No regression safety net for 40k LOC | Blocks all future automated testing | Port the migrations | — | 2 |
| **GAP-005** | Testing | 8 tests; 1 pass, 1 fail, 2 error, 4 skip | Effectively no test suite | Feature tests covering auth, RBAC, each module, policies | **Critical** | Any change is a leap of faith | Cannot verify To-Do work | Add tests per module as work lands | GAP-004 | 2, 8, 13 |
| **GAP-006** | Audit | `tyro_audit_logs` has zero write call sites; `activity_logs` written only by a seeder | Audit and activity logging are non-functional | Observers write both automatically | **Critical** | Compliance cannot be evidenced | `admin.audit-logs` always empty | One `AuditLogger` service + model observers | GAP-001 | 2 |
| **GAP-007** | Security | `LoginRequest::recordFailure()` missing two `use` imports | Fatal error on every failed login; security events never recorded | Working failure logging | **Critical** | Brute-force detection is blind | Missing security telemetry | Add imports; add a test | — | 2 |
| **GAP-008** | Security | `DatabaseBackupController` under `admin` role | Any admin can download a full DB dump incl. password hashes and sessions, unlogged | `super-admin` + audited + encrypted | **High** | Credential exfiltration path | PII exposure | Tighten gate, audit the action, encrypt | GAP-006 | 11 |
| **GAP-009** | Security | No `throttle` on any of ~120 routes | No rate limiting except login | Named limiters on auth, admin, API, export | **High** | Brute force, scraping | DoS and enumeration | `RateLimiter::for()` + middleware | — | 11 |
| **GAP-010** | Security | `SESSION_SECURE_COOKIE` unset; live SMTP password in `.env` + `.env.backup` | Session cookie over HTTP; credential on disk | Secure cookie enforced; secrets managed | **High** | Session hijack; credential leak | Compliance failure | Set env; rotate password | — | 11 |
| **GAP-011** | Security | Uploads written to `public` disk; no MIME validation | User files are web-accessible; type validation inconsistent | Private disk + `mimetypes` rules | **High** | Stored-file exposure; webshell upload | RCE risk on misconfigured servers | Route all uploads through one service on a private disk | — | 2, 11 |
| **GAP-012** | Database | `tasks.user_id`, `meetings.organizer_id`, `obligations.owner_user_id` are `cascadeOnDelete` | Deleting a user destroys their work | `nullOnDelete` + soft deletes | **High** | Irreversible data loss | Audit rows orphaned | Reversible migration | — | 2 |
| **GAP-013** | Database | `activity_logs`, `obligation_renewals`, `obligation_documents`, `notification_rules`, `escalation_rules` have no indexes | Admin/report screens full-scan | Index every filter column | **High** | Slow admin pages as data grows | Timeouts | Additive index migrations | — | 14 |
| **GAP-014** | Database | `permissions.permission_name` not unique/indexed; `employees.email` not unique | Natural keys unenforced | Unique constraints on natural keys | **Medium** | Duplicate permission rows | Ambiguous lookups | Additive unique indexes | — | 14 |
| **GAP-015** | Database | `create_role_permissions` FK references `roles` before it exists; duplicate `HAZIRA` column migration | Fresh `migrate` on empty MySQL fails | Migration set is order-correct and idempotent | **High** | Cannot rebuild an environment from scratch | No disaster recovery | Fix FK ordering; guard with `hasTable` | — | 2 |
| **GAP-016** | Database | Three `drop_*` migrations with `FOREIGN_KEY_CHECKS=0`; `down()` drops again | `rollback` past 2026-09-29 loses data permanently | Documented as irreversible; no auto-rollback | **Medium** | Catastrophic if rolled back | Data loss | Add a warning + `migrate:rollback` guard note | — | 15 |
| **GAP-017** | Architecture | All 68 migrations in `database/migrations`; ~120 routes in `routes/web.php`; module route files are stubs | Module boundaries are nominal | Each module owns its routes and migrations | **High** | Change impact unbounded | Microservice extraction impossible | Move routes + migrations per module | GAP-001 | 2 |
| **GAP-018** | Architecture | One Form Request in the whole codebase; zero in modules | All validation inline in controllers | Form Requests per action | **Medium** | Inconsistent rules; untestable | Fat controllers | Introduce per module, incrementally | — | 4, 5, 8 |
| **GAP-019** | Architecture | `TaskController` 403 lines, `DashboardController` 220 lines of aggregation | Business logic in controllers | Query objects / DTOs / thin controllers | **Medium** | Slow to change safely | Duplicated logic | Extract per feature | — | 8, 10 |
| **GAP-020** | Architecture | ENUMs declared 3× (migration, `in:` string, `in_array` whitelist); no PHP enums | Vocabulary drift; reporting corruption | `Priority`, `WorkItemStatus`, `RecurrenceFrequency`, `Channel` enums | **High** | Inconsistent statuses across modules | Unqueryable data | Introduce enums; align `tasks` status with `meeting_action_items` | — | 2 |
| **GAP-021** | Notifications | Laravel Notifications entirely unused; no `notifications` table, no preferences, no broadcasting | No in-app channel, no user preferences, no realtime | In-app via Laravel Notifications + preferences table | **High** | Users cannot control noise | Navbar runs 3 queries per page render | Adopt Notifications; add preferences | GAP-001 | 6 |
| **GAP-022** | Notifications | `NotificationService`/`EscalationService` send mail synchronously from the scheduler | Scheduler blocks on SMTP | All sends queued | **High** | Daily cron failures | Scheduler stalls | Dispatch jobs | — | 6 |
| **GAP-023** | Notifications | 3 identical `*_notification_logs` tables | One concept, three tables | One polymorphic `notification_logs` | **Medium** | Inconsistent delivery reporting | Schema bloat | Consolidate with backfill | — | 6 |
| **GAP-024** | Notifications | `TaskNotificationService::shouldNotify()` always returns `true` | No opt-out | Preference-driven | **Medium** | Notification fatigue | Users mute the app | Implement against preferences | GAP-021 | 6 |
| **GAP-025** | Task | No `parent_id`, no subtasks, no dependencies, no watchers, no effort/time tracking, no checklists, no tags, no recurrence, no approval, no SLA, no escalation, no activity history, no soft deletes | Task is a flat 14-column row | Hierarchical tasks, time tracking, tags, activity timeline | **Medium** | Cannot manage real project work | Weak reporting | Add incrementally — subtasks and watchers first | GAP-001, GAP-018 | 8 |
| **GAP-026** | Task | 3 statuses only (`pending`, `in_progress`, `completed`); `due_date` NOT NULL | Cannot express on-hold/cancelled/reopened; cannot be undated | Align with `meeting_action_items` (5 statuses); nullable due date | **Medium** | Users misuse `completed` for cancelled | Corrupt completion metrics | Widen ENUM + nullable due date | GAP-020 | 8 |
| **GAP-027** | Task | `meeting_action_items` duplicates `tasks` with different vocabularies; linked by nullable `task_id` | One unit of work representable twice | Single task model; action items are a Meeting-scoped view of tasks | **Medium** | Divergent status/priority reporting | Duplicate notifications | Migrate action items to tasks | GAP-020, GAP-025 | 8 |
| **GAP-028** | Meeting | Templates, recurrence generation, and version history are fully built but unwired (no route, no caller, no cron) | Three dead features | Wire them or delete them | **Medium** | Wasted investment; users expect them | Code surface with no behaviour | Wire templates + recurrence in Phase 8; delete versions | — | 8 |
| **GAP-029** | Meeting | `meetings.location` is free text while a `locations` table exists | No location reporting; inconsistent with Obligations | `location_id` FK + free-text fallback | **Low** | Cannot report meetings by location | Data inconsistency | Add `location_id` nullable | — | 8 |
| **GAP-030** | Meeting | `meeting_attachments` has 4 nullable `CASCADE` FKs | A row with all parents NULL is valid; deleting any parent orphans/deletes | Single `meeting_id` parent + nullable child refs with `nullOnDelete` | **Medium** | Attachment loss | Orphaned files | Tighten constraints | — | 8 |
| **GAP-031** | Obligation | `escalation_rules` targets `obligation_type_id` only; `approval_workflows` has no `entity_type`/`entity_id` | Cannot scope escalation by department/company/user; approval workflows unattachable | Add scope columns; attach workflows polymorphically | **Medium** | Enterprise escalation needs department targeting | Dead feature | Extend schema | GAP-001 | 8 |
| **GAP-032** | Obligation | `obligation_activity_logs` lacks array casts on `old_value`/`new_value`; manual `json_encode` | Reads return strings; `AuditLog` casts arrays | Array casts, consistent | **Low** | Display bugs | Inconsistent | Add casts | GAP-006 | 8 |
| **GAP-033** | To-Do | **No `todos` table and no equivalent exists anywhere** (case-insensitive search across the repo returned zero matches) | No personal lightweight action items | Full To-Do module | **High** | Core requested capability absent | — | Build new module | GAP-001, GAP-004 | 3–6 |
| **GAP-034** | Dashboard | 220 lines of ad-hoc aggregation in one controller; no role-aware widgets; no cross-module "My Work" view | No single place to see all work | Role-aware widget registry with cached queries | **High** | Users cannot triage their day | Slow, unmaintainable | Extract query layer; add widgets | GAP-019, GAP-033 | 10 |
| **GAP-035** | Search | **No global search exists.** No full-text index, no cross-entity search | Cannot find anything across modules | Permission-aware global search over MySQL FULLTEXT first | **Medium** | Poor usability at scale | — | `LIKE` + FULLTEXT migration, single search service. No Elasticsearch. | GAP-001 | 9 |
| **GAP-036** | Reporting | Meeting + Obligation reports exist; Tasks have none; no cross-module report; no export (PDF/Excel/CSV) | Reporting is per-module and partial | Shared report query layer + exports | **Medium** | No leadership view | Duplicated report code | Extract shared layer; add exports | GAP-019 | 10 |
| **GAP-037** | API | `routes/api.php` is 8 lines; module API files are stubs with an `api.api` prefix bug; Sanctum installed but no token is ever issued | No API | Versioned `/api/v1` with API Resources | **Medium** | No mobile/integration path | Unused dependency | Fix prefix, add `/api/v1` read endpoints | GAP-001, GAP-018 | 12 |
| **GAP-038** | UX | Server-paginated tables **then** client-side DataTables → double pagination and a duplicate paginator | Every list view is wrong | Choose one: yajra server-side (installed) or pure client-side | **Medium** | Confusing UX; wrong page sizes | Wasted dependency | Standardise on yajra server-side | — | 5, 8 |
| **GAP-039** | UX | No shared empty/loading/error state for dashboards; `x-datatable` options are raw JSON per view | Inconsistent UX | Reusable `stat`, `empty-state`, `filter-bar` wired into every module | **Low** | Inconsistent feel | Rework per module | Reuse the 31 existing components | GAP-033 | 5 |
| **GAP-040** | Identity | `users` and `employees` are two person records with no sync; `users.employee_id` nullable; `departments.head_of_department_id` → `employees` | A department head can be a non-user | Employee as the person record; User as credentials | **Medium** | HR data divergence | Ambiguous identity | Make `employee_id` NOT NULL; add CRUD for employees | GAP-001 | 8 |
| **GAP-041** | Identity | `employees`, `companies`, `departments`, `locations` have models but **no CRUD routes anywhere** | Reference data is unmanageable in-app | Admin CRUD for all four | **Medium** | Data seeded and frozen | Manual SQL to change data | Build admin CRUD | GAP-001 | 8 |
| **GAP-042** | Dead code | `PermissionController` (unrouted, views missing), `config/menu.php` (unused), `tblAccountInfo` (no references), `meeting_templates`, `meeting_versions` | Dead surface | Delete or wire | **Low** | Confusion | Maintenance cost | Delete in Phase 15 | — | 15 |
| **GAP-043** | Data integrity | `User::tasks()/responsibleTasks()/projects()` reference unimported `Task`/`Project` → fatal; `User::employee` missing | Latent fatals | Fix imports; add relations | **Critical** | Any eager-load crashes | Runtime errors | Fix and cover with a test | — | 2 |
| **GAP-044** | Data integrity | `users.status` not fillable → silently discarded; `Role.description` same | Validated input is thrown away | Correct `$fillable` + `preventSilentlyDiscardingAttributes()` in dev | **High** | Users cannot be deactivated via UI | Silent data loss | Fix fillables; add the dev guard | — | 2 |
| **GAP-045** | Data integrity | `SecurityEventController` overwrites `$events` with the paginator | Security-event filter renders model objects | Fix variable shadowing | **Medium** | Broken admin filter | UI bug | Fix and test | — | 2 |
| **GAP-046** | Operations | No committed cron; no `withoutOverlapping`/`onOneServer`; app TZ is UTC while data is `+06:00`; `obligations:process` exits 1 on any bad row | Schedules silently may not run; TZ mismatch misfires reminders | Committed cron definition, locking, TZ, advisory exit codes | **High** | Reminders at wrong time or never | Missed obligations | Configure and document | — | 15 |
| **GAP-047** | Operations | `config/queue.php` batching/failed fall back to `sqlite`; `REDIS_PREFIX` defaults to `'AssetTask Pro'`; `LOG_LEVEL` defaults to `debug` | Misrouted failed-job storage; stale config | Correct defaults; explicit prod env | **Medium** | Failed jobs invisible in prod | Debug noise | Fix config defaults | — | 15 |
| **GAP-048** | Cross-module | No reusable comments/attachments/tags platform | Comments exist 3× (`task_remarks`, `meeting_discussions`, `obligation_activity_logs.remarks`); tags only for meetings | Polymorphic `comments`, `attachments`, `tags`+`taggables` | **Medium** | To-Do cannot reuse anything | Duplication | Build the platform, migrate lazily | GAP-001 | 2, 3 |
| **GAP-049** | Recurrence | `meeting_recurrences` (rich ENUM) exists; `obligations` uses flat strings; tasks/todos have none | Recurrence is reinvented per module | One `RecurrenceService` + `recurrence_rule` JSON, shared | **Medium** | Inconsistent recurrence semantics | Duplicate schedulers | Extract one service | GAP-020 | 6 |
| **GAP-050** | Reporting | No export (PDF/Excel/CSV/print) anywhere except the meeting print view | Reports are screen-only | Export service with queued PDF generation | **Low** | Manual work to share data | — | Add per report | GAP-036 | 10 |

---

## Gap Counts by Priority

| Priority | Count | Gaps |
|---|---|---|
| **Critical** | 7 | 001, 002, 003, 004, 005, 006, 007, 043 |
| **High** | 16 | 008, 009, 010, 011, 012, 013, 015, 017, 020, 021, 022, 025, 026, 033, 034, 044, 046 |
| **Medium** | 20 | 014, 016, 018, 019, 023, 024, 027, 028, 030, 031, 032, 035, 036, 037, 038, 040, 041, 045, 047, 048, 049 |
| **Low** | 5 | 029, 039, 042, 050 |

---

## Critical Path

```
GAP-004 (portable migrations)  ─┐
GAP-043 (broken model imports)  ─┤
GAP-007 (broken login logging) ─┼─► Phase 2 hardening
GAP-044/045 (data-integrity)   ─┘
                                          │
GAP-001 (policies) ─► GAP-002/003 ────────┤
GAP-006 (audit) ──► GAP-048 (platform) ───┤
                                          ▼
                          Phase 3–6: To-Do module
                                          │
GAP-020 (enums) ──────────────────────────┤
                                          ▼
                          Phase 7–15: integration, modules,
                          search, dashboard, security, API,
                          performance, production readiness
```

**Nothing in Phases 3+ should start until GAP-001 and GAP-004 are closed.** Building the
To-Do module on top of a platform with no authorization and no test database would
multiply both flaws.