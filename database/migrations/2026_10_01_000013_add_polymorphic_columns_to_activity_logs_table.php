<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Polymorphic subjects on `activity_logs` — DATABASE-ARCHITECTURE.md §4.12.
 *
 * `activity_logs` already carries `module_name` + `record_id`. Those stay for
 * backward compatibility during the migration window and are dropped in Phase 15;
 * `subject_type` + `subject_id` is the shape every consumer should read from now
 * on, so the two per-module log tables can be retired.
 *
 * Purely additive — no existing column is altered or removed.
 */
return new class extends Migration
{
    /**
     * @var array<string, list<string>>
     */
    protected array $indexes = [
        'activity_subject_idx' => ['subject_type', 'subject_id'],
        'activity_legacy_idx' => ['module_name', 'record_id'],
        'activity_action_idx' => ['action', 'created_at'],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('activity_logs', 'subject_type')) {
                $table->string('subject_type')->nullable();
            }

            if (! Schema::hasColumn('activity_logs', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable();
            }

            if (! Schema::hasColumn('activity_logs', 'user_agent')) {
                $table->string('user_agent')->nullable();
            }
        });

        foreach ($this->indexes as $name => $columns) {
            if (Schema::hasIndex('activity_logs', $name)) {
                continue;
            }

            Schema::table('activity_logs', function (Blueprint $table) use ($columns, $name) {
                $table->index($columns, $name);
            });
        }

        // Phase 2's index migration already put an index on `created_at` under a
        // different name, so check for the leading column rather than the name.
        if (! $this->leadingColumnAlreadyIndexed('activity_logs', ['created_at'])) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->index('created_at');
            });
        }
    }

    /**
     * @param  list<string>  $columns
     */
    protected function leadingColumnAlreadyIndexed(string $table, array $columns): bool
    {
        if (count($columns) !== 1) {
            return false;
        }

        foreach (Schema::getIndexes($table) as $index) {
            if (($index['columns'][0] ?? null) === $columns[0]) {
                return true;
            }
        }

        return false;
    }

    public function down(): void
    {
        if (! Schema::hasTable('activity_logs')) {
            return;
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            if (! $this->leadingColumnAlreadyIndexed('activity_logs', ['created_at'])
                && Schema::hasIndex('activity_logs', 'created_at')) {
                $table->dropIndex('created_at');
            }
        });

        foreach (array_keys($this->indexes) as $name) {
            if (! Schema::hasIndex('activity_logs', $name)) {
                continue;
            }

            Schema::table('activity_logs', function (Blueprint $table) use ($name) {
                $table->dropIndex($name);
            });
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            $columns = [];

            foreach (['user_agent', 'subject_id', 'subject_type'] as $column) {
                if (Schema::hasColumn('activity_logs', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
