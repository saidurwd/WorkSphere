<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estate_residence_types', function (Blueprint $table) {
            $table->id();
            $table->string('residence_type_eng', 100);
            $table->string('residence_type_bn', 100)->nullable();
            $table->string('residence_type_code', 20)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estate_residence_types');
    }
};
