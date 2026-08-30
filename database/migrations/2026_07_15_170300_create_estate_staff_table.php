<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estate_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estate_id')->constrained('estates')->cascadeOnDelete();
            $table->foreignId('division_id')->constrained('estate_divisions')->cascadeOnDelete();
            $table->foreignId('estate_residence_type_id')->nullable()->constrained('estate_residence_types')->nullOnDelete();
            $table->string('staff_name', 150);
            $table->string('staff_type', 50)->nullable();
            $table->string('designation', 100)->nullable();
            $table->string('pf_number', 30)->nullable();
            $table->string('quarter_number', 30)->nullable();
            $table->string('quarter_code', 30)->nullable();
            $table->string('token_number', 30)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estate_staff');
    }
};
