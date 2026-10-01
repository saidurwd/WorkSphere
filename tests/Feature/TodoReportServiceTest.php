<?php

namespace Tests\Feature;

use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Models\User;
use Database\Factories\DepartmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoReportService;
use Tests\TestCase;

/**
 * TodoReportService — TODO-MODULE-SPECIFICATION.md §10.
 *
 * Every expected number here is hand-computed from a fixture whose size is
 * known, so a broken aggregate shows up as a specific wrong figure rather than a
 * vague "report looks wrong". Aggregation happens in SQL: the N+1 case at the
 * bottom fails if a report ever starts loading rows into PHP.
 */
class TodoReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private TodoReportService $reports;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reports = app(TodoReportService::class);
    }

    public function test_summary_counts_each_state_once(): void
    {
        $user = User::factory()->create();

        // 4 open (3 assigned + 1 unassigned), 1 of the assigned overdue, 1 completed.
        Todo::factory()->count(2)->assignedTo($user)->create();
        Todo::factory()->overdue()->assignedTo($user)->create();
        Todo::factory()->completed()->assignedTo($user)->create();
        Todo::factory()->create(['assignee_id' => null]);

        $summary = $this->reports->summary();

        $this->assertSame(4, (int) $summary->open, 'The unassigned To-Do is open too.');
        $this->assertSame(1, (int) $summary->overdue);
        $this->assertSame(1, (int) $summary->completed);
        $this->assertSame(1, (int) $summary->unassigned);
    }

    public function test_completion_by_owner_counts_only_completed_rows_in_the_window(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        Todo::factory()->completed()->count(2)->assignedTo($alice)->create(['completed_at' => '2026-10-05']);
        Todo::factory()->completed()->count(3)->assignedTo($bob)->create(['completed_at' => '2026-10-06']);
        // Outside the window.
        Todo::factory()->completed()->assignedTo($alice)->create(['completed_at' => '2026-01-01']);

        $rows = $this->reports->completionByOwner('2026-10-01', '2026-10-31')
            ->keyBy('assignee_id');

        $this->assertSame(2, (int) $rows[$alice->id]->completed_total);
        $this->assertSame(3, (int) $rows[$bob->id]->completed_total);
    }

    public function test_completion_by_department_groups_correctly(): void
    {
        $first = DepartmentFactory::new()->create();
        $second = DepartmentFactory::new()->create();

        Todo::factory()->completed()->count(2)->create(['department_id' => $first->id, 'completed_at' => '2026-10-05']);
        Todo::factory()->completed()->create(['department_id' => $second->id, 'completed_at' => '2026-10-05']);

        $rows = $this->reports->completionByDepartment('2026-10-01', '2026-10-31')
            ->keyBy('department_id');

        $this->assertSame(2, (int) $rows[$first->id]->completed_total);
        $this->assertSame(1, (int) $rows[$second->id]->completed_total);
    }

    public function test_overdue_excludes_completed_and_archived(): void
    {
        $user = User::factory()->create();

        Todo::factory()->overdue()->assignedTo($user)->create();
        Todo::factory()->overdue()->assignedTo($user)->completed()->create([
            'due_date' => now()->subDays(5)->format('Y-m-d'),
            'completed_at' => now(),
        ]);
        Todo::factory()->overdue()->assignedTo($user)->create([
            'status' => WorkItemStatus::Archived,
            'archived_from' => 'in_progress',
        ]);
        Todo::factory()->assignedTo($user)->create(['due_date' => now()->addDays(5)->format('Y-m-d')]);

        $rows = $this->reports->overdueByOwner();
        $total = $rows->sum('overdue_total');

        $this->assertSame(
            1,
            (int) $total,
            'Only open, past-due work counts as overdue.',
        );
    }

    public function test_personal_productivity_computes_a_completion_rate(): void
    {
        $user = User::factory()->create();

        // 4 created, 1 completed => 25%.
        Todo::factory()->count(3)->createdBy($user)->create(['created_at' => '2026-10-01']);
        Todo::factory()->completed()->createdBy($user)->create([
            'created_at' => '2026-10-01',
            'start_date' => '2026-10-01',
            'completed_at' => '2026-10-05',
        ]);

        $row = $this->reports->personalProductivity('2026-10-01', '2026-10-31')
            ->firstWhere('creator_id', $user->id);

        $this->assertSame(4, (int) $row->created_total);
        $this->assertSame(1, (int) $row->completed_total);
        $this->assertSame(25, $row->completion_rate);
        $this->assertSame(4, (int) $row->avg_days_to_complete);
    }

    public function test_workload_distribution_reports_open_and_overdue(): void
    {
        $user = User::factory()->create();

        Todo::factory()->count(2)->assignedTo($user)->create();
        Todo::factory()->overdue()->assignedTo($user)->create();

        $row = $this->reports->workloadDistribution()->firstWhere('assignee_id', $user->id);

        $this->assertSame(3, (int) $row->open_total);
        $this->assertSame(1, (int) $row->overdue_total);
    }

    public function test_overdue_trend_buckets_by_week(): void
    {
        $user = User::factory()->create();

        Todo::factory()->overdue()->count(2)->assignedTo($user)->create([
            'due_date' => '2026-10-05',
        ]);
        Todo::factory()->overdue()->assignedTo($user)->create([
            'due_date' => '2026-10-20',
        ]);

        $rows = $this->reports->overdueTrend('2026-10-01', '2026-10-31');

        $this->assertCount(2, $rows, 'Two distinct weeks should produce two buckets.');
        $this->assertSame(3, (int) $rows->sum('overdue_total'));
    }

    public function test_recurrence_adherence_reports_generated_versus_completed(): void
    {
        $user = User::factory()->create();
        $rule = ['frequency' => 'weekly', 'start_date' => '2026-10-01', 'max_occurrences' => 10];

        Todo::factory()->count(3)->createdBy($user)->create([
            'recurrence_rule' => $rule,
            'created_at' => '2026-10-01',
        ]);
        Todo::factory()->completed()->createdBy($user)->create([
            'recurrence_rule' => $rule,
            'created_at' => '2026-10-01',
        ]);

        $row = $this->reports->recurrenceAdherence('2026-10-01', '2026-10-31')
            ->firstWhere('creator_id', $user->id);

        $this->assertSame(4, (int) $row->generated_total);
        $this->assertSame(1, (int) $row->completed_total);
        $this->assertSame(25, $row->adherence_rate);
    }

    public function test_soft_deleted_todos_are_excluded_from_every_report(): void
    {
        $user = User::factory()->create();

        $todo = Todo::factory()->completed()->assignedTo($user)->create([
            'completed_at' => '2026-10-05',
        ]);

        $this->assertSame(1, (int) $this->reports->summary()->completed);

        $todo->delete();

        $this->assertSame(0, (int) $this->reports->summary()->completed);
        $this->assertCount(0, $this->reports->completionByOwner('2026-10-01', '2026-10-31'));
    }

    public function test_reports_aggregate_in_sql_and_do_not_load_rows(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 60) as $index) {
            Todo::factory()->createdBy($user)->create([
                'assignee_id' => $index % 2 === 0 ? $user->id : null,
                'status' => $index % 3 === 0 ? WorkItemStatus::Completed : WorkItemStatus::InProgress,
                'visibility' => Visibility::Personal,
            ]);
        }

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->reports->summary();
        $this->reports->completionByOwner('2026-01-01', '2026-12-31');
        $this->reports->overdueByOwner();
        $this->reports->workloadDistribution();
        $this->reports->overdueTrend('2026-01-01', '2026-12-31');
        $this->reports->personalProductivity('2026-01-01', '2026-12-31');
        $this->reports->recurrenceAdherence('2026-01-01', '2026-12-31');

        // Six reports, each one aggregate query. Anything approaching the row
        // count means a report has started iterating in PHP.
        $this->assertLessThanOrEqual(
            8,
            $queries,
            "Reports issued {$queries} queries for 60 rows; aggregation is happening in PHP.",
        );
    }
}
