<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_action_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agenda_id')->nullable()->constrained('meeting_agendas')->nullOnDelete();
            $table->foreignId('discussion_id')->nullable()->constrained('meeting_discussions')->nullOnDelete();
            $table->foreignId('decision_id')->nullable()->constrained('meeting_decisions')->nullOnDelete();
            $table->unsignedInteger('action_no');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('status', ['open', 'in_progress', 'on_hold', 'completed', 'cancelled'])->default('open');
            $table->unsignedInteger('completion_percentage')->default(0);
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('meeting_id');
            $table->index('assigned_to');
            $table->index('assigned_department_id');
            $table->index('due_date');
            $table->index('status');
            $table->index('task_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_action_items');
    }
};
