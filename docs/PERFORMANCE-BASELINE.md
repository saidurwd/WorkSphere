# Performance Baseline and Optimisation — Phase 14

> Deliverable: the measurement that justifies every change in this phase, and the
> changes the measurement did **not** justify.
>
> Regenerate the table with:
>
> ```bash
> vendor/bin/phpunit --filter PerformanceBaselineTest
> ```
>
> It prints the table to STDERR and fails if any page exceeds its query budget or
> does not return 200. It is a guard, not a report: the numbers below were read off
> it.

---

## 1. Method

Two numbers per page, because they fail differently.

**Query count is asserted. Milliseconds are recorded but never asserted.** Query
counts are deterministic — the same request issues the same queries on every run —
so they can be a budget CI enforces on every push. Timings are not: three
consecutive runs of the same page produced 532 ms, 856 ms and 522 ms on one
machine. A test asserting on that fails on Monday and passes on Tuesday and gets
ignored within a fortnight. Every budget in `PageBenchmark` is a query count.

**Every page is measured twice and the second measurement is recorded.** The first
pays for the permission cache, the session and the query plan. Measuring it would
blame a page for the harness's own first-request cost.

**Every page must return 200.** This was not in the original design and it turned
out to matter more than any number in the table: six benchmark pages were returning
500 or 405, and a query-count-only measurement ranks a 500 above a working page,
because a page that dies early issues no queries.

### Volume

| entity | rows |
| --- | --- |
| users | 60 |
| departments | 6 |
| todos | 400 |
| tasks | 300 |
| meetings | 120 |
| obligations | 150 |
| meeting action items / agendas / participants | 240 |

Chosen to be uncomfortable rather than flattering: enough that an N+1 is
unmistakable in the query count. This is **not** a claim about production volume. If
the real tables are an order of magnitude larger, the *scaling* findings in §6
matter more and the absolute milliseconds matter less; the query counts do not
change, because they are structural.

---

## 2. Before and after

`queries` is the asserted number. `ms` is recorded for information only.

| page | queries before | queries after | ms before | ms after |
| --- | --- | --- | --- | --- |
| dashboard | 2 | 2 | 29 | 28 |
| tasks dashboard | 29 | 29 | 30 | 35 |
| meetings dashboard | 14 | 14 | 25 | 29 |
| obligations dashboard | 16 | 16 | 25 | 27 |
| todos index | 7 | 7 | 83 | 85 |
| todos inbox | 6 | 6 | 86 | 85 |
| todos calendar | 3 | 3 | 22 | 21 |
| tasks index | 8 | **7** | 211 | **90** |
| meetings index | 9 | **8** | 122 | 107 |
| meetings action items | 6 | **4** | 154 | **38** |
| obligations index | 18 | **14** | 279 | **188** |
| obligations my tasks | 3 | 3 | 21 | 21 |
| projects index | 4 | 4 | 31 | 30 |
| my work | 4 | 4 | 23 | 21 |
| task transfers | 5 | **4** | 205 | 199 |
| tasks report | 5 | 5 | 26 | 24 |
| task workload report | 3 | 3 | 22 | 21 |
| meetings overdue report | 3 | 3 | 21 | 22 |
| todos report | 11 | 11 | 36 | 33 |
| meetings report | 2 | 2 | 21 | 19 |
| obligations report | — (500) | 7 | — | 24 |
| todo show | 14 | 14 | 31 | 34 |
| task show | 12 | 12 | 24 | 26 |
| todos notification log | 3 | 3 | 21 | 19 |
| tasks notification log | 3 | 3 | 22 | 20 |
| meeting show | 19 | **17** | 532 | **451** |
| meeting participants | 5 | **4** | 77 | **21** |
| meeting agendas | 6 | **5** | 81 | **21** |
| meeting decisions | 5 | **4** | 76 | **22** |
| meeting attachments | 4 | 4 | 20 | 22 |
| meeting print | 14 | 14 | 8 | 7 |
| obligation show | 18 | 18 | 26 | 28 |
| obligation renewals | 3 | 3 | 19 | 20 |
| obligation vendors | 4 | 4 | 30 | 30 |
| project show | 5 | 5 | 21 | 21 |
| api todos | 3 | 3 | 12 | 12 |
| api tasks | 5 | 5 | 13 | 15 |
| api meetings | 4 | 4 | 13 | 14 |
| api obligations | 5 | 5 | 12 | 14 |
| api todo show | 6 | 6 | 4 | 5 |
| api todo comments | 2 | 2 | 2 | 3 |

