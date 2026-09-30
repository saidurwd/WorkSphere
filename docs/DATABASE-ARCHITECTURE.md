# Database Architecture

> Current-state inventory, target-state schema and migration strategy.
> Derived from all 68 migrations in `database/migrations/` plus live model inspection.

---

## 1. Current State

### 1.1 Summary

- **~60 tables** on the default `mysql` connection; `tblAccountInfo` alone on `sqlsrv`.
- **68 migration files** — all in `database/migrations/`. Every
  `Modules/*/database/migrations/` directory is **empty**.
- MySQL-specific throughout: `ENUM()` columns, `->after()`, raw
  `ALTER TABLE … ENUM` via `DB::statement`, and `information_schema` queries.
- **Verified: `php artisan migrate` fails on SQLite** at
  `2026_07_09_070842_alter_departments_head_and_status.php:25`. This blocks in-memory
  test databases and therefore all automated testing (GAP-004).

### 1.2 Table Groups

**Framework (9):** `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`,
`jobs`, `job_batches`, `failed_jobs`, `personal_access_tokens`.

**Identity & org (6):** `employees`, `departments`, `locations`, `companies`,
`vendors`, `tblAccountInfo` *(legacy sqlsrv, fully orphaned)*.

**AuthZ (4):** `permissions`, `roles`, `role_permissions`, `user_roles`.

**Logging (4):** `login_logs`, `tyro_audit_logs`, `activity_logs`,
`obligation_activity_logs`.

**Tasks (5):** `tasks`, `task_projects`, `task_remarks`, `task_transfers`,
`task_notification_logs`.

**Meetings (15):** `meeting_types`, `meetings`, `meeting_participants`,
`meeting_agendas`, `meeting_discussions`, `meeting_decisions`, `meeting_action_items`,
`meeting_attachments`, `meeting_recurrences`, `meeting_templates`,
`meeting_template_agendas`, `meeting_tags`, `meeting_tag_map`, `meeting_versions`,
`meeting_minutes_approvals`, `meeting_notification_logs`.

**Obligations (12):** `obligation_types`, `obligation_categories`, `obligations`,
`obligation_responsibilities`, `obligation_renewals`, `obligation_documents`,
`obligation_activity_logs`, `notification_rules`, `notification_logs`,
`escalation_rules`, `approval_workflows`, `approval_workflow_steps`.

---

## 2. Existing Schema Defects

### 2.1 Blocking

| ID | Defect | Location | Impact |
|---|---|---|---|
| **DB-01** | FK `role_permissions.role_id → roles` declared in `2026_07_09_000026`, but `roles` is created in `2026_09_29_152053` — 3 months later | migration ordering | Fresh `migrate` on empty MySQL **fails** |
| **DB-02** | `HAZIRA` column declared in the create migration **and** added again by `2026_08_24_182838` with no `hasColumn` guard | `2026_08_24_*` | Duplicate-column failure |
| **DB-03** | Raw `ALTER TABLE … ENUM` (5 migrations) + `->after()` (4 migrations) + `information_schema` query (1) | throughout | **SQLite migration impossible** → no test DB |
| **DB-04** | `make_role_permissions_surrogate_key_and_timestamps` adds only a missing FK — no surrogate key, no timestamps, despite its filename | `2026_07_11_192454` | Misleads every future reader |

### 2.2 Data Integrity

