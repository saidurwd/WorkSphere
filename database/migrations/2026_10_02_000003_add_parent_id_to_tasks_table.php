<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sub-task support — GAP-025.
 *
 * `parent_id` is a self-referencing nullable FK. It nulls on delete rather than
 * cascading: deleting a parent should orphan its children (they are still real
 * work), not delete them along with it.
 *
 * Cycles are prevented in the application layer, not here. A database-level
 * cycle check on a self-referencing FK is not expressible portably, and a
 * partial unique index cannot prevent A->B->A. `Task::wouldCreateCycle()` is the
 * single enforcement point and is covered by a test.
 *
 * (`parent_id` deliberately avoids the name `task_id`, which Phase 7's
 * `todo_links` test asserts must not appear on `tasks` for To-Do linkage.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('tasks', 'parent_id')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('task_no')
                ->constrained('tasks')
                ->nullOnDelete();

            $table->index(['parent_id', 'status'], 'tasks_parent_status_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('tasks', 'parent_id')) {
            return;
        }

        // FK -> index -> column. MySQL refuses to drop an index a live foreign key
        // depends on; SQLite refuses to drop a column a live index covers. This is
        // the only order both accept.
        Schema::table('tasks', function (Blueprint $table): void {
            if (Schema::hasForeignKey('tasks', ['parent_id'])) {
                $table->dropForeign(['parent_id']);
            }
        });

        Schema::table('tasks', function (Blueprint $table): void {
            if (Schema::hasIndex('tasks', 'tasks_parent_status_idx')) {
                $table->dropIndex('tasks_parent_status_idx');
            }
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn('parent_id');
        });
    }
};
