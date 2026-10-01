<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `App\Models\RolePermission` has timestamps enabled, but the table never got
 * the columns, so every `RolePermission::create()` — including the ones in
 * ProjectPermissionSeeder — failed with "no column named created_at". The
 * 2026_07_11_192454 migration is named for this change but only handled the
 * foreign key.
 *
 * Add-only: existing rows keep their composite primary key and their data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('role_permissions')) {
            return;
        }

        Schema::table('role_permissions', function (Blueprint $table): void {
            if (! Schema::hasColumn('role_permissions', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('role_permissions', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('role_permissions')) {
            return;
        }

        Schema::table('role_permissions', function (Blueprint $table): void {
            $columns = [];

            if (Schema::hasColumn('role_permissions', 'updated_at')) {
                $columns[] = 'updated_at';
            }

            if (Schema::hasColumn('role_permissions', 'created_at')) {
                $columns[] = 'created_at';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