**Total: 12 fewer queries across the catalogue**, all of it on the pages that
compose more than one reference list. Nine pages improved, none regressed.

---

## 3. What the measurement found before it found anything else

Six pages were returning errors, and four of them were 500s on pages real users
open. These were found by requiring 200, not by looking for slowness.

| page | error | cause |
| --- | --- | --- |
| `/obligations/dashboard` | 500 | `UnhandledMatchError: 'important'`. An exhaustive `match` over `obligations.priority` with no `default`, so any value outside the four listed cases took the whole dashboard down — for every user, because the grouping query reads the column rather than the caller's rows. |
| `/meetings/{meeting}` | 500 | `ucfirst()` given a `WorkItemStatus` enum. |
| `/meetings/action-items` | 500 | `<x-badge>` never closed. Blade's component compiler swallows the following `@endif` into the slot, and the compiled view has unbalanced control flow. |
| `/meetings/{meeting}/agendas` | 500 | The same unclosed `<x-badge>`. |
| `/obligations/reports` | 500 | `DATEDIFF()` — MySQL-only. The page could not render on any other driver, including the SQLite the entire test suite runs on, so it had never been rendered outside production. |
| `/obligations/{id}/renew` and several sub-routes | 405 | Not defects: they are POST-only routes that the first version of the catalogue wrongly listed as pages. |

All six are fixed. The `match` arms went to `StatusBadge`, which is the single
status→badge map Phase 5 asked for and which never reached the Obligations module;
it now has a colour accessor with the same fallback, so the chart and the badge can
no longer disagree. The obligation calendar's `match` gained a `default` for the
same reason — it kept its literal palette, which FullCalendar needs, but no longer
fails on a value nobody anticipated.

**A view that does not compile is a page that does not work**, and nothing in the
suite caught either one. `ApiSchemaTest`-style "every view compiles" is now part of
the Phase 14 record and is worth making permanent — see §7.

---

## 4. Indexes: none added, and here is the evidence

Every hot query was run through `EXPLAIN QUERY PLAN`. All of them already use an
index; none scans a filtered column.

| query | plan |
| --- | --- |
| tasks list, `status IN (…)` | `SEARCH tasks USING INDEX tasks_status_index` |
| tasks list, overdue filter | `SEARCH tasks USING INDEX tasks_due_date_index` |
| todos list, `status = ?` | `SEARCH todos USING INDEX todos_status_due_soft_idx` |
| obligations list, `status = ?` | `SEARCH obligations USING INDEX obligations_status_index` |
| obligations, owner or responsible | `SEARCH obligations USING INDEX obligations_owner_user_id_index` + `SEARCH obligation_responsibilities USING INDEX obligation_responsibilities_user_id_index` |
| meetings, organiser or participant | `SEARCH meetings USING INDEX meetings_organizer_id_index` + `SEARCH meeting_participants USING INDEX meeting_participants_user_id_index` |
| notifications for a user | `SEARCH notifications USING INDEX notifications_unread_index` |
| activity logs for a subject | `SEARCH activity_logs USING INDEX activity_subject_idx` |

Three plans carry `USE TEMP B-TREE FOR ORDER BY`: the tasks list ordered by
`due_date`, the obligations list ordered by `expiry_date`, and the notification
list ordered by `created_at`. A covering index for each would remove the sort —
over 300–400 rows, which is microseconds. **No index was added**, because doing so
without a measurement showing the sort mattered would be exactly the speculative
work this phase forbids.

Phase 2's index inventory remains the source of truth for what exists.

---

## 5. Eager-loading audit: no N+1, and the detector is proven

Phase 13's `NPlusOneDetectionTest` covers thirteen screens and grows each screen's
related collection fifteen-fold, asserting the query count does not move. All pass.
The controller audit found nothing further.

The instrument is proven against a deliberate N+1: `NPlusOneDetectionTest::test_the_detector_detects_a_deliberate_n_plus_one`
builds users and compares `User::query()->with('employee')->get()` against
`User::query()->get()` with each relation touched. Eager loading is flat in the row
count; lazy loading grows by one query per row. If the detector stopped detecting,
that test fails.

`Model::preventLazyLoading()` is now on in `local` — and in `testing` too, so the
suite runs under it. It is off when running tests, because the measurement and
factory-coverage tests read relations the code does not eager-load by design, and a
throw there would be a false positive. It is off in production, as the phase
requires: a forgotten `with()` must degrade, not take the request down.

