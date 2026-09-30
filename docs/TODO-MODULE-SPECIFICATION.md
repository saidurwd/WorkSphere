# To-Do Module Specification

> First-class, enterprise-grade To-Do module for WorkSphere.
> Depends on: `ARCHITECTURE-ASSESSMENT.md`, `FUNCTIONAL-GAP-ANALYSIS.md`.

---

## 1. Scope & Positioning

### 1.1 What a To-Do Is

A **lightweight personal or team action item.** Cheap to capture, cheap to complete,
low ceremony, no workflow engine.

> Call the supplier. Review the contract draft. Submit the Q3 report. Follow up with
> Finance on the invoice. Buy office equipment.

Characteristics:

- Captured in **seconds** — title is the only required field.
- Single owner, or shared with a small set of collaborators.
- Lives in an Inbox/Planned → Completed lifecycle.
- Optionally recurs.
- Carries reminders, tags, a checklist, and links to anything else in the platform.
- **Never** becomes a Task. If it grows a dependency chain, an approval, or a time
  budget, promote it to a Task.

### 1.2 Boundary: To-Do vs Task vs Meeting vs Obligation

|  | **To-Do** | **Task** | **Meeting** | **Obligation** |
|---|---|---|---|---|
| **Purpose** | Capture and close a small action | Deliver structured work | Convene people, record outcomes | Meet a compliance/contractual/regulatory commitment |
| **Required fields** | `title` | `title`, `due_date` | `title`, `meeting_date`, `start_time`, `end_time`, `meeting_type_id` | `title`, `obligation_no`, `start_date`, `expiry_date`, `company_id` |
| **Assignees** | 1 owner + N watchers | 1 responsible + watchers | Many participants | Owner + backup + reviewer + approver + N responsibilities |
| **Status vocabulary** | Inbox → Planned → In Progress → Waiting → Completed → Archived | Pending → In Progress → On Hold → Completed → Cancelled | Scheduled → In Progress → Completed / Cancelled / Postponed | Active → (compliance state) |
| **Priority** | Low / Medium / High / **Urgent** | Low / Medium / High / **Critical** | Normal / Important / Urgent | Low / Medium / High + **Risk** |
| **Hierarchy** | None | Parent/subtask tree + dependencies | Agenda → Discussion → Decision → Action Item | Category + Type |
| **Effort** | Optional estimate in minutes (no timesheet) | Estimated vs actual effort, time entries | n/a | Estimated cost + currency |
| **Recurrence** | Yes — first class | Planned (Phase 8) | Yes — rich rules | Yes — renewal cycle |
| **Approval** | No | Optional | Minutes approval workflow | Approval workflow, often mandatory |
| **Escalation** | No | Optional | No | Yes — escalation rules + levels |
| **Evidence/Attachments** | Optional | Yes | Yes | **Mandatory** (documents + expiry) |
| **Reporting** | Personal/team productivity | Delivery performance | Attendance, decisions, action completion | Compliance, overdue, renewal |
| **Volume** | High (hundreds/user/year) | Medium | Medium | Low (tens) |
| **Creator == owner?** | Usually yes | No | No (creator, organizer, chair differ) | No (owner, backup, reviewer, approver differ) |

**Rule of thumb:** *To-Do = "I will do this." Task = "This must be delivered with
tracking." Obligation = "This must be evidenced, or we are non-compliant."*

### 1.3 Architecture Decision: Separate Table, Not a Task Subtype

**Decision: `todos` is its own table. Do NOT add `type` to `tasks`, and do NOT use
`meeting_action_items` as a template.**

Rationale:

- Different required fields (`due_date` nullable for To-Dos, NOT NULL for Tasks).
- Different status vocabulary — forcing one ENUM to cover both produces a union enum
  where every query must filter out the irrelevant half.
- Different volume and lifecycle. A personal To-Do has no project, no approval, no
  timesheet. Carrying those nullable columns into a high-volume table is pure noise.
- `meeting_action_items` is the **anti-pattern to avoid** — it duplicates Tasks with a
  conflicting vocabulary and is already causing reporting divergence (GAP-027).

**What IS shared:** the *concepts*. Ownership, priority, status, due dates, reminders,
comments, attachments, tags, activity and notifications are expressed through:

