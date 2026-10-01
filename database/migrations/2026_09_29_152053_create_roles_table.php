<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        // `role_permissions` predates this table, so its foreign key is attached here
        // rather than in 2026_07_09_000026 where the parent table did not yet exist.
        if (! Schema::hasForeignKey('role_permissions', ['role_id'])) {
            Schema::table('role_permissions', function (Blueprint $table) {
                $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('role_permissions') && Schema::hasForeignKey('role_permissions', ['role_id'])) {
            Schema::table('role_permissions', function (Blueprint $table) {
                $table->dropForeign(['role_id']);
            });
        }

        Schema::dropIfExists('roles');
    }
};
