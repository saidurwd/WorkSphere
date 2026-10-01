<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shared reminders — DATABASE-ARCHITECTURE.md §4.8.
 *
 * Two indexes carry the whole design:
 *
 * - `reminder_unique` on (subject_type, subject_id, remind_at) makes creating a
 *   duplicate reminder impossible, so the create path is idempotent.
 * - `reminder_dispatch_idx` on (status, remind_at) is the scheduler's only hot
 *   query. Everything else about the dispatcher reads through these two.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('reminders')) {
            return;
        }

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id');
            $table->timestamp('remind_at');
            $table->string('channel', 20)->default('in_app');
            $table->string('status', 20)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['subject_type', 'subject_id', 'remind_at'], 'reminder_unique');
            $table->index(['status', 'remind_at'], 'reminder_dispatch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
