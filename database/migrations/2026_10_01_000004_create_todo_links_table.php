<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * To-Do polymorphic links — DATABASE-ARCHITECTURE.md §4.4.
 *
 * Replaces the six nullable FK columns that a Task↔To-Do relationship would
 * otherwise need. `link_type` is one of related, relates_to, blocks, blocked_by,
 * derived_from.
 *
 * `todo_link_reverse_idx` is the bidirectional-navigation index: given any
 * record, it answers "which To-Dos point at me" without a table scan.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('todo_links')) {
            return;
        }

        Schema::create('todo_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_id')->constrained('todos')->cascadeOnDelete();
            $table->string('linkable_type');
            $table->unsignedBigInteger('linkable_id');
            $table->string('link_type', 30)->default('related');
            $table->timestamps();

            $table->unique(
                ['todo_id', 'linkable_type', 'linkable_id', 'link_type'],
                'todo_link_unique',
            );

            $table->index(['linkable_type', 'linkable_id'], 'todo_link_reverse_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_links');
    }
};
