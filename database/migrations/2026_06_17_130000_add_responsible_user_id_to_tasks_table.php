<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index('responsible_user_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $hasColumn = Schema::hasColumn('tasks', 'responsible_user_id');

            // FK -> index -> column. MySQL will not drop an index a live foreign key
            // depends on; SQLite will not drop a column a live index covers.
            if ($hasColumn) {
                $table->dropForeign(['responsible_user_id']);
            }

            if (Schema::hasIndex('tasks', ['responsible_user_id'])) {
                $table->dropIndex(['responsible_user_id']);
            }

            if ($hasColumn) {
                $table->dropColumn('responsible_user_id');
            }
        });
    }
};
