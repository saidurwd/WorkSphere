<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * DATABASE-ARCHITECTURE.md §4.1-§4.10, asserted against the live schema.
 *
 * These indexes are not decoration: `reminder_dispatch_idx` is the scheduler's
 * only hot query, `dedupe_key` is what makes a re-fired cron safe, and
 * `todo_link_reverse_idx` is what makes bidirectional navigation a lookup rather
 * than a scan. A silently dropped index turns into a slow table at a volume
 * nobody notices until it is expensive, so each one is pinned here.
 */
class TodoSchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * table => index name => columns, exactly as §4.1-§4.10 specifies them.
     *
     * @var array<string, array<string, list<string>>>
     */
    private function expectedIndexes(): array
    {
        return [
            'todos' => [
                'todos_assignee_status_due_idx' => ['assignee_id', 'status', 'due_date'],
                'todos_creator_status_idx' => ['creator_id', 'status'],
                'todos_status_due_soft_idx' => ['status', 'due_date', 'deleted_at'],
                'todos_department_status_idx' => ['department_id', 'status'],
                'todos_last_reminded_at_index' => ['last_reminded_at'],
            ],
            'todo_watchers' => [
                'todo_watcher_unique' => ['todo_id', 'user_id'],
            ],
            'todo_checklist_items' => [
                'todo_checklist_order_idx' => ['todo_id', 'sort_order'],
            ],
            'todo_links' => [
                'todo_link_unique' => ['todo_id', 'linkable_type', 'linkable_id', 'link_type'],
                'todo_link_reverse_idx' => ['linkable_type', 'linkable_id'],
            ],
            'comments' => [
                'comment_subject_idx' => ['commentable_type', 'commentable_id', 'created_at'],
                'comments_parent_id_index' => ['parent_id'],
            ],
            'attachments' => [
                'attachment_subject_idx' => ['attachable_type', 'attachable_id'],
                'attachments_checksum_index' => ['checksum'],
            ],
            'tags' => [
                'tags_name_unique' => ['name'],
                'tags_slug_unique' => ['slug'],
            ],
            'taggables' => [
                'taggable_unique' => ['tag_id', 'taggable_type', 'taggable_id'],
                'taggable_subject_idx' => ['taggable_type', 'taggable_id'],
            ],
            'reminders' => [
                'reminder_unique' => ['subject_type', 'subject_id', 'remind_at'],
                'reminder_dispatch_idx' => ['status', 'remind_at'],
            ],
            'notification_logs' => [
                'notiflog_subject_idx' => ['subject_type', 'subject_id'],
                'notiflog_dispatch_idx' => ['status', 'scheduled_at'],
                'notiflog_user_idx' => ['user_id', 'created_at'],
            ],
            'notification_preferences' => [
                'notif_pref_unique' => ['user_id', 'notification_type', 'channel'],
            ],
            'notifications' => [
                'notifications_unread_index' => ['notifiable_type', 'notifiable_id', 'read_at'],
            ],
            'activity_logs' => [
                'activity_subject_idx' => ['subject_type', 'subject_id'],
                'activity_legacy_idx' => ['module_name', 'record_id'],
                'activity_action_idx' => ['action', 'created_at'],
            ],
        ];
    }

    public function test_every_specified_index_exists_on_the_right_columns(): void
    {
        $problems = [];

        foreach ($this->expectedIndexes() as $table => $indexes) {
            if (! Schema::hasTable($table)) {
                $problems[] = "table {$table} is missing";

                continue;
            }

            $actual = [];

            foreach (Schema::getIndexes($table) as $index) {
                $actual[$index['name']] = $index['columns'];
            }

            foreach ($indexes as $name => $columns) {
                if (! isset($actual[$name])) {
                    $problems[] = "{$table}.{$name} is missing";

                    continue;
                }

                if ($actual[$name] !== $columns) {
                    $problems[] = sprintf(
                        '%s.%s covers [%s] but the spec says [%s]',
                        $table,
                        $name,
                        implode(',', $actual[$name]),
                        implode(',', $columns),
                    );
                }
            }
        }

        $this->assertSame([], $problems, implode("\n", $problems));
    }

    public function test_the_dedupe_key_is_unique(): void
    {
        $indexes = collect(Schema::getIndexes('notification_logs'))
            ->firstWhere('columns', ['dedupe_key']);

        $this->assertNotNull($indexes, 'notification_logs.dedupe_key has no index.');
        $this->assertTrue($indexes['unique'], 'dedupe_key must be UNIQUE — it is what makes a re-fired cron safe.');
    }

    public function test_a_duplicate_dedupe_key_is_rejected_by_the_database(): void
    {
        $user = User::factory()->create();

        $attributes = [
            'subject_type' => 'Modules\\Todos\\Models\\Todo',
            'subject_id' => 1,
            'channel' => 'mail',
            'notification_type' => 'todo.overdue',
            'dedupe_key' => 'todo.overdue:1:2026-10-01',
        ];

        \DB::table('notification_logs')->insert($attributes + ['user_id' => $user->id]);

        $this->expectException(UniqueConstraintViolationException::class);

        \DB::table('notification_logs')->insert($attributes + ['user_id' => $user->id]);
    }

    public function test_multiple_null_dedupe_keys_are_allowed(): void
    {
        // Legacy rows have no dedupe_key. A unique index must not collapse them.
        $attributes = [
            'subject_type' => 'Modules\\Todos\\Models\\Todo',
            'subject_id' => 1,
            'channel' => 'mail',
            'notification_type' => 'todo.overdue',
        ];

        \DB::table('notification_logs')->insert($attributes);
        \DB::table('notification_logs')->insert($attributes);

        $this->assertSame(
            2,
            \DB::table('notification_logs')->whereNull('dedupe_key')->count()
        );
    }

    public function test_attachments_default_to_the_private_local_disk(): void
    {
        // SQLite reports the default wrapped in quotes; strip both forms so the
        // assertion is about the value, not the driver's formatting.
        $default = trim((string) $this->columnDefault('attachments', 'disk'), '\'"');

        $this->assertSame('local', $default);
    }

    public function test_every_phase_three_table_exists(): void
    {
        foreach ([
            'todos',
            'todo_watchers',
            'todo_checklist_items',
            'todo_links',
            'comments',
            'attachments',
            'tags',
            'taggables',
            'reminders',
            'notification_preferences',
            'notifications',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "{$table} is missing.");
        }
    }

    public function test_notification_logs_is_capable_of_serving_every_module(): void
    {
        // It existed already as the Obligations log with a mandatory
        // `obligation_id`. Consolidation means the polymorphic pair must also
        // exist, and both must be optional so no writer is forced into one shape.
        $this->assertTrue(Schema::hasColumn('notification_logs', 'subject_type'));
        $this->assertTrue(Schema::hasColumn('notification_logs', 'subject_id'));

        // `obligation_id` was NOT NULL, which made the table unusable for any
        // other module. Consolidation is only real once it is optional.
        $obligation = collect(Schema::getColumns('notification_logs'))
            ->firstWhere('name', 'obligation_id');

        $this->assertNotNull($obligation);
        $this->assertTrue($obligation['nullable'], 'notification_logs.obligation_id must be nullable to serve other modules.');

        foreach (['subject_type', 'subject_id', 'dedupe_key'] as $column) {
            $nullable = collect(Schema::getColumns('notification_logs'))
                ->firstWhere('name', $column)['nullable'];

            $this->assertTrue($nullable, "notification_logs.{$column} must be nullable.");
        }
    }

    public function test_creator_id_is_required_and_assignee_id_is_optional(): void
    {
        $columns = collect(Schema::getColumns('todos'))->keyBy('name');

        $this->assertFalse($columns['creator_id']['nullable'], 'A To-Do must always have an author.');
        $this->assertTrue($columns['assignee_id']['nullable'], 'An unassigned To-Do is valid.');
        $this->assertTrue($columns['due_date']['nullable'], 'An undated To-Do is valid — unlike tasks.');
        $this->assertTrue(Schema::hasColumn('todos', 'deleted_at'));
    }

    public function test_no_phase_three_column_uses_a_database_enum(): void
    {
        // status / priority / visibility are string + a PHP enum cast. A DB ENUM
        // here would be unportable and would make adding a value a schema change.
        foreach (['todos.status', 'todos.priority', 'todos.visibility'] as $column) {
            [$table, $name] = explode('.', $column);

            $type = strtolower(collect(Schema::getColumns($table))->firstWhere('name', $name)['type']);

            $this->assertStringNotContainsString('enum', $type, "{$column} must not be a DB ENUM.");
        }
    }

    public function test_the_work_items_view_is_created_on_mysql_only(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->assertFalse(
                Schema::hasTable('work_items'),
                'The view is MySQL-only; the query layer must fall back to a UNION on SQLite.'
            );

            return;
        }

        $this->assertTrue(Schema::hasTable('work_items'), 'work_items view is missing on MySQL.');

        $columns = array_map(
            fn (string $name): string => $name,
            DB::selectOne(
                'SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY ORDINAL_POSITION) AS cols
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                ['work_items'],
            )->cols ? explode(',', DB::selectOne(
                'SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY ORDINAL_POSITION) AS cols
                 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
                ['work_items'],
            )->cols) : []
        );

        $this->assertSame([
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
        ], $columns);
    }

    private function columnDefault(string $table, string $column): ?string
    {
        $definition = collect(Schema::getColumns($table))->firstWhere('name', $column);

        return $definition['default'] ?? null;
    }
}
