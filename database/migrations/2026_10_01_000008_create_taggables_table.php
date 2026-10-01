<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Polymorphic tag join — DATABASE-ARCHITECTURE.md §4.7.
 *
 * One row per (tag, subject). The composite unique index makes attaching an
 * existing tag idempotent, which matters because the UI attaches the same tag
 * from several screens.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('taggables')) {
            return;
        }

        Schema::create('taggables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag_id')->constrained('tags')->cascadeOnDelete();
            $table->string('taggable_type');
            $table->unsignedBigInteger('taggable_id');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tag_id', 'taggable_type', 'taggable_id'], 'taggable_unique');
            $table->index(['taggable_type', 'taggable_id'], 'taggable_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
