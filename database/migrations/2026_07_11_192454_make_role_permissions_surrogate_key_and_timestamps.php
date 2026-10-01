<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('roles') && ! $this->hasForeignKey('role_permissions', 'role_id')) {
            Schema::table('role_permissions', function (Blueprint $table) {
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('roles') && $this->hasForeignKey('role_permissions', 'role_id')) {
            Schema::table('role_permissions', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
            });
        }
    }

    /**
     * Portable foreign key probe. The previous implementation queried
     * `information_schema` directly, which does not exist on SQLite.
     */
    protected function hasForeignKey(string $table, string $column): bool
    {
        return Schema::hasForeignKey($table, [$column]);
    }
};
