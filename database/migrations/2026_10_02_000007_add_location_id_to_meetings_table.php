<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add `meetings.location_id` — GAP-030.
 *
 * `location` stays and remains the fallback: it is free text, it is what every
 * existing row has, and "Room 3 / Annexe" is not always a row in `locations`.
 * The FK is the structured option for the places that *are* known locations, not
 * a replacement.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('meetings', 'location_id')) {
            return;
        }

        Schema::table('meetings', function (Blueprint $table): void {
            // setNull, not cascade: a location row being deleted must not delete
            // the meetings that happened there. The free-text `location` is
            // untouched, so the name is never lost.
            $table->foreignId('location_id')
                ->nullable()
                ->after('location')
                ->constrained('locations')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('meetings', 'location_id')) {
            return;
        }

        // FK -> column, for the same reason Phase 2's migrations needed it: MySQL
        // will not drop an index a live foreign key depends on.
        Schema::table('meetings', function (Blueprint $table): void {
            if (Schema::hasForeignKey('meetings', ['location_id'])) {
                $table->dropForeign(['location_id']);
            }
        });

        Schema::table('meetings', function (Blueprint $table): void {
            $table->dropColumn('location_id');
        });
    }
};
