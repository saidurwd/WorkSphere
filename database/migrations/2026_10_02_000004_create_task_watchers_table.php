<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task watchers — GAP-025.
 *
 * Mirrors `todo_watchers` exactly, including the composite unique index: the same
 * two modules now use the same idea, and the same guarantee that watching twice
 * is impossible.
 *
 * A watcher is a visibility and notification party only. They gain no update or
 * delete rights over the task.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('task_watchers')) {
            return;
        }

        Schema::create('task_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'user_id'], 'task_watcher_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_watchers');
    }
};
