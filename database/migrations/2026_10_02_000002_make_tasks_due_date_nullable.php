<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make `tasks.due_date` nullable — GAP-026.
 *
 * Deliberate, and the opposite of the To-Do decision in Phase 3: a task with no
 * deadline is legitimate ("do this sometime"), and the To-Do spec says so
 * explicitly. The column was `NOT NULL` only because the original create
 * migration did not anticipate it, which forced a placeholder date onto every
 * task that nobody had actually committed to.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tasks') || ! Schema::hasColumn('tasks', 'due_date')) {
            return;
        }

        if ($this->isNullable()) {
            return;
        }

        // `->change()` on a nullable modifier is portable: MySQL drops the
        // NOT NULL, and SQLite's grammar supports the rewrite.
        Schema::table('tasks', function (Blueprint $table): void {
            $table->date('due_date')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tasks') || ! Schema::hasColumn('tasks', 'due_date')) {
            return;
        }

        // Tightening cannot be done without inventing dates, and an invented date
        // is worse than a missing one: it would appear in overdue reports.
        $undated = DB::table('tasks')->whereNull('due_date')->count();

        if ($undated > 0) {
            throw new RuntimeException(
                "Refusing to make tasks.due_date NOT NULL: {$undated} task(s) have no due date. "
                .'Assign them a date first — a fabricated one would show up as overdue.'
            );
        }

        Schema::table('tasks', function (Blueprint $table): void {
            $table->date('due_date')->nullable(false)->change();
        });
    }

    protected function isNullable(): bool
    {
        $column = collect(Schema::getColumns('tasks'))->firstWhere('name', 'due_date');

        return (bool) ($column['nullable'] ?? false);
    }
};
