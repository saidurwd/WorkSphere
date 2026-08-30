<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('participant_type', ['organizer', 'chairperson', 'member', 'guest', 'presenter', 'observer'])->default('member');
            $table->enum('attendance_status', ['invited', 'accepted', 'declined', 'present', 'absent', 'apology'])->default('invited');
            $table->dateTime('invited_at')->nullable();
            $table->dateTime('responded_at')->nullable();
            $table->dateTime('joined_at')->nullable();
            $table->dateTime('left_at')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['meeting_id', 'user_id']);
            $table->index('meeting_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_participants');
    }
};
