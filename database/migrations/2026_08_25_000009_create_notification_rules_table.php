<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_rules')) {
            Schema::create('notification_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('obligation_type_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('days_before_expiry');
                $table->string('notification_level');
                $table->string('recipient_type');
                $table->string('channel')->default('IN_APP');
                $table->string('subject_template')->nullable();
                $table->string('message_template')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
