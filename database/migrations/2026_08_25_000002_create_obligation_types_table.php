<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('obligation_types')) {
            Schema::create('obligation_types', function (Blueprint $table) {
                $table->id();
                $table->string('type_name')->unique();
                $table->text('description')->nullable();
                $table->json('default_reminder_days')->nullable();
                $table->string('default_priority')->default('medium');
                $table->string('default_recurrence_type')->nullable();
                $table->integer('default_recurrence_interval')->nullable();
                $table->string('default_risk_level')->default('medium');
                $table->boolean('approval_required')->default(false);
                $table->boolean('renewal_required')->default(true);
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('obligation_types');
    }
};
