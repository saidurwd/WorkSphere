<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `roles.description` — repairs schema drift caused by an edited migration.
 *
 * WHY THIS MIGRATION HAS TO EXIST
 *
 * `2026_09_29_152053_create_roles_table` originally had no `description` column.
 * Phase 2 (commit `0ca11ec`, 2026-10-01) added the column by EDITING THAT FILE.
 * On a database that had already recorded the migration — which is every real
 * deployment, and the development MySQL included — Laravel will never run it again,
 * so the column was never created there. On SQLite the suite migrates from scratch
 * on every run, so it always had the column and no test could see the difference.
 *
 * The symptom was a `QueryException` on saving a role:
 *
 *     Unknown column 'description' in 'SET' (update `roles` ...)
 *
 * The project's own rule is "never edit a migration that has already run in
 * production — add a new one". This is that new one.
 *
 * IDEMPOTENT, so it is a no-op on a database where the edited create-migration DID
 * run — a fresh install, or a test database migrated from scratch. The check is on
 * the column rather than on whether this migration is recorded, because a fresh
 * database has both and must not be given the column twice.
 *
 * `string` to match the create-migration, not `text`. They are not
 * interchangeable on MySQL — the two are different types with different indexes —
 * so the repair has to reproduce the declared type exactly or the two databases
 * will drift in a new way.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || Schema::hasColumn('roles', 'description')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table): void {
            $table->string('description')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        // The column is removed rather than the migration disabled: on a database
        // that never had it, `down()` must stay a no-op, and `hasColumn` is the only
        // way to tell the two apart.
        if (! Schema::hasTable('roles') || ! Schema::hasColumn('roles', 'description')) {
            return;
        }

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn('description');
        });
    }
};
