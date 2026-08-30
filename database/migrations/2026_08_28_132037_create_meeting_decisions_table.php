<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agenda_id')->nullable()->constrained('meeting_agendas')->nullOnDelete();
            $table->foreignId('discussion_id')->nullable()->constrained('meeting_discussions')->nullOnDelete();
            $table->unsignedInteger('decision_no');
            $table->string('decision_title');
            $table->text('decision_description')->nullable();
            $table->enum('decision_type', ['approved', 'rejected', 'deferred', 'noted', 'further_discussion_required'])->default('approved');
            $table->enum('decision_status', ['active', 'superseded', 'cancelled'])->default('active');
            $table->date('decision_date')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('effective_date')->nullable();
            $table->text('remarks')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('meeting_id');
            $table->index('decision_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_decisions');
    }
};
