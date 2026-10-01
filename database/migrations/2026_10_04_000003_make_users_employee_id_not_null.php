<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `users.employee_id` NOT NULL — GAP-040.
 *
 * THE MIGRATION REFUSES TO RUN until every user has an employee record.
 *
 * This is deliberate and it is the whole point. The prompt's own precondition —
 * "verify every existing user has an employee row before applying the constraint" —
 * does not hold on the installs this project has: users exist with no employee
 * and `employees` holds unreferenced rows. Applying NOT NULL there would lock out
 * every account, including the super-admin, with no way back except raw SQL.
 *
 * So the migration checks, and on failure throws with the number and the remedy
 * rather than corrupting the schema. The remedy is `users:backfill-employees`, which
 * reports first and only writes with `--link`.
 *
 * Once the column is NOT NULL, the split is enforced by the database rather than
 * by the hope that every future code path remembers to check.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'employee_id')) {
            return;
        }

        $column = collect(Schema::getColumns('users'))->firstWhere('name', 'employee_id');

        if ($column['nullable'] === false) {
            return;
        }

        $orphans = DB::table('users')->whereNull('employee_id')->count();

        if ($orphans > 0) {
            throw new RuntimeException(
                "Refusing to make users.employee_id NOT NULL: {$orphans} user(s) have no employee "
                .'record, and every one of them would be locked out. Run '
                .'`php artisan users:backfill-employees` to see who is missing, then `--link` to attach the '
                .'matching employee rows. Users with no match need an employee record created first.'
            );
        }

        // The existing foreign key is `ON DELETE SET NULL`, which MySQL rejects
        // against a NOT NULL column ("needed in a foreign key constraint"). It has
        // to be re-declared as RESTRICT: a user account whose employee record is
        // about to disappear must block the delete rather than lose its link.
        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['employee_id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('employee_id')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'employee_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropForeign(['employee_id']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->unsignedBigInteger('employee_id')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();
        });
    }
};
