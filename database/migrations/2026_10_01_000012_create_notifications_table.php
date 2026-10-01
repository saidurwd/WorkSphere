<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel notifications table — DATABASE-ARCHITECTURE.md §4.11.
 *
 * UUID primary key, matching Laravel's `Notifiable` contract, which generates
 * UUIDs for notification ids. The composite index serves the navbar's
 * unread-count query on every page render — the query this table exists to
 * replace, which today fans out across three separate log tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_unread_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
