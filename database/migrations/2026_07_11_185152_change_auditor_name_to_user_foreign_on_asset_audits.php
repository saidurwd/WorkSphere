<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asset_audits', function (Blueprint $table) {
            $table->dropColumn('auditor_name');
            $table->foreignId('auditor_name')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_audits', function (Blueprint $table) {
            $table->dropForeign(['auditor_name']);
            $table->dropColumn('auditor_name');
            $table->string('auditor_name')->nullable();
        });
    }
};