1. **PHP enums** (`Priority`, `WorkItemStatus`) for vocabulary.
2. **Model traits** (`HasAssignee`, `HasPriority`, `HasDueDate`, `HasReminders`,
   `HasComments`, `HasAttachments`, `HasTags`, `LogsActivity`).
3. **Polymorphic platform tables** — `comments`, `attachments`, `tags`/`taggables`,
   `activity_logs`, `notification_logs`, `reminders` — all keyed on
   `(subject_type, subject_id)`.
4. **A `work_items` SQL VIEW** unifying Tasks + To-Dos + Meeting Action Items for
   dashboard and search reads only. Never written to.

> "Do not force all modules into one database table simply for abstraction."
> — we honour this by sharing *vocabulary and behaviour*, not *storage*.

---

## 2. Functional Requirements

### 2.1 Basic (US-01 … US-09)

| ID | User Story | Acceptance Criteria |
|---|---|---|
| US-01 | As a user I can create a To-Do with only a title | `title` is the only required field; everything else nullable/defaulted; created in 1 request, no page navigation |
| US-02 | As a user I can edit a To-Do | Only fields I own or am permitted to edit; validation per field; activity logged |
| US-03 | As a user I can delete a To-Do | Soft delete; recoverable for 30 days; recipients notified |
| US-04 | As a user I can complete a To-Do | Sets `status=Completed`, stamps `completed_at` + `completed_by`; repeatable; notification to watchers |
| US-05 | As a user I can reopen a To-Do | Clears completion fields; returns to `In Progress` or `Planned`; logged |
| US-06 | As a user I can archive a To-Do | `status=Archived`; hidden from default lists; restorable |
| US-07 | As a user I can restore an archived/deleted To-Do | Soft-delete aware; restores previous status |
| US-08 | As a user I can assign a To-Do to someone | Sets owner; assignee gets in-app + email notification; previous owner logged |
| US-09 | As a user can create personal, team or shared To-Dos | `visibility`: `Personal` (owner only), `Team` (visible to a team/department), `Shared` (explicit watchers) |

### 2.2 Properties

**Core** — `title` (required), `description`, `status`, `priority`, `due_date`,
`due_time`, `start_date`, `assignee_id`, `creator_id`, `visibility`.

**Classification** — `team_id`*, `department_id`, `category_id`*, `color`,
`estimated_minutes`, `actual_minutes`, `sort_order`.

**Reminders** — handled by the shared `reminders` table (see §6).

**Recurrence** — `recurrence_rule` JSON (see §5).

**Relations** — polymorphic link table `todo_links` (`todo_id` → `linkable_type`,
`linkable_id`) with `link_type` ∈ {`RelatedTask`, `RelatedMeeting`, `RelatedObligation`,
`RelatedProject`, `RelatedTodo`, `Blocks`, `BlockedBy`}. Replaces six nullable FKs.

**Collaboration** — `todo_watchers` pivot; polymorphic `comments` and `attachments`;
`tags`/`taggables`; `checklist_items`.

\* `category_id` is optional and only justified if the roadmap's reporting work needs
to-do categories. **Default: skip it.** `team_id` is skipped in v1 — `visibility=Team`
scopes to `department_id`, which already exists.

---

## 3. Status Model

### 3.1 Vocabulary

| Enum Case | Meaning | Terminal | In default list? |
|---|---|---|---|
| `Inbox` | Captured, not yet triaged | No | ✅ |
| `Planned` | Triaged, scheduled, not started | No | ✅ |
| `InProgress` | Being worked on | No | ✅ |
| `Waiting` | Blocked on someone/something else | No | ✅ |
| `Completed` | Done | Yes | ❌ (toggle) |
| `Archived` | Closed and hidden | Yes | ❌ |

Backed `task_work_status` style: `WorkItemStatus` enum, `status` = `string(20)` + PHP
enum cast (not a DB ENUM — see GAP-020/§DATABASE-ARCHITECTURE).

### 3.2 Allowed Transitions

```
Inbox ──► Planned ──► InProgress ──► Completed
  │          │  ▲         │  ▲           │
  │          │  └─────────┘  │           │
  │          │               ▼           │
  │          └──────────► Waiting        │
  │                          │           │
  ▼                          ▼           ▼
Archived ◄────────────────────────────────┘
(any non-archived) ────────────────────► Archived
```

