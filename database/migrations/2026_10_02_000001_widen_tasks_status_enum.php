<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Widen `tasks.status` to match `meeting_action_items` — GAP-026.
 *
 * Adds `on_hold` and `cancelled` so a task can express the same states a meeting
 * action item can. Purely additive: the three existing values keep their meaning
 * and every existing row stays valid, so there is no value to remap.
 *
 * Laravel cannot ALTER a MySQL ENUM through the schema builder, and the
 * `tasks.status` column is one, so the statement is issued directly and skipped
 * on SQLite — where the column is already a plain VARCHAR and needs nothing.
 */
return new class extends Migration
{
    /** The values `meeting_action_items.status` already allows. */
    private const TARGET = "('pending', 'in_progress', 'on_hold', 'completed', 'cancelled')";

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if ($this->alreadyWidened()) {
            return;
        }

        DB::statement('ALTER TABLE `tasks` MODIFY COLUMN `status` ENUM'.self::TARGET." NOT NULL DEFAULT 'pending'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Narrowing cannot happen while rows use the new values, so report rather
        // than silently truncating someone's work to the nearest old state.
        $stranded = DB::table('tasks')
            ->whereIn('status', ['on_hold', 'cancelled'])
            ->count();

        if ($stranded > 0) {
            throw new RuntimeException(
                "Refusing to narrow tasks.status: {$stranded} row(s) use on_hold or cancelled, "
                .'which the previous enum did not allow. Reassign them before rolling back.'
            );
        }

        DB::statement("ALTER TABLE `tasks` MODIFY COLUMN `status` ENUM('pending', 'in_progress', 'completed') NOT NULL DEFAULT 'pending'");
    }

    protected function alreadyWidened(): bool
    {
        $column = collect(Schema::getColumns('tasks'))->firstWhere('name', 'status');

        $type = strtolower((string) ($column['type'] ?? ''));

        return str_contains($type, 'on_hold');
    }
};
