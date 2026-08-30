<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (!Schema::hasColumn('tasks', 'obligation_id')) {
                    $table->foreignId('obligation_id')->nullable()->constrained()->nullOnDelete()->after('project_id');
                }
                if (!Schema::hasColumn('tasks', 'task_no')) {
                    $table->string('task_no')->nullable()->after('obligation_id');
                }
                if (!Schema::hasIndex('tasks', ['obligation_id'])) {
                    $table->index('obligation_id');
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('obligation_id');
            $table->dropColumn('task_no');
            $table->dropIndex(['obligation_id']);
        });
    }
};