Rules:

- `Waiting` requires `waiting_on` (a user id or a free-text reason) — otherwise it is
  meaningless and pollutes reporting.
- `Completed` requires `completed_at` and `completed_by`; both cleared on reopen.
- `Archived` is reachable from any state and reversible back to the prior state, which is
  stored in `archived_from`.
- Any transition is written to `activity_logs` with old and new values.

### 3.3 System vs Configurable

**Decision: system-defined, implemented as a PHP enum.** Not organization-specific, not
user-specific.

Rationale: a configurable status model requires a status table, a transition table, a
transition engine, per-record status validation, and reporting that must cope with
arbitrary statuses. For an entity whose entire value proposition is *low ceremony*, that
is the wrong trade. The `Waiting` state covers the only common need for a custom state.
If genuine demand appears later, the transition table is the extension point — but do
not build it speculatively.

---

## 4. Permission Model

Extends the existing `Role`/`Permission`/`RolePermission`/`UserRole` tables. Twelve new
permission strings, all prefixed `todos.`:

| Permission | Grants |
|---|---|
| `todos.view` | See To-Dos they are party to |
| `todos.view_all` | See every To-Do (override) |
| `todos.create` | Create a To-Do |
| `todos.create_for_others` | Assign a To-Do to another user at creation |
| `todos.update_own` | Edit To-Dos they own |
| `todos.update_any` | Edit any To-Do |
| `todos.complete` | Complete/reopen any To-Do they can view |
| `todos.delete` | Soft-delete |
| `todos.restore` | Restore archived/deleted |
| `todos.assign` | Reassign owner |
| `todos.comment` | Comment and mention |
| `todos.manage_recurrence` | Create/edit recurrence rules |

### 4.1 `TodoPolicy` Rules

```
view(user, todo)      = todos.view_all
                      OR todo.assignee_id  = user.id
                      OR todo.creator_id   = user.id
                      OR user watches the todo
                      OR (visibility = Team AND user.department_id = todo.department_id)

update(user, todo)    = (todos.update_own AND (assignee OR creator))
                      OR (todos.update_any AND view)
                      OR todo.creator_id = user.id        ← creator always may amend

delete(user, todo)    = todos.delete AND (creator OR todos.update_any)

assign(user, todo)    = todos.assign AND (creator OR assignee OR todos.update_any)

restore(user, todo)   = todos.restore AND (creator OR todos.update_any)
```

**Why `view` is explicit rather than `can`:** the existing modules scope *lists* by
`where user_id = me OR responsible_user_id = me` inside controllers and perform **no**
object-level check (GAP-003). To-Dos get it right from day one via a policy, and a
`TodoScope` global scope keeps the list query and the object check in agreement.

### 4.2 Enforcement

- Every controller action calls `$this->authorize('update', $todo)`.
- Route model binding resolves the To-Do; the policy decides 403 vs 404.
- The `TodoPolicy` is registered in `AppServiceProvider::$policies`.
- Tests cover: non-assignee cannot view/update/delete; cross-department Team To-Do is
  invisible; `view_all` sees everything.

---

## 5. Recurrence

### 5.1 Decision: Rule-on-Row, Materialise-Next

**Decision: store an RRULE-shaped JSON rule on the To-Do row and materialise the next
occurrence when the current one completes.** No `todo_occurrences` table for v1.

Justification: materialising eagerly means a scheduler that must create rows for every
recurring To-Do even when the user never completes them — write amplification for records
that will be discarded. Materialising on completion generates exactly the rows that will
actually be used. For a personal-scale recurrence (daily/weekly/monthly), that is
correct and dramatically simpler.

Revisit only if reporting requires a historical occurrence ledger.

### 5.2 `recurrence_rule` JSON

```jsonc
{
  "frequency": "monthly",          // daily|weekly|monthly|quarterly|yearly|custom
  "interval": 1,                   // every N periods
  "by_weekday": [1, 3],           // ISO-8601, 1=Mon … 7=Sun (weekly)
  "by_month_day": 15,              // monthly
  "start_date": "2026-10-01",
  "end_date": "2027-09-30",        // nullable
  "max_occurrences": 12,           // nullable
  "occurrence_count": 3,           // maintained by the service
  "last_generated_at": "2026-12-01T09:00:00Z",
  "skip_dates": ["2026-11-15"]     // skipped occurrences
}
```

