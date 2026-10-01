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
                if (! Schema::hasColumn('tasks', 'obligation_id')) {
                    $table->foreignId('obligation_id')->nullable()->constrained()->nullOnDelete();
                }
                if (! Schema::hasColumn('tasks', 'task_no')) {
                    $table->string('task_no')->nullable();
                }
                if (! Schema::hasIndex('tasks', ['obligation_id'])) {
                    $table->index('obligation_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('tasks')) {
            return;
        }

        Schema::table('tasks', function (Blueprint $table) {
            $hasObligationId = Schema::hasColumn('tasks', 'obligation_id');

            if ($hasObligationId) {
                // Order matters and differs per driver: MySQL refuses to drop an index
                // that a live foreign key depends on, SQLite refuses to drop a column
                // that a live index depends on. Dropping FK -> index -> column is the
                // only sequence both accept.
                $table->dropForeign(['obligation_id']);
            }

            if (Schema::hasIndex('tasks', ['obligation_id'])) {
                $table->dropIndex(['obligation_id']);
            }

            if ($hasObligationId) {
                $table->dropColumn('obligation_id');
            }

            if (Schema::hasColumn('tasks', 'task_no')) {
                $table->dropColumn('task_no');
            }
        });
    }
};
