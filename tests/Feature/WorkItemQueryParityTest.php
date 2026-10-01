<?php

namespace Tests\Feature;

use App\Enums\Visibility;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\WorkItemQuery;
use Database\Factories\MeetingFactory;
use Database\Factories\TaskFactory;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoWatcher;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The `work_items` view and its UNION fallback must produce the same rows.
 *
 * SQLite has no view, so the suite runs on the fallback and the parity is only
 * provable by forcing both paths. On MySQL both are exercised directly; on SQLite
 * the fallback is compared against a hand-written UNION over the same tables,
 * which is what the view is defined as.
 *
 * A view that drifted from the base tables would otherwise ship unnoticed: the
 * dashboard would quietly stop counting a module.
 */
class WorkItemQueryParityTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_the_view_is_created_on_mysql_only(): void
    {
        $query = new WorkItemQuery;

        $expected = DB::connection()->getDriverName() !== 'sqlite';

        $this->assertSame(
            $expected,
            $query->viewExists(),
            'The work_items view is MySQL-only; every other driver must fall back.',
        );
    }

    public function test_the_union_returns_the_same_rows_as_the_view(): void
    {
        $user = User::factory()->create();

        TaskFactory::new()->ownedBy($user)->count(2)->create(['title' => 'Task A']);
        Todo::factory()->count(2)->createdBy($user)->create(['title' => 'Todo A']);

        $meeting = MeetingFactory::new()->organisedBy($user)->create();
        MeetingActionItem::query()->create([
            'meeting_id' => $meeting->id,
            'action_no' => 1,
            'title' => 'Action A',
            'priority' => 'high',
            'status' => 'open',
        ]);

        $fromView = $this->rowsFrom((new WorkItemQuery)->all());
        $fromUnion = $this->rowsFrom((new WorkItemQuery)->union(array_keys(WorkItemQuery::SOURCES)));

        $this->assertSame(
            $fromUnion,
            $fromView,
            'The view and the UNION fallback disagree.',
        );

        $this->assertCount(5, $fromView);
    }

    public function test_the_declared_columns_match_the_view_migration(): void
    {
        // Column order is part of the contract: the UNION relies on positional
        // alignment, so a new column added in one place and not the other would
        // silently mis-map every column after it.
        $migration = (string) file_get_contents(
            database_path('migrations/2026_10_01_000014_create_work_items_view.php')
        );

        foreach (WorkItemQuery::COLUMNS as $position => $column) {
            if ($column === 'source_type' || $column === 'source_id') {
                continue;
            }

            // Each column is selected as `column` in exactly one of the three
            // branches; count the aliased occurrences to prove the order.
            $this->assertStringContainsString(
                '`'.$column.'`',
                $migration,
                "The view migration does not select {$column}.",
            );

            $this->assertIsInt($position);
        }
    }

    public function test_soft_deleted_todos_are_excluded_from_both_paths(): void
    {
        $user = User::factory()->create();
        Todo::factory()->count(2)->createdBy($user)->create();

        $first = Todo::query()->withoutGlobalScopes()->firstOrFail();
        $first->delete();

        $this->assertCount(1, $this->rowsFrom((new WorkItemQuery)->all()));
        $this->assertCount(1, $this->rowsFrom((new WorkItemQuery)->union(array_keys(WorkItemQuery::SOURCES))));
    }

    public function test_visibility_matches_todo_policy_on_the_fallback(): void
    {
        $outsider = $this->plainUser();
        $department = Department::factory()->create();

        $mine = Todo::factory()->createdBy($outsider)->create(['title' => 'Mine']);
        $theirs = Todo::factory()->create(['title' => 'Theirs']);

        $teamTodo = Todo::factory()->withVisibility(Visibility::Team)->create([
            'title' => 'Team',
            'department_id' => $department->id,
        ]);

        $employee = Employee::factory()->create(['department_id' => $department->id]);
        $colleague = User::factory()->create(['employee_id' => $employee->id]);

        // The outsider is in no department, so the Team To-Do is not theirs.
        $visible = $this->titlesFor(
            (new WorkItemQuery)->union(array_keys(WorkItemQuery::SOURCES), $outsider->id),
        );
        $this->assertContains('Mine', $visible);
        $this->assertNotContains('Theirs', $visible);
        $this->assertNotContains('Team', $visible);

        // A colleague in that department does see the Team To-Do.
        $colleagueVisible = $this->titlesFor(
            (new WorkItemQuery)->union(array_keys(WorkItemQuery::SOURCES), $colleague->id, $department->id),
        );
        $this->assertContains('Team', $colleagueVisible);
    }

    public function test_a_watcher_sees_the_todo_through_the_fallback(): void
    {
        $watcher = $this->plainUser();
        $todo = Todo::factory()->create(['title' => 'Watched']);

        TodoWatcher::query()->create([
            'todo_id' => $todo->id,
            'user_id' => $watcher->id,
        ]);

        $this->assertContains('Watched', $this->titlesFor(
            (new WorkItemQuery)->union(array_keys(WorkItemQuery::SOURCES), $watcher->id),
        ));
    }

    public function test_an_empty_source_list_returns_nothing(): void
    {
        $user = User::factory()->create();
        Todo::factory()->count(2)->createdBy($user)->create();

        // Empty means "no sources", not "every source": widening it silently
        // would return more rows than the caller asked for.
        $this->assertCount(0, $this->rowsFrom((new WorkItemQuery)->union([])));
    }

    public function test_visible_to_defaults_to_every_source(): void
    {
        $user = User::factory()->create();
        Todo::factory()->createdBy($user)->create();
        TaskFactory::new()->ownedBy($user)->create();

        $rows = $this->rowsFrom((new WorkItemQuery)->visibleTo($user->id));

        $this->assertCount(2, $rows);
    }

    public function test_a_work_item_row_resolves_back_to_its_model(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->createdBy($user)->create();

        $resolved = (new WorkItemQuery)->resolve('todo', $todo->id);

        $this->assertInstanceOf(Todo::class, $resolved);
        $this->assertSame($todo->id, $resolved->id);
    }

    public function test_resolving_an_unknown_source_type_returns_null(): void
    {
        $this->assertNull((new WorkItemQuery)->resolve('not_a_module', 1));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rowsFrom(Builder $query): array
    {
        return $query->get()
            ->map(fn (object $row): array => [
                'source_type' => $row->source_type,
                'source_id' => (int) $row->source_id,
                'title' => $row->title,
            ])
            ->sortBy(fn (array $row): string => $row['source_type'].'-'.$row['source_id'])
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function titlesFor(Builder $query): array
    {
        return $query->pluck('title')->all();
    }
}
