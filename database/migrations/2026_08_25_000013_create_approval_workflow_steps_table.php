<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('approval_workflow_steps')) {
            Schema::create('approval_workflow_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('approval_workflow_id')->constrained()->cascadeOnDelete();
                $table->integer('step_order');
                $table->string('approver_type');
                $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->boolean('required')->default(true);
                $table->timestamps();

                $table->index('approval_workflow_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_workflow_steps');
    }
};
