<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * To-Do checklist items — DATABASE-ARCHITECTURE.md §4.3.
 *
 * Progress is computed from `is_completed` at read time and is deliberately not
 * stored: a cached percentage on the parent row goes stale the moment any item
 * is toggled, and nothing would update it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('todo_checklist_items')) {
            return;
        }

        Schema::create('todo_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_id')->constrained('todos')->cascadeOnDelete();
            $table->string('title');
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['todo_id', 'sort_order'], 'todo_checklist_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_checklist_items');
    }
};
