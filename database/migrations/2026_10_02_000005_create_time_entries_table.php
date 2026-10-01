<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task time tracking — GAP-025.
 *
 * `time_entries` is a log, not a counter: `tasks.estimated_minutes` and
 * `tasks.actual_minutes` are the headline figures, and this table is where the
 * minutes behind them come from. Keeping the log means an estimate can be revised
 * without losing the history of what was actually done.
 *
 * `logged_on` is a business date rather than a timestamp: a question is asked
 * every morning ("what did I spend yesterday"), and grouping by local calendar day
 * is the whole point.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('time_entries')) {
            return;
        }

        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('minutes');
            $table->date('logged_on');
            $table->string('note')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'logged_on'], 'time_entry_task_date_idx');
            $table->index(['user_id', 'logged_on'], 'time_entry_user_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
