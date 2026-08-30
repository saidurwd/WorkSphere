<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->date('repair_date')->nullable();
            $table->decimal('repair_cost', 12, 2)->nullable();
            $table->text('resolution')->nullable();
            $table->decimal('downtime_hours', 8, 2)->nullable();
            $table->string('completed_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_history');
    }
};
