<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Todos\Models\Scopes\TodoScope;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoChecklistItem;
use Tests\TestCase;

/**
 * N+1 guard — TODO-MODULE-SPECIFICATION.md §11.
 *
 * The requirement is that the query count does not grow as the comment count
 * rises. A list view that loads one comment collection per row is fine at five
 * comments and unusable at five hundred, and the failure is invisible in a
 * feature test unless the query count is asserted directly.
 */
class TodoNPlusOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_listing_todos_with_comments_does_not_query_per_todo(): void
    {
        $user = User::factory()->create();

        Todo::factory()->count(5)->createdBy($user)->create();

        $this->commentsForEachTodo(1, $user);

        // Two one-off costs land on the first query only and would otherwise look
        // like the query count changing between measurements:
        //   - TodoScope resolves the department through $user->employee;
        //   - TodoScope calls $user->hasPermission('todos.view_all'), which
        //     resolves the whole permission set on first use and caches it.
        // Both are warmed here so the two blocks are measured on equal terms.
        $this->actingAs($user->load('employee'));
        $user->hasPermission('todos.view_all');

        // A resettable counter rather than the global query log: the log keeps
        // whatever was recorded before it was enabled, so the two blocks would
        // not be measured on the same footing.
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $measure = function (int $expectedComments) use (&$queries): int {
            $queries = 0;

            $todos = Todo::query()
                ->withCount('comments')
                ->with(['assignee:id,name', 'watchers'])
                ->get();

            $this->assertSame(
                $expectedComments,
                $todos->sum('comments_count'),
                'Every comment must be counted — otherwise the fixture is not measuring what it claims.',
            );

            return $queries;
        };

        $withFew = $measure(5);

        // Four more comments per todo. The row count changes by 20; the query
        // count must not change at all.
        $this->commentsForEachTodo(4, $user);

        $withMany = $measure(25);

        $this->assertSame(
            $withFew,
            $withMany,
            "Query count grew with the comment count ({$withFew} → {$withMany}). withCount is required, not with().",
        );
    }

    /**
     * `Builder::count()` is the SQL aggregate, not a factory count, so fixtures
     * are built with explicit arrays rather than a fluent count.
     */
    private function commentsForEachTodo(int $perTodo, User $user): void
    {
        $rows = [];

        foreach (Todo::query()->withoutGlobalScope(TodoScope::class)->get() as $todo) {
            for ($i = 0; $i < $perTodo; $i++) {
                $rows[] = [
                    'commentable_type' => Todo::class,
                    'commentable_id' => $todo->id,
                    'user_id' => $user->id,
                    'body' => 'A comment',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Comment::query()->insert($rows);
    }

    private function checklistItems(Todo $todo, int $count, bool $completed): void
    {
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'todo_id' => $todo->id,
                'title' => 'Step '.$i,
                'is_completed' => $completed,
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        TodoChecklistItem::query()->insert($rows);
    }

    public function test_a_todo_with_checklist_progress_does_not_query_per_item(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->createdBy($user)->create();

        $this->checklistItems($todo, 6, true);

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        [$completed, $total, $percent] = $todo->checklistProgress();

        $this->assertSame(6, $total);
        $this->assertSame(6, $completed);
        $this->assertSame(100, $percent);
        $this->assertLessThanOrEqual(2, $queries, 'Progress must not query once per checklist item.');
    }

    public function test_checklist_progress_is_computed_not_stored(): void
    {
        $user = User::factory()->create();
        $todo = Todo::factory()->createdBy($user)->create();

        $this->checklistItems($todo, 1, true);

        $this->checklistItems($todo, 3, false);

        [$completed, $total, $percent] = $todo->checklistProgress();

        $this->assertSame([1, 4, 25], [$completed, $total, $percent]);

        // Nothing is cached on the parent row: there is no column to go stale.
        $this->assertFalse(
            DB::getSchemaBuilder()->hasColumn('todos', 'progress'),
            'Checklist progress must never be stored on the parent.',
        );
    }
}
