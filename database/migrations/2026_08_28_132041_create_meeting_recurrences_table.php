<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_recurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->enum('recurrence_type', ['daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'yearly', 'custom'])->default('weekly');
            $table->unsignedInteger('recurrence_interval')->default(1);
            $table->string('day_of_week')->nullable();
            $table->unsignedInteger('day_of_month')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('occurrences')->nullable();
            $table->dateTime('next_occurrence')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_recurrences');
    }
};
