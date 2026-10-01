<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Database\Connection as DbConnection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Tasks\Models\Task;
use Modules\Todos\Models\Todo;

/**
 * The unified work-item query — DATABASE-ARCHITECTURE.md §4.13.
 *
 * Two implementations behind one interface:
 *
 * - **MySQL/MariaDB** reads the `work_items` VIEW, which UNIONs Tasks, To-Dos and
 *   meeting action items in the database.
 * - **SQLite** has no such view, so the same three-way UNION is built in PHP.
 *
 * The fallback exists so the test suite exercises the real query rather than a
 * stub. A `if (testing) return collect()` shortcut would leave the production
 * path untested, which is exactly how a view that drifted from the base tables
 * would ship. `WorkItemQueryParityTest` forces each path and compares the rows.
 *
 * READ-ONLY by contract: never written to, never joined to for a mutation, never
 * used as a foreign-key target. A view has no index and no constraint, so any
 * write through it is a performance and integrity trap.
 */
class WorkItemQuery
{
    /**
     * The columns both paths produce, in order.
     *
     * @var list<string>
     */
    public const COLUMNS = [
        'source_type',
        'source_id',
        'title',
        'status',
        'priority',
        'assignee_id',
        'creator_id',
        'due_date',
        'completed_at',
        'project_id',
        'deleted_at',
    ];

    /**
     * The base table behind each `source_type`.
     *
     * @var array<string, class-string>
     */
    public const SOURCES = [
        'task' => Task::class,
        'todo' => Todo::class,
        'meeting_action_item' => MeetingActionItem::class,
    ];

    /**
     * The default is the application's configured connection, not a hardcoded
     * `mysql`: production runs MySQL, the test suite runs SQLite, and a view that
     * only exists on one of them must be detected rather than assumed.
     */
    public function __construct(private readonly ?string $connection = null) {}

