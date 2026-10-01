<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the request context columns the audit trail needs. Without them an
     * audit row cannot answer "who did this, from where".
     */
    public function up(): void
    {
        if (! Schema::hasTable('tyro_audit_logs')) {
            return;
        }

        Schema::table('tyro_audit_logs', function (Blueprint $table): void {
            if (! Schema::hasColumn('tyro_audit_logs', 'ip_address')) {
                $table->string('ip_address', 45)->nullable();
            }

            if (! Schema::hasColumn('tyro_audit_logs', 'user_agent')) {
                $table->string('user_agent', 255)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tyro_audit_logs')) {
            return;
        }

        Schema::table('tyro_audit_logs', function (Blueprint $table): void {
            $columns = [];

            if (Schema::hasColumn('tyro_audit_logs', 'user_agent')) {
                $columns[] = 'user_agent';
            }

            if (Schema::hasColumn('tyro_audit_logs', 'ip_address')) {
                $columns[] = 'ip_address';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
