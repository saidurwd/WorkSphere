<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_sub_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('asset_categories')->cascadeOnDelete();
            $table->string('sub_category_name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_sub_categories');
    }
};
