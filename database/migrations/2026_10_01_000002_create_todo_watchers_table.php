<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * To-Do watchers — DATABASE-ARCHITECTURE.md §4.2.
 *
 * A watcher is a party to the To-Do for visibility purposes only. The composite
 * unique index is what makes "watch this once" idempotent.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('todo_watchers')) {
            return;
        }

        Schema::create('todo_watchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_id')->constrained('todos')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['todo_id', 'user_id'], 'todo_watcher_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_watchers');
    }
};
