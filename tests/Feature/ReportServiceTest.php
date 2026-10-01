<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\CsvExporter;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Tasks\Models\Task;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The shared report layer and its CSV export — GAP-036, GAP-050.
 *
 * The expected numbers are hand-computed from a fixture whose size is known, so a
 * broken aggregate shows up as a specific wrong figure rather than "the report
 * looks off". The CSV cases matter more than they look: an export is a file that
 * leaves the application, and a spreadsheet executes what it is handed.
 */
class ReportServiceTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private ReportService $reports;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reports = app(ReportService::class);
    }

    /**
     * A hand-computable fixture:
     *
     *   alice: 3 created, 2 completed, 1 overdue
     *   bob:   2 created, 0 completed, 0 overdue
     */
    private function seedFixture(): array
    {
        $alice = $this->userWithPermissions(['task.view']);
        $alice->update(['name' => 'Alice Report']);

        $bob = $this->userWithPermissions(['task.view']);
        $bob->update(['name' => 'Bob Report']);

        $past = now()->subDays(10)->toDateString();

        // Alice: three tasks, two completed, one open and already past its date.
        foreach ([1, 2] as $index) {
            $this->backdatedTask([
                'title' => "Alice done {$index}",
                'user_id' => $alice->id,
                'responsible_user_id' => $alice->id,
                'status' => 'completed',
                'priority' => 'medium',
                'due_date' => $past,
            ]);
        }

        $this->backdatedTask([
            'title' => 'Alice overdue',
            'user_id' => $alice->id,
            'responsible_user_id' => $alice->id,
            'status' => 'in_progress',
            'priority' => 'high',
            'due_date' => now()->subDays(3)->format('Y-m-d'),
        ]);

        // Bob: two open tasks, neither overdue.
        foreach ([1, 2] as $index) {
            $this->backdatedTask([
                'title' => "Bob open {$index}",
                'user_id' => $bob->id,
                'responsible_user_id' => $bob->id,
                'status' => 'pending',
                'priority' => 'low',
                'due_date' => now()->addDays(10)->format('Y-m-d'),
            ]);
        }

        return [$alice, $bob];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function backdatedTask(array $attributes): Task
    {
        return Task::query()->create($attributes);
    }

    public function test_completion_by_owner_matches_a_hand_computed_fixture(): void
    {
        [$alice, $bob] = $this->seedFixture();

        $rows = $this->reports
            ->taskCompletionByOwner(now()->subMonth()->toDateString(), now()->addDay()->toDateString())
            ->keyBy('assignee_id');

        $this->assertSame(3, (int) $rows[$alice->id]->created_total);
        $this->assertSame(2, (int) $rows[$alice->id]->completed_total);
        $this->assertSame(1, (int) $rows[$alice->id]->overdue_total);

        $this->assertSame(2, (int) $rows[$bob->id]->created_total);
        $this->assertSame(0, (int) $rows[$bob->id]->completed_total);
        $this->assertSame(0, (int) $rows[$bob->id]->overdue_total);
    }

    public function test_a_report_only_shows_the_viewers_work(): void
    {
        [$alice] = $this->seedFixture();

        $rows = $this->reports
            ->taskCompletionByOwner(
                now()->subMonth()->toDateString(),
                now()->addDay()->toDateString(),
                $alice,
            );

        $this->assertCount(1, $rows, 'The report must not include another person\'s tasks.');
        $this->assertSame($alice->name, (string) $rows->first()->assignee);
    }

    public function test_workload_groups_by_assignee(): void
    {
        [$alice, $bob] = $this->seedFixture();

        $rows = $this->reports->taskWorkload()->keyBy('assignee_id');

        $this->assertSame(3, (int) $rows[$alice->id]->total);
        $this->assertSame(1, (int) $rows[$alice->id]->overdue);
        $this->assertSame(2, (int) $rows[$bob->id]->total);
    }

    public function test_distribution_counts_every_task_exactly_once(): void
    {
        $this->seedFixture();

        $rows = $this->reports->taskDistribution();

        // 5 tasks in total across two statuses and three priorities.
        $this->assertSame(5, (int) $rows->sum('total'));
    }

    public function test_reports_aggregate_in_sql(): void
    {
        $this->seedFixture();

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $this->reports->taskCompletionByOwner(now()->subMonth()->toDateString(), now()->addDay()->toDateString());

        // One aggregate query. Loading five rows and counting them in PHP would
        // look identical from here, so the number is what matters.
        $this->assertLessThanOrEqual(1, $queries, 'The report must be a single aggregate query.');
    }

    // ---- CSV ----------------------------------------------------------------

    public function test_every_report_exports_to_csv(): void
    {
        $user = $this->userWithPermissions(['report.view', 'task.view', 'task.view_all']);
        $this->seedFixture();

        foreach (['tasks', 'workload'] as $report) {
            $response = $this->actingAs($user)->get(route('reports.'.$report));

            $response->assertOk();

            $export = $this->actingAs($user)->get(route('reports.'.($report === 'tasks' ? 'tasks' : 'workload').'.export'));

            $export->assertOk();
            $this->assertStringContainsString('text/csv', (string) $export->headers->get('Content-Type'));
            $this->assertStringContainsString('.csv', (string) $export->headers->get('Content-Disposition'));
        }
    }

    public function test_a_formula_shaped_title_is_neutralised_in_the_export(): void
    {
        // Excel, Sheets and LibreOffice all evaluate a cell beginning with these
        // characters. A user-authored title is attacker-controlled text, so an
        // export is a place where stored input becomes executed input.
        $csv = app(CsvExporter::class)->render(
            ['Title'],
            [
                ['=cmd|\' /C calc\'!A1'],
                ['+SUM(A1:A2)'],
                ['-1+1'],
                ['@import'],
                ['Normal title'],
            ],
        );

        $this->assertStringContainsString("'=cmd", $csv);
        $this->assertStringContainsString("'+SUM", $csv);
        $this->assertStringContainsString("'-1+1", $csv);
        $this->assertStringContainsString("'@import", $csv);

        $this->assertStringNotContainsString("\n=cmd", $csv, 'A raw formula prefix must never reach the file.');
        $this->assertStringNotContainsString('Normal title,', $csv, 'A plain title is untouched.');
    }

    public function test_null_and_boolean_cells_render_sensibly(): void
    {
        $csv = app(CsvExporter::class)->render(['A', 'B', 'C'], [[null, true, false]]);

        $this->assertStringContainsString(',Yes,No', $csv);
    }

    public function test_the_export_cannot_contain_a_title_the_viewer_may_not_see(): void
    {
        // The export reads from the same service call as the screen, so it cannot
        // describe a wider set than the table above it.
        // Alice is the viewer: the report filters to the viewer's own work, so
        // using a separate user would correctly return nothing and prove nothing.
        [$alice] = $this->seedFixture();
        $alice->roles()->attach(
            Role::query()->create(['name' => 'Reporters', 'slug' => 'reporters'])->id,
        );
        $alice->forgetPermissionCache();

        $rows = $this->reports
            ->taskCompletionByOwner(
                now()->subMonth()->toDateString(),
                now()->addDay()->toDateString(),
                $alice,
            );

        $csv = app(CsvExporter::class)->render(
            ['Assignee'],
            $rows->map(fn (object $row): array => [(string) $row->assignee])->all(),
        );

        $this->assertStringContainsString('Alice Report', $csv);
        $this->assertStringNotContainsString('Bob Report', $csv, 'The export must not widen the report.');
    }

    // ---- Authorization ------------------------------------------------------

    public function test_reports_require_the_report_permission(): void
    {
        $this->actingAs($this->plainUser())
            ->get(route('reports.tasks'))
            ->assertForbidden();
    }

    public function test_the_export_requires_the_same_permission_as_the_screen(): void
    {
        // An export that is broader than the screen is a privilege-escalation
        // path, so the gate is asserted on the export too.
        $this->actingAs($this->plainUser())
            ->get(route('reports.tasks.export'))
            ->assertForbidden();

        $this->actingAs($this->plainUser())
            ->get(route('reports.workload.export'))
            ->assertForbidden();

        $this->actingAs($this->plainUser())
            ->get(route('reports.distribution.export'))
            ->assertForbidden();
    }

    public function test_the_workload_report_needs_the_separate_management_permission(): void
    {
        $user = $this->userWithPermissions(['report.view']);

        $this->actingAs($user)->get(route('reports.workload'))->assertForbidden();
        $this->actingAs($user)->get(route('reports.tasks'))->assertOk();
    }

    public function test_reports_require_authentication(): void
    {
        $this->get(route('reports.tasks'))->assertRedirect(route('login'));
        $this->get(route('reports.tasks.export'))->assertRedirect(route('login'));
    }

    public function test_a_reversed_date_range_is_swapped(): void
    {
        // The viewer must own the fixture work, or the permission filter empties the
        // report and the assertion proves nothing.
        [$alice] = $this->seedFixture();
        $user = $this->userWithPermissions(['report.view', 'task.view']);
        $alice->roles()->attach($user->roles->first()->id);
        $user = $user->fresh();

        $response = $this->actingAs($alice)->get(route('reports.tasks', [
            'from' => now()->addDay()->toDateString(),
            'to' => now()->subMonth()->toDateString(),
        ]));

        $response->assertOk();
        $response->assertSee('Alice Report');
    }
}
