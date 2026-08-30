<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('obligations')) {
            Schema::create('obligations', function (Blueprint $table) {
                $table->id();
                $table->string('obligation_no')->unique();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('obligation_type_id')->constrained()->cascadeOnDelete();
                $table->foreignId('category_id')->constrained('obligation_categories')->cascadeOnDelete();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('department_id')->constrained()->cascadeOnDelete();
                $table->foreignId('location_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('owner_user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('backup_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('reviewer_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('approver_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('start_date');
                $table->date('expiry_date');
                $table->boolean('renewal_required')->default(true);
                $table->boolean('auto_renew')->default(false);
                $table->string('recurrence_type')->nullable();
                $table->integer('recurrence_interval')->nullable();
                $table->string('priority')->default('medium');
                $table->string('risk_level')->default('medium');
                $table->decimal('estimated_cost', 15, 2)->nullable();
                $table->string('currency')->default('BDT');
                $table->string('status')->default('active');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index('obligation_no');
                $table->index('obligation_type_id');
                $table->index('department_id');
                $table->index('company_id');
                $table->index('location_id');
                $table->index('owner_user_id');
                $table->index('vendor_id');
                $table->index('expiry_date');
                $table->index('status');
                $table->index('priority');
                $table->index('risk_level');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('obligations');
    }
};
