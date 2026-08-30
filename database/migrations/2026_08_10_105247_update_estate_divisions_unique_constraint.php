<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estate_divisions', function (Blueprint $table) {
            $table->dropUnique('estate_divisions_division_code_unique');
            $table->unique(['estate_id', 'division_code'], 'estate_divisions_estate_id_division_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('estate_divisions', function (Blueprint $table) {
            $table->dropUnique('estate_divisions_estate_id_division_code_unique');
            $table->unique('division_code', 'estate_divisions_division_code_unique');
        });
    }
};