### 5.3 Behaviour

| Concern | Behaviour |
|---|---|
| Next occurrence | Computed from `start_date` + frequency + interval, honouring `by_weekday`/`by_month_day` |
| Previous occurrence | `previous_occurrence_at` column on the row, retained for the "series" view |
| Skip occurrence | `skip_dates` array; a scheduled command `todos:skip` moves a single occurrence forward without completing |
| Completion behaviour | On `Completed`, `TodoRecurrenceService::advance()` creates the next To-Do if `occurrence_count < max_occurrences` and `next <= end_date` |
| End date | `end_date` — no occurrence is generated past it |
| Max occurrences | `max_occurrences` — generation stops at the count |
| Delete series | Deletes the whole series, not one occurrence |
| Timezone | Stored dates are local business dates (`+06:00`); reminders are computed in the app timezone, which must be fixed from UTC (GAP-046) |

### 5.4 Shared Recurrence Service

Extract `RecurrenceService` into `app/Services/` and reuse it for `meeting_recurrences`
and `obligations.recurrence_type` (GAP-049). The To-Do module is the first consumer; the
other two migrate onto it in Phase 8.

---

## 6. Reminders & Notifications

### 6.1 Reminders

Shared polymorphic `reminders` table (GAP-048), not a To-Do-specific one:

```
reminders: id, subject_type, subject_id, remind_at, channel, status,
           sent_at, cancelled_at, created_by, timestamps
UNIQUE(subject_type, subject_id, remind_at)   ← idempotency
INDEX(reminders.status, remind_at)            ← the scheduler's hot path
```

Channels: `InApp`, `Email`. `Browser`/`Push`/`SMS` are enum cases reserved but not
implemented — no Reverb, no SMS gateway.

A single scheduled command `reminders:dispatch` runs every minute, selects
`status = Pending AND remind_at <= now()` in chunks of 200, dispatches jobs, and marks
sent. This replaces the five module-specific daily commands (GAP-046) in Phase 6.

### 6.2 Events

| Event | Recipients | Channels |
|---|---|---|
| `TodoCreated` | assignee (if not creator) | InApp + Email |
| `TodoAssigned` / `TodoReassigned` | new assignee; old assignee (info) | InApp + Email |
| `TodoCompleted` | creator (if not completer), watchers | InApp |
| `TodoReopened` | watchers | InApp |
| `TodoOverdue` | assignee | InApp + Email |
| `TodoDueSoon` | assignee | InApp |
| `TodoCommented` | assignee, creator, watchers (minus actor) | InApp (+ Email if `@mention`) |
| `TodoMentioned` | mentioned users | InApp + Email |
| `TodoRecurringGenerated` | assignee | InApp |
| `TodoReminder` | assignee | InApp + Email |

### 6.3 Pipeline

```
Event (dispatched in the service/action)
  └─► Listener implements ShouldQueue
        └─► Job implements ShouldQueue
              ├─► Notification::send($users, new TodoNotification(...))   ← Laravel Notifications
              └─► NotificationLog::create([...])                          ← one polymorphic log
```

**Every send is queued.** No synchronous mail in any request or scheduler
(GAP-022). In-app uses Laravel Notifications with a `notifications` table — the navbar
reads one table instead of three log tables (GAP-021). User preferences come from
`notification_preferences(user_id, notification_type, channel, enabled)` and
`TodoNotificationService::shouldNotify()` consults it instead of returning `true`
(GAP-024).

### 6.4 Idempotency

`notification_logs` carries a `dedupe_key` (e.g.
`todo.overdue:{todo_id}:{date}`) with a unique index. A duplicate insert is swallowed,
so re-running the overdue command is safe.

---

## 7. Database Design

Authoritative table/column spec is in `DATABASE-ARCHITECTURE.md` §4. Summary:

