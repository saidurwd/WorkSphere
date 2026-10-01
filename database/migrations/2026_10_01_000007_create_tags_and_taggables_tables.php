<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shared tags — DATABASE-ARCHITECTURE.md §4.7.
 *
 * `tags` is the vocabulary, `taggables` the polymorphic join. The existing
 * `meeting_tags` / `meeting_tag_map` pair is dual-written onto these in Phase 7
 * and migrated in Phase 8; nothing is moved here.
 *
 * Split into two migrations by concern — tags is vocabulary, taggables is a join
 * table — so either can be rolled back without discarding the other.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tags')) {
            return;
        }

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('color', 20)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
