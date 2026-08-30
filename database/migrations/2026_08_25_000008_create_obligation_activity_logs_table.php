<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('obligation_activity_logs')) {
            Schema::create('obligation_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('obligation_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('action');
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->text('remarks')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('user_agent')->nullable();
                $table->timestamps();

                $table->index('obligation_id');
                $table->index('user_id');
                $table->index('action');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('obligation_activity_logs');
    }
};