**`todos`** — `id`, `title`, `description`, `status`(string+enum), `priority`,
`visibility`, `assignee_id`→users `nullOnDelete`, `creator_id`→users `restrictOnDelete`,
`department_id`→departments `nullOnDelete`, `start_date`, `due_date` **nullable**,
`due_time`, `estimated_minutes`, `actual_minutes`, `completed_at`, `completed_by`,
`archived_from`, `waiting_on`, `color`, `sort_order`, `recurrence_rule`(json),
`previous_occurrence_at`, `last_reminded_at`, `timestamps`, `deleted_at`.

Indexes: `(assignee_id, status, due_date)`, `(creator_id, status)`,
`(status, due_date, deleted_at)`, `(department_id, status)`,
fulltext `(title, description)`.

**Companions:** `todo_watchers`, `todo_checklist_items`, `todo_links`.
**Shared platform tables this reuses:** `comments`, `attachments`,
`tags` + `taggables`, `reminders`, `activity_logs`, `notification_logs`,
`notification_preferences`.

### 7.1 Checklist

`todo_checklist_items`: `todo_id` cascade, `title`, `is_completed`, `completed_at`,
`completed_by`, `sort_order`, timestamps. Progress is computed, not stored.

### 7.2 Links

`todo_links`: `todo_id` cascade, `linkable_type`, `linkable_id`, `link_type`,
timestamps, `UNIQUE(todo_id, linkable_type, linkable_id, link_type)`. Bidirectional
navigation resolves through `TodoLinkService`, so a Task and a To-Do can reference each
other without two columns.

### 7.3 `work_items` Read View

A SQL view over `tasks`, `todos`, `meeting_action_items` exposing
`source_type, source_id, title, status, priority, assignee_id, creator_id, due_date,
completed_at, project_id, deleted_at`. Used by the dashboard, global search and
cross-module reports. **Read-only. Never written to. Never joined to for writes.**

---

## 8. UI Requirements

### 8.1 Screens

| Screen | Route | Purpose |
|---|---|---|
| To-Do list | `GET /todos` | Main workspace. Filterable, sortable, paginated |
| Create | `GET/POST /todos/create` | Single-field-first capture form |
| Detail | `GET /todos/{todo}` | Full view: description, checklist, comments, attachments, links, activity |
| Edit | `GET/PUT /todos/{todo}/edit` | Same form as create |
| Inbox | `GET /todos/inbox` | `status=Inbox` shortcut |
| Calendar | `GET /todos/calendar` | Month/week view from `due_date` |
| Reports | `GET /todos/reports` | Completion, overdue, personal & team productivity |
| Notification log | `GET /todos/notification-logs` | Delivery history, mirroring Tasks/Meetings |

### 8.2 Component Reuse

Built entirely from the existing 31 Blade components — no new design system:

`x-form/input`, `x-form/select`, `x-form/textarea`, `x-badge` (status badge),
`x-btn`, `x-modal`, `x-alert`, `x-datatable`, `x-detail-card`, `x-empty-state`,
`x-pagination`, `x-stat`, `x-progress-list`, `x-tom-select`, `x-flatpickr`,
`x-icon-btn`, `x-detail-list`, `x-confirm-dialog`.

Reuse of `x-badge` requires a **single shared status→variant map** — the current
`match` arms are duplicated in `DashboardController.php:166-189`. Introduce
`App\Support\StatusBadge::variant(WorkItemStatus|string): string` and use it in every
module (Phase 2, GAP-020).

### 8.3 UX Standards

- **Quick capture** — a title input on the list page that creates a To-Do on Enter with
  no navigation. This is the single most important interaction in the module.
- **Server-side pagination only.** Choose yajra (installed, unused) and remove client-side
  DataTables (GAP-038). No double pagination.
- **Empty states** — distinct copy for Inbox-empty, filtered-empty, and never-created.
- **Loading states** — button-level spinners on all mutations via the `data-confirm`
  SweetAlert2 interceptor pattern already in `resources/js/app.js:53-88`.
- **Error states** — inline `is-invalid` + `invalid-feedback` (already automatic in
  `x-form/input`).
- **Confirmation** — delete, archive and bulk actions require a dialog. Server-side
  re-validation, never client-side only.
- **Keyboard** — `n` focuses capture, `Esc` closes modals, all controls reachable by Tab.
- **Accessibility** — `aria-label` on icon-only buttons, `aria-live` on filter results,
  WCAG AA contrast on badges, no colour-only status encoding.