| ID | Defect | Impact |
|---|---|---|
| **DB-05** | `tasks.user_id` `NOT NULL` + `cascadeOnDelete`; also `meetings.organizer_id`, `meeting_minutes_approvals.approver_id`, `obligations.owner_user_id`, `task_remarks.user_id` | Deleting a user **destroys** their work and audit trails |
| **DB-06** | `meeting_attachments` has 4 nullable `CASCADE` FKs | A row with all parents NULL is valid; deleting any one parent destroys the attachment |
| **DB-07** | `meeting_discussions.agenda_id` `cascadeOnDelete` | Deleting an agenda destroys its discussion record |
| **DB-08** | `users.employee_id` nullable; `departments.head_of_department_id → employees.id` | A department head can be set to a non-user |
| **DB-09** | `permissions.permission_name` — no unique, no index | Natural key unenforced |
| **DB-10** | Three `drop_*` migrations with `FOREIGN_KEY_CHECKS=0`; `down()` drops instead of recreating | `rollback` past 2026-09-29 = permanent data loss |
| **DB-11** | `task_transfers.file_attache` | Typo baked into the schema |
| **DB-12** | `obligations.category_id → obligation_categories` | Singular/plural naming break |
| **DB-13** | `meeting_recurrences`, `meeting_agendas`, `meeting_decisions`, `meeting_versions` have no uniqueness on their per-parent sequence numbers | Duplicate `action_no`/`decision_no`/`version_no` |

### 2.3 Performance

