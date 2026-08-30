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
        Schema::table('software_products', function (Blueprint $table) {
            $table->timestamps();
        });

        Schema::table('software_licenses', function (Blueprint $table) {
            $table->timestamps();
        });

        Schema::table('software_installations', function (Blueprint $table) {
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('software_products', function (Blueprint $table) {
            $table->dropTimestamps();
        });

        Schema::table('software_licenses', function (Blueprint $table) {
            $table->dropTimestamps();
        });

        Schema::table('software_installations', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
