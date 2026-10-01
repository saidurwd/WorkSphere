<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notification preferences — DATABASE-ARCHITECTURE.md §4.10.
 *
 * Gives `shouldNotify()` something real to consult. Today that method returns
 * `true` unconditionally (GAP-024), which is why a user cannot switch off a
 * notification type.
 *
 * A user with no row for a (type, channel) pair is treated as opted IN, so the
 * table stays small: only opted-out or channel-specific choices need a row.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('notification_preferences')) {
            return;
        }

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('notification_type', 60);
            $table->string('channel', 20);
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(
                ['user_id', 'notification_type', 'channel'],
                'notif_pref_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