| Table | Missing index on |
|---|---|
| `activity_logs` | **all** — `module_name`, `record_id`, `action`, `created_at` have zero indexes. Every admin log screen full-scans |
| `obligation_renewals` | **all** — not even `obligation_id` |
| `obligation_documents` | **all** |
| `notification_rules` | **all** |
| `escalation_rules` | **all** |
| `tasks` | `status`, `due_date` standalone; `priority`; `task_no` |
| `meeting_recurrences` | `meeting_id`, **`next_occurrence`** (the occurrence generator's hot path) |
| `meeting_action_items` | `priority`, `action_no` |
| `*_notification_logs` (×3) | `channel`, `notification_type`, `scheduled_at` |
| `employees` / `companies` / `vendors` / `locations` | `status`; `employees.email` not unique |
| `login_logs` | `email`, `ip_address`, `attempted_at` |
| `permissions` | **`permission_name`** |
| `roles` | `name` |
| `users` | `status` |

### 2.4 Duplication

| Concept | Duplicated across | Notes |
|---|---|---|
| Notification delivery logs | `notification_logs`, `meeting_notification_logs`, `task_notification_logs` | Column-for-column identical except subject FKs |
| Audit trail | `activity_logs`, `tyro_audit_logs`, `obligation_activity_logs` | 3 different shapes; only the third is live |
| Approval | `approval_workflows` + `_steps` (dead) vs `meeting_minutes_approvals` (live) | Generic pair is structurally unattachable — no `entity_type`/`entity_id` |
| Work item | `tasks` vs `meeting_action_items` | Different status and priority vocabularies; linked by nullable `task_id` |
| Tags | `meeting_tags` + `meeting_tag_map` only | No reusable tagging |
| Attachments | `meeting_attachments`, `obligation_documents`, `tasks.attachment` (string), `task_transfers.file_attache` (string) | 2 tables + 2 loose strings |
| Comments | `task_remarks`, `meeting_discussions`, `obligation_activity_logs.remarks`, `meetings.agenda` (text duplicating `meeting_agendas`) | 4 shapes |
| Person | `users` (with `employee_id`) vs `employees` (own name/email/phone) | No sync |
| Location | `locations` table vs `meetings.location` free text | Inconsistent |
| Recurrence | `meeting_recurrences` (rich ENUM) vs `obligations.recurrence_type/interval` (flat strings) | Inconsistent |
| "Active" flag | `status='active'` / `active` bool / `is_active` bool | 3 conventions |

### 2.5 Orphaned / Dead

| Object | Status |
|---|---|
| `tblAccountInfo` | 2 migrations, 0 models, 0 routes. Also has two column-identical indexes and `FILTERDATE` as `string(20)` |
| `approval_workflows` / `_steps` | Models exist; no service, controller or route references them |
| `meeting_templates` / `_template_agendas` | Models + relations exist; no route or controller |
| `meeting_versions` | `MeetingService::createVersion()` exists but is never called |
| `meeting_recurrences` | `MeetingRecurrenceService::generateOccurrences()` exists but is never called; no cron |
| `employees`, `companies`, `departments`, `locations` | Models exist; **no CRUD routes anywhere** |

---

## 3. Target-State Principles

1. **Portable migrations.** No `ENUM()` DB columns — `string` + PHP enum casts. No
   `->after()`. No raw `ALTER`. No `information_schema`. Portable to SQLite so tests run
   in memory.
2. **Additive-first.** Every change is a new migration. No existing table is rewritten
   without a dedicated, reversible migration and an impact note.
3. **No destructive FK changes in the same release.** Changing `cascadeOnDelete` →
   `nullOnDelete` is safe, but it must be its own migration with a verified `down()`.
4. **Polymorphism for shared concepts.** `comments`, `attachments`, `tags`,
   `reminders`, `activity_logs`, `notification_logs` are polymorphic platform tables.
5. **Enumerations live in PHP.** Vocabulary is enforced by `App\Enums`, not by the DB.
6. **Index every filter dimension.** Any column used in a `where` in a controller gets an
   index.
7. **Soft deletes on work items**, not on reference data.

---

## 4. Target Schema — New Tables (To-Do + Shared Platform)

### 4.1 `todos`

```php
Schema::create('todos', function (Blueprint $table) {
    $table->id();

    $table->string('title');
    $table->text('description')->nullable();

    $table->string('status', 20)->default('inbox');
    $table->string('priority', 20)->default('medium');
    $table->string('visibility', 20)->default('personal');

    $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('creator_id')->constrained('users')->restrictOnDelete();
    $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();

    $table->date('start_date')->nullable();
    $table->date('due_date')->nullable();
    $table->time('due_time')->nullable();

    $table->unsignedSmallInteger('estimated_minutes')->nullable();
    $table->unsignedSmallInteger('actual_minutes')->nullable();

    $table->timestamp('completed_at')->nullable();
    $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->string('archived_from', 20)->nullable();
    $table->string('waiting_on')->nullable();

    $table->string('color', 20)->nullable();
    $table->unsignedInteger('sort_order')->default(0);

    $table->json('recurrence_rule')->nullable();
    $table->date('previous_occurrence_at')->nullable();
    $table->timestamp('last_reminded_at')->nullable();

    $table->timestamps();
    $table->softDeletes();

    $table->index(['assignee_id', 'status', 'due_date'], 'todos_assignee_status_due_idx');
    $table->index(['creator_id', 'status'], 'todos_creator_status_idx');
    $table->index(['status', 'due_date', 'deleted_at'], 'todos_status_due_soft_idx');
    $table->index(['department_id', 'status'], 'todos_department_status_idx');
    $table->index('last_reminded_at');
    $table->fullText(['title', 'description'], 'todos_fulltext_idx');
});
```

Notes:
- `due_date` is **nullable** — unlike `tasks`, an undated To-Do is valid.
- `creator_id` uses `restrictOnDelete` so history cannot be orphaned; `assignee_id` uses
  `nullOnDelete` so deleting a user does not destroy their To-Dos.
- `status`/`priority` are `string`, not `ENUM` (Principle 1) and are cast to PHP enums.
- `creator_id` is NOT NULL — a To-Do always has an author.

### 4.2 `todo_watchers`

```php
Schema::create('todo_watchers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('todo_id')->constrained('todos')->cascadeOnDelete();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['todo_id', 'user_id'], 'todo_watcher_unique');
});
```

### 4.3 `todo_checklist_items`

```php
Schema::create('todo_checklist_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('todo_id')->constrained('todos')->cascadeOnDelete();
    $table->string('title');
    $table->boolean('is_completed')->default(false);
    $table->timestamp('completed_at')->nullable();
    $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();
    $table->index(['todo_id', 'sort_order'], 'todo_checklist_order_idx');
});
```

Progress is **computed**, never stored.

### 4.4 `todo_links`

```php
Schema::create('todo_links', function (Blueprint $table) {
    $table->id();
    $table->foreignId('todo_id')->constrained('todos')->cascadeOnDelete();
    $table->string('linkable_type');
    $table->unsignedBigInteger('linkable_id');
    $table->string('link_type', 30)->default('related');
    $table->timestamps();
    $table->unique(['todo_id', 'linkable_type', 'linkable_id', 'link_type'], 'todo_link_unique');
    $table->index(['linkable_type', 'linkable_id'], 'todo_link_reverse_idx');
});
```

Replaces six nullable FK columns. `link_type` ∈ `related`, `relates_to`, `blocks`,
`blocked_by`, `derived_from`. The reverse index serves bidirectional navigation.

### 4.5 `comments` (shared platform)

```php
Schema::create('comments', function (Blueprint $table) {
    $table->id();
    $table->string('commentable_type');
    $table->unsignedBigInteger('commentable_id');
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('parent_id')->nullable();
    $table->text('body');
    $table->json('mentions')->nullable();     // user ids
    $table->timestamp('edited_at')->nullable();
    $table->timestamps();
    $table->softDeletes();
    $table->index(['commentable_type', 'commentable_id', 'created_at'], 'comment_subject_idx');
    $table->index('parent_id');
});
```

### 4.6 `attachments` (shared platform)

```php
Schema::create('attachments', function (Blueprint $table) {
    $table->id();
    $table->string('attachable_type');
    $table->unsignedBigInteger('attachable_id');
    $table->string('disk', 40)->default('local');
    $table->string('path');
    $table->string('original_name');
    $table->string('mime_type', 120)->nullable();
    $table->unsignedBigInteger('size')->nullable();
    $table->string('checksum', 64)->nullable();
    $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->softDeletes();
    $table->index(['attachable_type', 'attachable_id'], 'attachment_subject_idx');
    $table->index('checksum');
});
```

**Default disk is `local` (private), not `public`** — closes GAP-011. Downloads go
through an authorized controller, never a direct URL. `checksum` enables dedupe and
integrity verification.

### 4.7 `tags` + `taggables` (shared platform)

```php
Schema::create('tags', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->string('slug')->unique();
    $table->string('color', 20)->nullable();
    $table->timestamps();
});

Schema::create('taggables', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
    $table->string('taggable_type');
    $table->unsignedBigInteger('taggable_id');
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->unique(['tag_id', 'taggable_type', 'taggable_id'], 'taggable_unique');
    $table->index(['taggable_type', 'taggable_id'], 'taggable_subject_idx');
});
```

`meeting_tags` / `meeting_tag_map` migrate onto these in Phase 8.

### 4.8 `reminders` (shared platform)

```php
Schema::create('reminders', function (Blueprint $table) {
    $table->id();
    $table->string('subject_type');
    $table->unsignedBigInteger('subject_id');
    $table->timestamp('remind_at');
    $table->string('channel', 20)->default('in_app');
    $table->string('status', 20)->default('pending');   // pending|sent|cancelled|failed
    $table->timestamp('sent_at')->nullable();
    $table->timestamp('cancelled_at')->nullable();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
    $table->unique(['subject_type', 'subject_id', 'remind_at'], 'reminder_unique');
    $table->index(['status', 'remind_at'], 'reminder_dispatch_idx');
});
```

The unique index provides **idempotency**; the composite index is the scheduler's only
hot query.

### 4.9 `notification_logs` — consolidated

```php
Schema::create('notification_logs', function (Blueprint $table) {
    $table->id();
    $table->string('subject_type');                     // polymorphic
    $table->unsignedBigInteger('subject_id');
    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('notification_rule_id')->nullable();
    $table->string('channel', 20);
    $table->string('notification_type', 60);
    $table->timestamp('scheduled_at')->nullable();
    $table->timestamp('sent_at')->nullable();
    $table->string('status', 20)->default('pending');
    $table->string('subject')->nullable();
    $table->text('message')->nullable();
    $table->string('dedupe_key')->nullable()->unique();
    $table->unsignedTinyInteger('retry_count')->default(0);
    $table->text('error_message')->nullable();
    $table->string('provider_message_id')->nullable();
    $table->timestamps();
    $table->index(['subject_type', 'subject_id'], 'notiflog_subject_idx');
    $table->index(['status', 'scheduled_at'], 'notiflog_dispatch_idx');
    $table->index(['user_id', 'created_at'], 'notiflog_user_idx');
});
```

`dedupe_key` unique makes every send idempotent — the single most valuable column in
the notification system, because every reminder command must be safely re-runnable.
This table absorbs all three existing `*_notification_logs` tables.

### 4.10 `notification_preferences`

```php
Schema::create('notification_preferences', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
    $table->string('notification_type', 60);
    $table->string('channel', 20);
    $table->boolean('enabled')->default(true);
    $table->timestamps();
    $table->unique(['user_id', 'notification_type', 'channel'], 'notif_pref_unique');
});
```

Gives `shouldNotify()` something real to consult instead of returning `true` (GAP-024).

### 4.11 `notifications` (Laravel Notifications)

Standard Laravel `notifications` migration — UUID primary key, `type`, `notifiable_type`,
`notifiable_id`, `data` json, `read_at`, timestamps, index
`(notifiable_type, notifiable_id, read_at)`. Replaces the navbar's three log-table
queries per page render.

### 4.12 `activity_logs` — unified

Existing `activity_logs` gains polymorphic columns rather than a fourth table:

```php
$table->string('subject_type')->nullable();     // Task|Todo|Meeting|Obligation|User|Role
$table->unsignedBigInteger('subject_id')->nullable();
$table->string('user_agent')->nullable();
$table->index(['subject_type', 'subject_id'], 'activity_subject_idx');
$table->index(['module_name', 'record_id'], 'activity_legacy_idx');
$table->index(['action', 'created_at'], 'activity_action_idx');
$table->index('created_at');
```

`module_name` + `record_id` are retained for backward compatibility during the
migration window, then dropped in Phase 15.

`tyro_audit_logs` gains `ip_address` and `user_agent`, and becomes append-only
(`AuditLog` overrides `delete()` to throw; no `updated_at` column is added).

### 4.13 `work_items` — Read View

```sql
CREATE OR REPLACE VIEW work_items AS
SELECT 'task'            AS source_type, id AS source_id, title,
       status, priority, responsible_user_id AS assignee_id, user_id AS creator_id,
       due_date, completed_at, project_id, deleted_at
FROM tasks
UNION ALL
SELECT 'todo', id, title, status, priority, assignee_id, creator_id,
       due_date, completed_at, NULL, deleted_at
FROM todos
UNION ALL
SELECT 'meeting_action_item', id, title, status, priority, assigned_to, created_by,
       due_date, completed_at, NULL, NULL
FROM meeting_action_items;
```

**Read-only.** Powers the dashboard, global search and cross-module reports. Never
written to; never joined to for mutations. Created in a migration guarded by
`DB::statement` for MySQL and skipped on SQLite (tests use the underlying tables).

---

## 5. Target Schema — Changes to Existing Tables

Every one of these is its own reversible migration. Ordered by priority.

| Order | Migration | Change | Gap |
|---|---|---|---|
| 1 | `fix_role_permissions_migration_order` | Defer the FK until after `roles` exists | DB-01 |
| 2 | `guard_hazira_column_migration` | Wrap in `Schema::hasColumn` | DB-02 |
| 3 | `port_migrations_to_sqlite` | Remove `ENUM`, `->after()`, raw `ALTER`, `information_schema` across all 68 | DB-03 |
| 4 | `add_indexes_to_existing_tables` | Every index in §2.3, additive only | DB-03/perf |
| 5 | `add_unique_permission_name` | Unique index on `permissions.permission_name` | DB-09 |
| 6 | `soften_ownership_cascade_deletes` | `tasks.user_id`, `meetings.organizer_id`, `obligations.owner_user_id`, `meeting_minutes_approvals.approver_id`, `task_remarks.user_id` → `nullOnDelete` + nullable | DB-05 |
| 7 | `widen_tasks_status_and_nullable_due_date` | Add `on_hold`, `cancelled`; make `due_date` nullable | GAP-026 |
| 8 | `add_meetings_location_id` | Nullable FK → `locations` | GAP-029 |
| 9 | `add_unique_sequences` | `UNIQUE(meeting_id, agenda_no)`, `(meeting_id, decision_no)`, `(meeting_id, version_no)`, `(meeting_id, action_no)` | DB-13 |
| 10 | `fix_meeting_attachments_cascade` | All four parents → `nullOnDelete`; require at least one non-null (app-level) | DB-06 |
| 11 | `fix_typo_file_attache` | Rename → `file_attachment` | DB-11 |
| 12 | `rename_obligations_category_id` | → `category_id` → `category_id` (keep column, document) or rename to `obligation_category_id` | DB-12 |
| 13 | `add_audit_ip_and_user_agent` | `tyro_audit_logs` + `ip_address` + `user_agent` | GAP-006 |
| 14 | `scope_escalation_and_approval_rules` | `escalation_rules` + `department_id`/`company_id`; `approval_workflows` + `subject_type`/`subject_id` | GAP-031 |
| 15 | `make_users_employee_id_required` | `NOT NULL` after backfill | GAP-040 |
| 16 | `consolidate_notification_logs` | Backfill the 3 tables into the polymorphic one, dual-write, then drop | GAP-023 |
| 17 | `drop_dead_tables` | `approval_workflows`, `_steps`, `meeting_versions`, `meeting_templates`, `_template_agendas` — **only after confirming zero references** | GAP-042 |

**Never drop `tblAccountInfo` without an explicit data-ownership decision.** It is
already harmless; deleting the migrations rewrites history.

### 5.1 Enum Removal Detail

Removing `ENUM()` from existing columns requires a column type change, which is not
portable. Two options:

- **Option A (recommended, deferred):** leave existing `ENUM` columns alone; only *new*
  columns use `string` + PHP enum. No migration risk. Accept a gradual mix.
- **Option B:** rebuild each table with a portable 3-step (`Schema::rename` →
  create new → copy → drop old). High risk, large tables, and it breaks any external
  consumer of the column type.

Choose **Option A**. The goal is that *new* work is portable and testable; rewriting
working tables for stylistic consistency is exactly the "blind rewrite" the brief warns
against.

---

## 6. Migration Strategy

### 6.1 Rules

1. One concern per migration. Never batch unrelated changes.
2. Every `down()` must genuinely restore prior state. If it cannot, document it in the
   migration class docblock and mark it in the Phase 15 runbook.
3. No raw SQL except where a driver genuinely cannot express it (views, ENUM rebuilds)
   — and even then, guard by driver and skip on SQLite so tests pass.
4. Guard with `Schema::hasTable`/`hasColumn`/`hasIndex` for idempotency. The existing
   `password_reset_tokens` double-create is the pattern to avoid.
5. Never edit a migration that has run in production. Add a new one.
6. Index migrations are always additive and always safe to run on a large table.
7. Run `php artisan migrate` on a **fresh empty database** as part of CI. That alone
   catches DB-01 and DB-02 permanently.

### 6.2 Test Database

Once DB-03 is closed:

```xml
<php>
  <env name="DB_CONNECTION" value="sqlite"/>
  <env name="DB_DATABASE" value=":memory:"/>
</php>
```

`phpunit.xml` already sets this. Today it produces "no such table: users" because
migration 3 fails. **This is the single highest-leverage fix in the entire programme.**

### 6.3 Large-Table Safety

For index additions on `activity_logs` and the notification logs, MySQL 8 supports
`ALGORITHM=INPLACE, LOCK=NONE` for index creation. Where a table is large enough to
matter, use the driver-specific path guarded by a driver check. At current data volumes
a plain additive index is fine — **measure before optimising** (brief §25).

### 6.4 Order of Execution

```
1. DB-01, DB-02, DB-03   (fresh migrate must succeed on sqlite AND mysql)
2. CI runs `migrate:fresh --env=testing` on every push
3. DB-04, DB-05, DB-06    (indexes, cascades — additive/reversible)
4. §4 new tables (todos + platform) — purely additive
5. DB-07 … DB-14         (target-state changes)
6. DB-15, DB-16          (consolidation — highest risk, do last)
7. DB-17                 (dead table drops — verify zero references first)
```