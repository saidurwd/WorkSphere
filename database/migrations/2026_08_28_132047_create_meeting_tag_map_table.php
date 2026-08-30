<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_tag_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('meeting_tags')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['meeting_id', 'tag_id']);
            $table->index('meeting_id');
            $table->index('tag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_tag_map');
    }
};