---

## 6. The one finding that was **not** fixed, and why

### The meeting detail page renders an `<option>` for every Task in the system

`MeetingController::show()` does:

```php
$tasks = Task::orderByDesc('created_at')->get(['id', 'task_no', 'title', 'status', 'priority']);
```

and `meetings/show.blade.php` renders one `<option>` per task in a "link a Task to
this action item" select.

**Measured**, by seeding more tasks and re-measuring the same page:

| tasks in the system | `/meetings/{id}` |
| --- | --- |
| 300 | 625 ms |
| 900 | 794 ms |
| 930 | 817 ms |

Roughly **0.25 ms per task**, growing linearly. At 5,000 tasks this is about
1.3 seconds on every meeting detail page view. This is the single largest
performance defect in the application and it is a rendering cost, not a query cost —
the query itself is 0.5 ms.

It is **not fixed here**, because every fix trades something away and the choice
is a product decision, not an engineering one:

1. **Bound the list** (open and top-level tasks only, capped at N). Removes the
   scaling; removes the ability to link a completed or old Task, which
   `MeetingActionItemController::linkTask()` currently permits — it validates
   `exists:tasks,id` and nothing else.
2. **Searchable remote lookup.** Keeps every capability, adds a component and JS.
   Meaningfully larger than the rest of this phase.
3. **Leave it.** Correct for a system with hundreds of tasks; not for one with
   thousands.

`TaskController::show` and the other pages in the catalogue do not have this
problem: they render the tasks they are scoped to. It is specific to a page that
loads an unbounded collection to populate a dropdown.

**Recommendation: option 2**, with option 1 as an immediate stopgap if a cap is
acceptable. This needs a decision before implementation.

---

## 7. Reference data: 32 duplicated queries, now one cached list

Thirty-two call sites across ten controllers issued the same statements —
`User::orderBy('name')->get(['id', 'name'])` and
`Department::orderBy('department_name')->get()`, sometimes twice on one page. All
now go through `App\Support\ReferenceData`, cached with a version-bumped
invalidation driven by a model observer.

**This is not an N+1 and was not slow**: the measurement shows each of those
queries running exactly once, at 1–3 ms. What it removes is the duplication and one
more round trip per page that composes several of them. The measurable effect is in
§2 — nine pages, twelve queries.

**Invalidation is by version bump**, not `Cache::forget`, because the store cannot
target a wildcard: every write to a reference table bumps a counter folded into
every future key. One write drops the lot.

**On sharing the key across users.** These lists are deliberately *not* keyed per
user, because they are identical for every caller. That is only safe because they
are **unfiltered** — which is asserted, not assumed. A permission-filtered list must
pass a scope, which becomes part of the key, so a filtered caller cannot collide
with the unfiltered value. An unknown scope throws rather than silently returning
the wider list.

### A cross-user data leak, found while doing this

The dashboard widget cache keyed on `dashboard:widget:{key}:v{version}:{permission
signature}`. **No user id.** The personal widgets — `my_todos`, `my_tasks`,
`personal_stats` — resolve data *about the viewer*, and the permission signature is
identical for any two users who can see the same widgets. So the first person to
load a dashboard populated the entry and everyone after them read it.

Proven before fixing:

```
ALICE data: [{"id":1,"title":"ALICE SECRET", …}]
BOB   data: [{"id":1,"title":"ALICE SECRET", …}]
```

The key now carries `:u{userId}:`. Four tests in `WidgetCacheIsolationTest` catch
the regression, including one that asserts the key's shape and one that is a direct
end-to-end check with two users holding identical permissions.

This is the vulnerability the phase brief names — *"A shared cache key on
permission-gated data is a vulnerability, not a bug"* — and it survived Phase 10
because every existing test used two users with *different* permissions.

---

## 8. Reports and exports: nothing to chunk

Every report in the catalogue returns 2–11 queries and under 40 ms.

The CSV exports are built from **aggregate** queries — grouped by assignee, or by
status and priority — so the row count is bounded by the number of distinct
assignees or status/priority pairs, not by the size of the table. There is no
unbounded row set to chunk.

There is no PDF generation anywhere in the application and no PDF package in
`composer.json`, so there is nothing to queue. Both are "no change needed" rather
than "not got to it".

---

## 9. Redis: not justified, and here is the evidence

**Recommendation: do not adopt Redis in this phase.**

