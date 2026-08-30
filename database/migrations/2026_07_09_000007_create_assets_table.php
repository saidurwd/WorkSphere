<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag')->unique();
            $table->string('asset_name');
            $table->foreignId('category_id')->constrained('asset_categories')->cascadeOnDelete();
            $table->foreignId('sub_category_id')->nullable()->constrained('asset_sub_categories')->nullOnDelete();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('service_tag')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 12, 2)->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->date('warranty_start')->nullable();
            $table->date('warranty_end')->nullable();
            $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('current_status', [
                'Available', 'Assigned', 'In Repair', 'In Transit', 'Disposed', 'Lost', 'Damaged', 'Reserved',
            ])->default('Available');
            $table->enum('condition_status', [
                'Excellent', 'Good', 'Fair', 'Poor', 'Damaged',
            ])->default('Good');
            $table->integer('depreciation_years')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