- **Bulk actions** — complete, reassign, archive, tag. Multi-select with a sticky action
  bar.

### 8.4 Navigation

Append one node to `config/navigation.php`:

```php
[
    'label'  => 'To-Dos',
    'icon'   => 'check-square',
    'route'  => 'todos.index',
    'active' => ['todos.*'],
    'children' => [
        ['label' => 'My To-Dos',  'icon' => 'inbox',        'route' => 'todos.index',  'active' => ['todos.index']],
        ['label' => 'Inbox',      'icon' => 'download',     'route' => 'todos.inbox',  'active' => ['todos.inbox']],
        ['label' => 'Calendar',   'icon' => 'calendar',     'route' => 'todos.calendar', 'active' => ['todos.calendar']],
        ['label' => 'Reports',    'icon' => 'bar-chart',    'route' => 'todos.reports', 'active' => ['todos.reports']],
    ],
],
```

---

## 9. API Requirements

`routes/api.php` gains a versioned group. Note the **module API prefix bug**
(`prefix('api')` inside an already-`api`-prefixed module registration, yielding
`/api/api/…`) must be fixed in Phase 2 first (GAP-017).

### 9.1 Endpoints

```
GET    /api/v1/todos                 index    ?status=&priority=&assignee_id=&due_before=&tag=&q=&per_page=
POST   /api/v1/todos                 store
GET    /api/v1/todos/{todo}          show
PATCH  /api/v1/todos/{todo}          update
DELETE /api/v1/todos/{todo}          destroy
POST   /api/v1/todos/{todo}/complete complete
POST   /api/v1/todos/{todo}/reopen   reopen
POST   /api/v1/todos/{todo}/archive  archive
POST   /api/v1/todos/{todo}/assign   assign
GET    /api/v1/todos/{todo}/comments index
POST   /api/v1/todos/{todo}/comments store
```

### 9.2 Standards

- **Auth**: Sanctum bearer tokens. Expiration must be set (`config/sanctum.php:53`
  currently `null` — tokens never expire).
- **AuthZ**: `TodoPolicy` — identical rules to the web routes. No web-only shortcuts.
- **Resources**: `TodoResource`, `TodoCollection`, `CommentResource`. Never expose
  `recurrence_rule` internals or `deleted_at`.
- **Envelope**: `{ "data": …, "meta": { "current_page", "last_page", "per_page", "total" } }`
  for collections; `{ "data": … }` for single resources.
- **Errors**: Laravel's JSON error format with a stable `code` string
  (`validation_failed`, `not_found`, `forbidden`, `unauthenticated`).
- **Throttling**: `throttle:api` (60/min) plus a tighter `throttle:api-writes`
  (30/min) on mutating routes.
- **Filtering/sorting**: whitelist status/priority via the enum; reject unknown sort
  columns rather than passing them to `orderBy`.
- **Rate limiting per user**, not per IP, once tokens are in use.
- **Docs**: OpenAPI 3.1 generated from the Form Requests / Resources. Do not hand-write.

---

## 10. Reporting Requirements

| Report | Definition |
|---|---|
| **Completion** | Completed To-Dos per period, by owner and by department |
| **Overdue** | `due_date < today AND status NOT IN (Completed, Archived)`, by owner |
| **Personal productivity** | Per-user completed vs created, completion rate, average days to complete |
| **Team productivity** | Same grouped by department; workload distribution per assignee |
| **Overdue trend** | Overdue count by week over a selected range |
| **Recurrence adherence** | For recurring To-Dos: generated vs completed |

**Implementation:** a `TodoReportService` using aggregate queries (`COUNT`,
`SUM(CASE WHEN …)`, `DATE_FORMAT`) — never load rows into PHP. This mirrors
`MeetingReportService`, which is the correct existing pattern. Add a `TodosReport`
export controller producing CSV; PDF only if Phase 10's export service exists, otherwise
defer (GAP-050 is Low).

---

## 11. Non-Functional Requirements

