<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('head_of_department');

            $table->foreignId('head_of_department_id')
                ->nullable()
                ->after('department_code')
                ->constrained('employees')
                ->nullOnDelete();
        });

        DB::statement("ALTER TABLE departments MODIFY COLUMN status ENUM('active', 'inactive') NOT NULL DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE departments MODIFY COLUMN status VARCHAR(255) NOT NULL DEFAULT 'active'");

        Schema::table('departments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('head_of_department_id');

            $table->string('head_of_department')->nullable();
        });
    }
};
