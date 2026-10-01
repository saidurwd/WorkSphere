<?php

namespace Tests\Feature;

use App\Enums\WorkItemStatus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Database\Factories\MeetingFactory;
use Database\Factories\ObligationFactory;
use Database\Factories\TaskFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Todos\Models\Todo;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The dashboard's performance budget, measured — the brief is explicit that
 * "< 500 ms" must be measured, not assumed.
 *
 * Two things are asserted, because either alone would be misleading:
 *
 * 1. **Wall-clock.** A generous ceiling on SQLite with a synthetic dataset big
 *    enough to matter. It will not catch a query that is merely slow on MySQL.
 * 2. **Query count**, which is the stable signal. The dashboard is a fixed set of
 *    panels; if resolving it issues a query per panel per row — or, worse, per
 *    row per relation — the count grows with the data and the wall-clock figure
 *    would move only slightly at this scale.
 */
class DashboardPerformanceTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private const BUDGET_MS = 500;

    /**
     * Roughly 30 rows per source, which is past the point where an N+1 shows up
     * in the query count even though the wall-clock is still small.
     */
    private function seedVolume(): User
    {
        $user = $this->userWithPermissions([
            'todos.view', 'task.view', 'task.view_all', 'meeting.view',
            'obligation.view', 'obligation.view_reports', 'report.view',
        ]);

        $department = Department::factory()->create();
        $employee = Employee::factory()->create(['department_id' => $department->id]);
        $user->update(['employee_id' => $employee->id]);
        $user->forgetPermissionCache();

        $open = [WorkItemStatus::Inbox, WorkItemStatus::InProgress, WorkItemStatus::Waiting];

        Todo::factory()->count(30)->create([
            'creator_id' => $user->id,
            'assignee_id' => $user->id,
            'status' => $open[array_rand($open)],
            'due_date' => now()->addDays(random_int(-5, 20))->format('Y-m-d'),
            'department_id' => $department->id,
        ]);

        TaskFactory::new()->count(30)->create([
            'user_id' => $user->id,
            'responsible_user_id' => $user->id,
            'status' => random_int(0, 1) === 0 ? 'completed' : 'in_progress',
            'due_date' => now()->addDays(random_int(-5, 20))->format('Y-m-d'),
        ]);

        $meetings = MeetingFactory::new()->count(10)->organisedBy($user)->create();

        foreach ($meetings as $meeting) {
            MeetingActionItem::query()->create([
                'meeting_id' => $meeting->id,
                'action_no' => 1,
                'title' => 'Action',
                'priority' => 'high',
                'status' => 'open',
                'assigned_to' => $user->id,
            ]);
        }

        ObligationFactory::new()->count(20)->create([
            'owner_user_id' => $user->id,
            'status' => 'active',
            'expiry_date' => now()->addDays(random_int(-5, 60))->format('Y-m-d'),
            'risk_level' => random_int(0, 1) === 0 ? 'critical' : 'high',
            'department_id' => $department->id,
        ]);

        return $user->fresh();
    }

    public function test_the_dashboard_renders_within_the_time_budget(): void
    {
        $user = $this->seedVolume();

        // Warm the caches so the measurement is of a steady-state render, which is
        // what a user experiences on a second page load rather than a cold one.
        $this->actingAs($user)->get(route('dashboard.index'));

        $started = hrtime(true);

        $response = $this->actingAs($user)->get(route('dashboard.index'));

        $elapsedMs = (hrtime(true) - $started) / 1_000_000;

        $response->assertOk();

        $this->assertLessThan(
            self::BUDGET_MS,
            $elapsedMs,
            sprintf('Dashboard took %.1f ms, over the %.0f ms budget.', $elapsedMs, self::BUDGET_MS),
        );
    }

    public function test_the_query_count_does_not_grow_with_the_data(): void
    {
        // The stable signal. A dashboard is a fixed set of panels, so its query
        // count should be roughly constant regardless of how many rows each
        // panel aggregates over. Growth here is an N+1.
        $small = $this->userWithPermissions([
            'todos.view', 'task.view', 'task.view_all', 'report.view',
            'obligation.view', 'obligation.view_reports', 'meeting.view',
        ]);

        $measure = function (User $user): int {
            $queries = 0;
            DB::listen(function () use (&$queries): void {
                $queries++;
            });

            $this->actingAs($user)->get(route('dashboard.index'));

            return $queries;
        };

        $few = $measure($small);

        foreach (range(1, 4) as $_) {
            Todo::factory()->count(20)->createdBy($small)->assignedTo($small)->create();
            TaskFactory::new()->count(20)->create(['user_id' => $small->id, 'responsible_user_id' => $small->id]);
        }

        // A second dashboard render, so the counters do not include the first.
        $many = $measure($small->fresh());

        $this->assertLessThanOrEqual(
            $few + 5,
            $many,
            sprintf(
                'Query count grew from %d to %d after quadrupling the data — that is an N+1, not a fixed panel count.',
                $few,
                $many,
            ),
        );
    }

    public function test_no_widget_resolves_a_query_per_row(): void
    {
        $user = $this->seedVolume();

        $this->actingAs($user)->get(route('dashboard.index'));

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->actingAs($user)->get(route('dashboard.index'));

        // A fixed panel count, not a per-row figure. Every widget caps its output
        // and aggregates in SQL, so this stays flat.
        //
        // The ceiling was 40 when this test was written and the dashboard issued
        // 48: `User::roleSlugs()` was uncached, so every policy and Gate check
        // re-queried the role join — 40 of them. Caching it brought the render to
        // 9. 20 leaves room for a widget or two without permitting the old shape
        // back in.
        $this->assertLessThan(
            20,
            $queries,
            sprintf('A dashboard render issued %d queries at 30 rows per source.', $queries),
        );
    }
}
