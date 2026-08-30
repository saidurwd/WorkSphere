<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maintenance_history', function (Blueprint $table) {
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('maintenance_history', function (Blueprint $table) {
            $table->dropForeign(['checked_by']);
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['checked_by', 'verified_by']);
        });
    }
};