| Concern | Requirement |
|---|---|
| **Performance** | List queries eager-load only what the view renders. `Todo::query()->withCount('comments')` rather than loading collections. Index-driven filters per §7. Target < 300 ms at 50k To-Dos. |
| **N+1** | Covered by a test asserting no query count growth as comment count rises. |
| **Security** | Policy on every action. Form Requests for every write. `description` escaped in Blade (never `{!! !!}`). File uploads via the shared private-disk service with MIME validation (GAP-011). Rate limited. |
| **Mass assignment** | `#[Fillable]` on the model; enable `preventSilentlyDiscardingAttributes()` in the `local`/`testing` environments so the `users.status` class of bug (GAP-044) cannot recur. |
| **Transactions** | Creating the next recurrence occurrence, linking records, and writing activity logs run inside `DB::transaction`. |
| **Queues** | All notifications dispatched, never sent inline. |
| **Observability** | Failures logged with the To-Do id; no PII in log lines. |
| **Backward compatibility** | Purely additive. No existing table is altered in Phases 3–6. New tables only, plus optional new columns. Nothing existing breaks. |
| **Migration reversibility** | Every migration has a working `down()`. No raw SQL. Portable to SQLite (GAP-004). |

---

## 12. Module File Layout

```
Modules/Todos/
├── app/
│   ├── Console/Commands/
│   │   ├── SendTodoOverdueCommand.php
│   │   ├── SendTodoDueSoonCommand.php
│   │   ├── GenerateTodoOccurrencesCommand.php
│   │   └── SkipTodoOccurrenceCommand.php
│   ├── Events/            TodoCreated, TodoAssigned, TodoReassigned, TodoCompleted,
│   │                      TodoReopened, TodoArchived, TodoOverdue, TodoDueSoon,
│   │                      TodoCommented, TodoMentioned, TodoReminderFired,
│   │                      TodoRecurringGenerated
│   ├── Http/Controllers/  TodoController, TodoChecklistController, TodoWatcherController,
│   │                      TodoLinkController, TodoReportController,
│   │                      TodoNotificationLogController, TodoCalendarController
│   ├── Http/Requests/     StoreTodoRequest, UpdateTodoRequest, AssignTodoRequest,
│   │                      CompleteTodoRequest, RecurrenceTodoRequest, StoreTodoCommentRequest
│   ├── Jobs/              SendTodoAssignedJob, SendTodoCompletedJob, SendTodoOverdueJob,
│   │                      SendTodoReminderJob, SendTodoMentionJob
│   ├── Listeners/         (one per event)
│   ├── Models/            Todo, TodoWatcher, TodoChecklistItem, TodoLink
│   ├── Notifications/     TodoNotification (in-app + mail channel)
│   ├── Policies/          TodoPolicy
│   ├── Providers/         TodosServiceProvider, RouteServiceProvider, EventServiceProvider
│   └── Services/          TodoService, TodoRecurrenceService, TodoReportService,
│                          TodoLinkService
├── config/config.php
├── database/{factories,migrations,seeders}/
├── resources/views/{index,show,create,edit,_form,_row,calendar,reports,reports/*}/
├── routes/{web,api}.php
└── tests/{Feature,Unit}/
```

Register in `modules_statuses.json`. Follow the **Meetings** module layout — it is the
most complete and correct example in this codebase.

---

## 13. Definition of Done

Per the platform standard, this module is complete only when:

- [ ] Migrations applied, reversible, SQLite-portable
- [ ] Models, relationships and casts implemented
- [ ] `TodoPolicy` registered and enforced on **every** action
- [ ] Form Requests for every write; no inline validation
- [ ] Status transitions validated against §3.2
- [ ] Recurrence service + 4 commands working, skip/limit/end-date honoured
- [ ] Reminders via the shared `reminders` table; all sends queued
- [ ] Activity + audit logs written on create/update/assign/status-change/delete
- [ ] Full UI using existing Blade components only; no double pagination
- [ ] Navigation entry added to `config/navigation.php`
- [ ] Permission strings seeded and verified **enforcing** (via a test)
- [ ] Feature tests: CRUD, lifecycle, authorization, recurrence, notifications, N+1
- [ ] API endpoints with API Resources, throttle and policy
- [ ] Reports via aggregate queries
- [ ] Existing test suite still green
- [ ] Security and performance reviewed
- [ ] `php artisan pint` clean