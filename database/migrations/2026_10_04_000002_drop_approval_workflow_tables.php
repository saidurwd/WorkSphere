<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop `approval_workflows` and `approval_workflow_steps` — GAP-031, decided.
 *
 * The Obligations module already models approval: `obligations.approver_user_id`,
 * `approver_decision` and a `status` that moves through `pending_approval`. This
 * pair is a second, parallel approval mechanism with models and a seeder but no
 * route, no controller and no query anywhere — keeping it is exactly the
 * duplication GAP-048 exists to remove, and it has never been used.
 *
 * The drop is guarded rather than unconditional: if any row exists at migrate
 * time, the migration refuses and says so. Silently deleting configuration data is
 * not something a migration should decide on its own — on an install that
 * somehow has rows, those rows are evidence that the feature was in use, and that
 * changes the decision.
 */
return new class extends Migration
{
    /** Child first, so no foreign key is left pointing at a dropped table. */
    private const TABLES = ['approval_workflow_steps', 'approval_workflows'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $rows = DB::table($table)->count();

            if ($rows > 0) {
                throw new RuntimeException(
                    "Refusing to drop {$table}: it holds {$rows} row(s). If approval workflows are in "
                    .'use somewhere, the decision to remove them needs revisiting — this migration will '
                    .'not delete configuration data on its own.'
                );
            }
        }

        Schema::disableForeignKeyConstraints();

        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }

        Schema::enableForeignKeyConstraints();
    }

    /**
     * Irreversible by design. The tables are recreated empty by the original
     * migration if this is ever rolled back through `migrate`, and this
     * `down()` is a no-op rather than a pretend reconstruction — the schema
     * history is the record of what existed.
     */
    public function down(): void
    {
        // Nothing to do. See the note above.
    }
};
