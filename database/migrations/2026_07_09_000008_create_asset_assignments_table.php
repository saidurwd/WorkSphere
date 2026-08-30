<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('assigned_date')->nullable();
            $table->date('expected_return_date')->nullable();
            $table->date('returned_date')->nullable();
            $table->string('assigned_by')->nullable();
            $table->string('received_by')->nullable();
            $table->text('assignment_note')->nullable();
            $table->enum('status', ['Assigned', 'Returned', 'Lost', 'Transferred'])->default('Assigned');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
