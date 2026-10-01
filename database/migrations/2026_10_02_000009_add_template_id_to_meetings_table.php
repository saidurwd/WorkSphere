<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Record which template a meeting came from.
 *
 * `meeting_templates` and its agenda items already existed and were entirely
 * unwired — nothing could create a template, and nothing could use one. This
 * column is what makes "scheduled from template" traceable after the fact: without
 * it, a meeting built from a template is indistinguishable from one typed by
 * hand, so a change to the template cannot be reasoned about.
 *
 * `nullOnDelete`: deleting a template must not delete the meetings it produced.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('meetings', 'template_id')) {
            return;
        }

        Schema::table('meetings', function (Blueprint $table): void {
            $table->foreignId('template_id')
                ->nullable()
                ->after('location_id')
                ->constrained('meeting_templates')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('meetings', 'template_id')) {
            return;
        }

        Schema::table('meetings', function (Blueprint $table): void {
            if (Schema::hasForeignKey('meetings', ['template_id'])) {
                $table->dropForeign(['template_id']);
            }
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->dropColumn('template_id');
        });
    }
};
