<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shared attachment table — DATABASE-ARCHITECTURE.md §4.6.
 *
 * The default disk is `local`, which is private. Nothing here may become
 * reachable by a direct URL: downloads go through an authorised controller
 * (Phase 5). `checksum` enables deduplication and integrity verification, and
 * is indexed because that lookup is the only way to find a candidate.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attachments')) {
            return;
        }

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('attachable_type');
            $table->unsignedBigInteger('attachable_id');

            // Private disk by default. A public disk here would make every uploaded
            // file world-readable regardless of the To-Do's visibility.
            $table->string('disk', 40)->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['attachable_type', 'attachable_id'], 'attachment_subject_idx');
            $table->index('checksum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
