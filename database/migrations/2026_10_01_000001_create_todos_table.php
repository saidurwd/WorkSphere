<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * To-Do table — DATABASE-ARCHITECTURE.md §4.1.
 *
 * Purely additive. Design points that are deliberate and must not drift:
 *
 * - `due_date` is NULLABLE, unlike `tasks`. An undated To-Do is valid.
 * - `creator_id` is NOT NULL and restricts on delete: a To-Do always has an
 *   author, and history cannot be orphaned by removing that user.
 * - `assignee_id` and `department_id` null on delete: removing a user or a
 *   department must not destroy their work.
 * - `status`, `priority` and `visibility` are `string` + a PHP enum cast.
 *   Never a DB ENUM (DATABASE-ARCHITECTURE §5.1 Option A).
 * - Checklist progress is computed and stored nowhere.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('todos')) {
            return;
        }

        Schema::create('todos', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description')->nullable();

            $table->string('status', 20)->default('inbox');
            $table->string('priority', 20)->default('medium');
            $table->string('visibility', 20)->default('personal');

            $table->foreignId('assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('creator_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();

            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->time('due_time')->nullable();

            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->unsignedSmallInteger('actual_minutes')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('archived_from', 20)->nullable();
            $table->string('waiting_on')->nullable();

            $table->string('color', 20)->nullable();
            $table->unsignedInteger('sort_order')->default(0);

            $table->json('recurrence_rule')->nullable();
            $table->date('previous_occurrence_at')->nullable();
            $table->timestamp('last_reminded_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['assignee_id', 'status', 'due_date'], 'todos_assignee_status_due_idx');
            $table->index(['creator_id', 'status'], 'todos_creator_status_idx');
            $table->index(['status', 'due_date', 'deleted_at'], 'todos_status_due_soft_idx');
            $table->index(['department_id', 'status'], 'todos_department_status_idx');
            $table->index('last_reminded_at');
        });

        $this->createFullTextIndex();
    }

    public function down(): void
    {
        Schema::dropIfExists('todos');
    }

    /**
     * Laravel's schema builder cannot emit a FULLTEXT index on MySQL or SQLite —
     * both throw from `compileFulltext`. MySQL and MariaDB both support the
     * syntax, so it is issued directly and skipped on SQLite, where the search
     * path falls back to LIKE (Phase 9).
     */
    protected function createFullTextIndex(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasIndex('todos', 'todos_fulltext_idx')) {
            return;
        }

        DB::statement('ALTER TABLE `todos` ADD FULLTEXT INDEX `todos_fulltext_idx` (`title`, `description`)');
    }
};
