<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Headline time figures on `tasks` — GAP-025.
 *
 * `actual_minutes` is a cache of `SUM(time_entries.minutes)`, recomputed whenever
 * an entry is added or removed. Storing it means the task list can show it
 * without a correlated subquery per row, which is the same reason it must never
 * be written by hand.
 */
return new class extends Migration
{
    public function up(): void
    {
        $columns = [];

        if (! Schema::hasColumn('tasks', 'estimated_minutes')) {
            $columns[] = 'estimated_minutes';
        }

        if (! Schema::hasColumn('tasks', 'actual_minutes')) {
            $columns[] = 'actual_minutes';
        }

        if ($columns === []) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) use ($columns): void {
            foreach ($columns as $column) {
                // 9999 minutes is over a week and comfortably above a single
                // session, while staying inside SMALLINT UNSIGNED (65535).
                $table->unsignedSmallInteger($column)->nullable();
            }
        });
    }

    public function down(): void
    {
        $columns = array_values(array_filter(
            ['estimated_minutes', 'actual_minutes'],
            fn (string $column): bool => Schema::hasColumn('tasks', $column),
        ));

        if ($columns === []) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }
};
