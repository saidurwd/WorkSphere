<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('discussion_id')->nullable()->constrained('meeting_discussions')->cascadeOnDelete();
            $table->foreignId('decision_id')->nullable()->constrained('meeting_decisions')->cascadeOnDelete();
            $table->foreignId('action_item_id')->nullable()->constrained('meeting_action_items')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->unsignedInteger('file_size')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('meeting_id');
            $table->index('discussion_id');
            $table->index('decision_id');
            $table->index('action_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_attachments');
    }
};