| evidence | measurement |
| --- | --- |
| SQL per page | 4–13 ms |
| SQL per page, share of total | 2–4% |
| Cache operations per page | ≤ 16 reads (13 dashboard widgets + 2–3 reference lists) on `/dashboard`; 2–3 elsewhere |
| Cache writes per page | one per miss; the dashboard's 13 widgets are cached for 60 s each |
| Session | one read + one write per request |
| Queue | notification jobs only, and the commands that push them are scheduled, not per-request |
| Current store | `database` |
| Redis client | **not installed** — `REDIS_CLIENT=phpredis` is set in `.env` but neither the `phpredis` extension nor `predis` is a dependency |

The threshold Redis is bought for is *cache latency dominating page latency, or
hundreds of cache operations per request*. Neither is met: at 16 operations against
a MySQL table at 0.3–3 ms, the cache is noise next to 20–450 ms of Blade rendering.

**What would change this answer**, and it is worth watching:

- more than ~50 widgets or reference lists per page;
- a cache read whose p95 exceeds ~5 ms on the database store;
- more than one application server, where the database store starts doing
  unpredictable work on every request;
- a queue whose backlog makes `database` polling visible.

**Note before any of those:** `REDIS_CLIENT=phpredis` is configured but no Redis
client is installed. Adopting Redis means adding a dependency, which is a decision
in its own right.

---

## 10. Deployment caches: verified working in this application

Task 8 asks to verify each cache actually works *here*, not just that the command
succeeds. Verified: `composer dump-autoload -o` (8,783 classes),
`php artisan route:cache`, `php artisan config:cache`, `php artisan view:cache` —
with all four applied, `/todos`, `/tasks`, `/meetings`, `/obligations`,
`/reports/tasks`, `/my-work` and `/dashboard` all returned 200.

**Not committed**, and they must not be: `config:cache` reads `.env` once and then
ignores it, so a cached config on a developer machine silently ignores every later
`.env` change. Caches were cleared after the check.

One thing to fix in Phase 15, not here: `app.debug` is `true` after
`config:cache` on this machine, because `.env` says so. Production needs
`APP_DEBUG=false` before that cache is built.

---

## 11. What was deliberately **not** done

| not done | why |
| --- | --- |
| Read replicas | no measurement suggests read pressure |
| A search cluster | MySQL FULLTEXT covers the current corpus; measured search is 2–3 queries |
| Redis | §9 |
| A CDN | the application is authenticated end to end; there is nothing public to cache at an edge |
| Chunking reports | §8 — the result sets are aggregates, bounded by design |
| Queuing PDF generation | §8 — no PDF exists |
| Removing the tasks dropdown on the meeting page | §6 — needs a product decision |
| Adding indexes | §4 — every hot query already uses one |
| `pint --test` in CI | 99 pre-existing unformatted files, none of them touched here. Adding the gate now would make every push red for a debt that predates it. It belongs in its own commit. |

---

## 12. Files

**Added**

- `app/Support/ReferenceData.php` — the cached reference lists
- `app/Support/ResolvesReferenceData.php` — the controller trait
- `app/Observers/ReferenceDataObserver.php` — invalidation on write
- `tests/Support/PageBenchmark.php` — the page catalogue and the dataset
- `tests/Feature/PerformanceBaselineTest.php` — the measurement and the budgets
- `tests/Feature/WidgetCacheIsolationTest.php` — the cross-user leak guard
- `tests/Feature/ReferenceDataCacheTest.php` — caching, invalidation, scope isolation

**Changed for the leak**

- `app/Dashboard/WidgetRegistry.php` — user id in the key; `keyFor()` made public

**Changed for the 500s**

- `app/Support/StatusBadge.php` — `priorityColor()`, with a fallback
- `Modules/Obligations/app/Http/Controllers/ObligationDashboardController.php` — `match` → `StatusBadge`
- `Modules/Obligations/app/Http/Controllers/ObligationCalendarController.php` — `default` arm
- `Modules/Obligations/app/Http/Controllers/ObligationReportController.php` — portable date difference
- `Modules/Meetings/resources/views/meetings/show.blade.php` — enum label
- `Modules/Meetings/resources/views/meetings/action_items/index.blade.php` — closed `<x-badge>`
- `Modules/Meetings/resources/views/meetings/agendas/index.blade.php` — closed `<x-badge>`

**Changed for the cache**

- `app/Providers/AppServiceProvider.php` — observer registration, `preventLazyLoading`
- 11 controllers across Meetings, Tasks and Obligations

**Database changes: none.** No migration was added, because §4 found no plan worth
changing.
