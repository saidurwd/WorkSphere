<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('event')->default('login');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'attempted_at']);
            $table->index('event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
