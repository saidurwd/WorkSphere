<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('transferred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->text('remarks')->nullable();
            $table->string('file_title')->nullable();
            $table->string('file_attache')->nullable();
            $table->date('transfer_date')->nullable();
            $table->timestamps();

            $table->index(['task_id', 'transfer_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_transfers');
    }
};
