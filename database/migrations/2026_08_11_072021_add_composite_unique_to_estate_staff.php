<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estate_staff', function (Blueprint $table) {
            $table->unique(['estate_id', 'division_id', 'estate_residence_type_id', 'pf_number', 'staff_name'], 'estate_staff_unique');
        });
    }

    public function down(): void
    {
        Schema::table('estate_staff', function (Blueprint $table) {
            $table->dropUnique('estate_staff_unique');
        });
    }
};