    /**
     * Whether the `work_items` view is actually available.
     *
     * Checked rather than inferred from the driver: a database that has not been
     * migrated, or one where the view migration was skipped, must fall back rather
     * than fail with a missing-table error.
     */
    public function viewExists(): bool
    {
        if ($this->driver() === 'sqlite') {
            return false;
        }

        try {
            return Schema::connection($this->connection ?? config('database.default'))
                ->hasTable('work_items');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * All work items, before any permission filter.
     */
    public function all(): Builder
    {
        return $this->viewExists()
            ? $this->connection()->table('work_items')
            : $this->union(array_keys(self::SOURCES));
    }

    /**
     * Work items visible to a user.
     *
     * The filter follows each module's own rule rather than one shared predicate:
     * a single predicate would either over-grant (showing work the user cannot
     * open) or under-grant.
     *
     * @param  list<string>  $sourceTypes  empty means every source
     */
    public function visibleTo(int $userId, array $sourceTypes = [], ?int $departmentId = null): Builder
    {
        $sources = $sourceTypes ?: array_keys(self::SOURCES);
        $connection = $this->connection();

        if ($this->viewExists()) {
            // The view already maps responsible_user_id/assigned_to onto
            // `assignee_id` and user_id/created_by onto `creator_id`, so the
            // party-based rules are one predicate. Team visibility is the
            // exception: `visibility` and `department_id` are To-Do-only columns
            // the view does not carry, so that rule needs its own subquery.
            return $this->all()
                ->whereIn('source_type', $sources)
                ->where(function (Builder $outer) use ($connection, $userId, $departmentId, $sources): void {
                    $outer->where('assignee_id', $userId)
                        ->orWhere('creator_id', $userId);

                    if (in_array('todo', $sources, true)) {
                        $outer->orWhere(function (Builder $todo) use ($connection, $userId, $departmentId): void {
                            $todo->where('source_type', 'todo')
                                ->whereIn('source_id', $this->visibleTodoIds($connection, $userId, $departmentId));
                        });
                    }

                    if (in_array('meeting_action_item', $sources, true)) {
                        $outer->orWhere(function (Builder $action) use ($connection, $userId): void {
                            $action->where('source_type', 'meeting_action_item')
                                ->whereIn('source_id', $connection->table('meeting_action_items')
                                    ->select('id')
                                    ->whereIn('meeting_id', $connection->table('meetings')
                                        ->select('id')
                                        ->where('organizer_id', $userId)));
                        });
                    }
                });
        }

        return $this->union($sources, $userId, $departmentId);
    }

    /**
     * The three-way UNION, mirroring §4.13 exactly.
     *
     * `tasks` has no `deleted_at` (Task does not soft delete) and
     * `meeting_action_items` has no `project_id`, so those are literal NULLs —
     * the same substitutions the view migration makes.
     *
     * @param  list<string>  $sources
     */
    public function union(array $sources = [], ?int $userId = null, ?int $departmentId = null): Builder
    {
        $connection = $this->connection();

        // An explicit empty list means "no sources", not "every source". Callers
        // that want everything pass array_keys(self::SOURCES) — see visibleTo(),
        // which is where the empty-means-all convenience lives. Silently widening
        // an empty filter here would return more rows than the caller asked for.
        $branches = [];

        if (in_array('task', $sources, true)) {
            $query = $connection->table('tasks')
                ->selectRaw("'task' AS source_type, id AS source_id, title, status, priority, responsible_user_id AS assignee_id, user_id AS creator_id, due_date, completed_at, project_id, NULL AS deleted_at");

            if ($userId !== null) {
                $query->where(function (Builder $inner) use ($userId): void {
                    $inner->where('responsible_user_id', $userId)->orWhere('user_id', $userId);
                });
            }

            $branches[] = $query;
        }

        if (in_array('todo', $sources, true)) {
            $query = $connection->table('todos')
                ->selectRaw("'todo' AS source_type, id AS source_id, title, status, priority, assignee_id, creator_id, due_date, completed_at, NULL AS project_id, deleted_at")
                ->whereNull('deleted_at');

            if ($userId !== null) {
                $query->whereIn('id', $this->visibleTodoIds($connection, $userId, $departmentId));
            }

            $branches[] = $query;
        }

        if (in_array('meeting_action_item', $sources, true)) {
            $query = $connection->table('meeting_action_items')
                ->selectRaw("'meeting_action_item' AS source_type, id AS source_id, title, status, priority, assigned_to AS assignee_id, created_by AS creator_id, due_date, completed_at, NULL AS project_id, NULL AS deleted_at");

            if ($userId !== null) {
                $query->where(function (Builder $inner) use ($connection, $userId): void {
                    $inner->where('assigned_to', $userId)
                        ->orWhereIn('meeting_id', $connection->table('meetings')
                            ->select('id')
                            ->where('organizer_id', $userId));
                });
            }

            $branches[] = $query;
        }

        if ($branches === []) {
            // No sources selected: an always-false query rather than an empty one,
            // so a caller cannot accidentally read it as "everything".
            return $connection->table('tasks')
                ->select(array_map(
                    fn (string $column): string => 'NULL AS '.$column,
                    self::COLUMNS,
                ))
                ->whereRaw('1 = 0');
        }

        $union = array_shift($branches);

        foreach ($branches as $branch) {
            $union = $union->unionAll($branch);
        }

        return $union;
    }

    /**
     * Resolve a work-item row back to its model.
     *
     * `withoutGlobalScopes()` on purpose: the caller has already established that
     * this row is visible, and re-applying the To-Do scope here would apply
     * `auth()->user()`, which is the console's user (nobody) during a scheduled
     * report.
     *
     * @return Model|null
     */
    public function resolve(string $sourceType, int $sourceId)
    {
        $class = self::SOURCES[$sourceType] ?? null;

        if ($class === null) {
            return null;
        }

        return $class::query()->withoutGlobalScopes()->find($sourceId);
    }

    /**
     * To-Do ids this user may see — the SQL form of `TodoPolicy::view`.
     */
    protected function visibleTodoIds(DbConnection $connection, int $userId, ?int $departmentId): Builder
    {
        $query = $connection->table('todos')
            ->select('id')
            ->whereNull('deleted_at')
            ->where(function (Builder $inner) use ($connection, $userId, $departmentId): void {
                $inner->where('assignee_id', $userId)
                    ->orWhere('creator_id', $userId)
                    ->orWhereIn('id', $connection->table('todo_watchers')
                        ->select('todo_id')
                        ->where('user_id', $userId));

                if ($departmentId !== null) {
                    $inner->orWhere(function (Builder $team) use ($departmentId): void {
                        $team->where('visibility', 'team')
                            ->where('department_id', $departmentId);
                    });
                }
            });

        return $query;
    }

    /**
     * The connection's query builder.
     */
    protected function connection(): Connection
    {
        return DB::connection($this->connection);
    }

    protected function driver(): string
    {
        return $this->connection()->getDriverName();
    }
}
