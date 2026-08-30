<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('obligation_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('notification_rule_id')->nullable()->constrained()->nullOnDelete();
                $table->string('channel');
                $table->string('notification_type');
                $table->timestamp('scheduled_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->string('status')->default('PENDING');
                $table->string('subject')->nullable();
                $table->text('message')->nullable();
                $table->unsignedInteger('retry_count')->default(0);
                $table->text('error_message')->nullable();
                $table->string('provider_message_id')->nullable();
                $table->timestamps();

                $table->index('obligation_id');
                $table->index('user_id');
                $table->index('notification_rule_id');
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
