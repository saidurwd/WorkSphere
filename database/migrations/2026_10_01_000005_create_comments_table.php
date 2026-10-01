<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shared comment table — DATABASE-ARCHITECTURE.md §4.5.
 *
 * One polymorphic table for every module. Meetings currently write to
 * `meeting_discussions` and Tasks to `task_remarks`; those become dual-write
 * targets and are dropped in Phase 15. Creating a second comment table per
 * module is what §2.4 of the architecture document calls duplication.
 *
 * `parent_id` is intentionally a plain unsignedBigInteger with no foreign key:
 * a soft-deleted parent comment must still be renderable in its thread, and a
 * hard FK would either block the delete or cascade the replies away.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('comments')) {
            return;
        }

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('body');
            $table->json('mentions')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(
                ['commentable_type', 'commentable_id', 'created_at'],
                'comment_subject_idx',
            );

            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
