<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_discussions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agenda_id')->constrained('meeting_agendas')->cascadeOnDelete();
            $table->string('topic')->nullable();
            $table->text('discussion')->nullable();
            $table->text('key_points')->nullable();
            $table->foreignId('discussion_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('meeting_id');
            $table->index('agenda_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_discussions');
    }
};
