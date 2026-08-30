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
        Schema::table('software_installations', function (Blueprint $table) {
            $table->dropColumn('installed_by');
            $table->foreignId('installed_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('software_installations', function (Blueprint $table) {
            $table->dropForeign(['installed_by']);
            $table->dropColumn('installed_by');
            $table->string('installed_by')->nullable();
        });
    }
};
