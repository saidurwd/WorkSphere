<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Full-text indexes for global search — GAP-035.
 *
 * MySQL/MariaDB only. Laravel's schema builder cannot emit a FULLTEXT index
 * (`Grammar::compileFulltext` throws), so the statement is issued directly, and
 * it is skipped entirely on SQLite — where the search service falls back to LIKE
 * so the test suite exercises the same code path rather than a stub.
 *
 * Each index covers the columns the search actually reads, and nothing more: an
 * index over a column that is not searched is write amplification with no payoff.
 *
 * `task_projects.name` is indexed because a project is a search target in its own
 * right, not only as a filter on tasks.
 *
 * No existing column is altered and no index is dropped: purely additive.
 */
return new class extends Migration
{
    /**
     * table => [index name, [columns]]
     *
     * @var array<string, array{string, list<string>}>
     */
    private array $indexes = [
        'tasks' => ['tasks_fulltext_idx', ['title', 'description']],
        'todos' => ['todos_fulltext_idx', ['title', 'description']],
        'meetings' => ['meetings_fulltext_idx', ['title', 'description']],
        'obligations' => ['obligations_fulltext_idx', ['title', 'description']],
        'task_projects' => ['task_projects_fulltext_idx', ['name', 'description']],
    ];

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->indexes as $table => [$name, $columns]) {
            if (! Schema::hasTable($table) || Schema::hasIndex($table, $name)) {
                continue;
            }

            $columnList = implode(', ', array_map(
                fn (string $column): string => '`'.$column.'`',
                $columns,
            ));

            DB::statement("ALTER TABLE `{$table}` ADD FULLTEXT INDEX `{$name}` ({$columnList})");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach ($this->indexes as $table => [$name, $columns]) {
            if (! Schema::hasTable($table) || ! Schema::hasIndex($table, $name)) {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$name}`");
        }
    }
};
