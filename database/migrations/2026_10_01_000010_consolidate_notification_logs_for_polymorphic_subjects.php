<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated notification log — DATABASE-ARCHITECTURE.md §4.9.
 *
 * `notification_logs` already exists (2026_08_25_000010) as the Obligations
 * module's log, and is shaped around a mandatory `obligation_id`. This migration
 * makes the same table able to serve every module — the "consolidation" the spec
 * describes — without touching or moving any existing row:
 *
 * - `subject_type` / `subject_id` are added as NULLABLE. Existing obligation rows
 *   keep using `obligation_id`; every new module writes the polymorphic pair.
 *   The cutover and the `obligation_id` drop are Phase 8 consolidation work
 *   (DATABASE-ARCHITECTURE §5, order 16), not additive work.
 *
 * `dedupe_key` is the most valuable column here: UNIQUE makes every send
 * idempotent, which is what lets a cron that fires twice not double-notify.
 * NULL is permitted so legacy rows, which have no key, are unaffected.
 *
 * `status` keeps its existing 'PENDING' default. The spec writes 'pending'; the
 * column already holds uppercase values and changing a default would alter
 * existing behaviour, so it is left alone and reported.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('notification_logs')) {
            return;
        }

        Schema::table('notification_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('notification_logs', 'subject_type')) {
                $table->string('subject_type')->nullable();
            }

            if (! Schema::hasColumn('notification_logs', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable();
            }

            if (! Schema::hasColumn('notification_logs', 'dedupe_key')) {
                $table->string('dedupe_key')->nullable()->unique();
            }
        });

        $this->relaxObligationId();

        $this->addIndex('notiflog_subject_idx', ['subject_type', 'subject_id']);
        $this->addIndex('notiflog_dispatch_idx', ['status', 'scheduled_at']);
        $this->addIndex('notiflog_user_idx', ['user_id', 'created_at']);
    }

    /**
     * `obligation_id` was NOT NULL, which made it impossible to record a delivery
     * for any other module — the table could not do the one job this migration
     * exists to give it. Relaxing the constraint is additive: existing rows keep
     * their value, and the foreign key is untouched. The column is dropped only in
     * Phase 8 consolidation, once every Obligations writer is dual-writing.
     */
    protected function relaxObligationId(): void
    {
        if (! Schema::hasColumn('notification_logs', 'obligation_id')) {
            return;
        }

        $column = collect(Schema::getColumns('notification_logs'))->firstWhere('name', 'obligation_id');

        if ($column['nullable'] ?? false) {
            return;
        }

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('obligation_id')->nullable()->change();
        });
    }

    protected function tightenObligationId(): void
    {
        if (! Schema::hasColumn('notification_logs', 'obligation_id')) {
            return;
        }

        // Any row without an obligation subject could not be represented by the
        // pre-consolidation schema, so refuse rather than fail mid-rollback.
        $orphans = DB::table('notification_logs')->whereNull('obligation_id')->count();

        if ($orphans > 0) {
            throw new RuntimeException(
                "Refusing to make notification_logs.obligation_id NOT NULL again: {$orphans} row(s) "
                .'have no obligation and cannot be represented by the original schema.'
            );
        }

        $column = collect(Schema::getColumns('notification_logs'))->firstWhere('name', 'obligation_id');

        if (! ($column['nullable'] ?? true)) {
            return;
        }

        Schema::table('notification_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('obligation_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('notification_logs')) {
            return;
        }

        $this->tightenObligationId();

        $this->dropIndex('notiflog_subject_idx');
        $this->dropIndex('notiflog_dispatch_idx');
        $this->dropIndex('notiflog_user_idx');

        Schema::table('notification_logs', function (Blueprint $table) {
            $columns = [];

            // dedupe_key sits behind a unique index, which must go first. MySQL
            // will not drop an index a live key depends on; SQLite will not drop
            // a column a live index covers. Dropping the index first satisfies both.
            if (Schema::hasColumn('notification_logs', 'dedupe_key')) {
                $table->dropUnique(['dedupe_key']);
            }

            foreach (['subject_id', 'subject_type', 'dedupe_key'] as $column) {
                if (Schema::hasColumn('notification_logs', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }

    /**
     * @param  list<string>  $columns
     */
    protected function addIndex(string $name, array $columns): void
    {
        if (Schema::hasIndex('notification_logs', $name)) {
            return;
        }

        Schema::table('notification_logs', function (Blueprint $table) use ($columns, $name) {
            $table->index($columns, $name);
        });
    }

    protected function dropIndex(string $name): void
    {
        if (! Schema::hasIndex('notification_logs', $name)) {
            return;
        }

        Schema::table('notification_logs', function (Blueprint $table) use ($name) {
            $table->dropIndex($name);
        });
    }
};
