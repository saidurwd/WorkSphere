<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('meeting_no')->unique();
            $table->string('title');
            $table->foreignId('meeting_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organizer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('chairperson_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location')->nullable();
            $table->date('meeting_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone')->default('UTC');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'postponed'])->default('scheduled');
            $table->enum('priority', ['normal', 'important', 'urgent'])->default('normal');
            $table->text('description')->nullable();
            $table->text('agenda')->nullable();
            $table->enum('minutes_status', ['draft', 'prepared', 'submitted', 'under_review', 'approved', 'published'])->default('draft');
            $table->foreignId('minutes_prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('minutes_prepared_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('meeting_no');
            $table->index('meeting_date');
            $table->index('meeting_type_id');
            $table->index('organizer_id');
            $table->index('department_id');
            $table->index('status');
            $table->index('minutes_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
