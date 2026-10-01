<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->constrained('task_projects')->nullOnDelete();
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $hasProjectId = Schema::hasColumn('tasks', 'project_id');

            if ($hasProjectId) {
                // FK -> index -> column: MySQL will not drop an index a live foreign
                // key depends on, SQLite will not drop a column a live index covers.
                $table->dropForeign(['project_id']);
            }

            if (Schema::hasIndex('tasks', ['project_id'])) {
                $table->dropIndex(['project_id']);
            }

            if ($hasProjectId) {
                $table->dropColumn('project_id');
            }
        });
    }
};
