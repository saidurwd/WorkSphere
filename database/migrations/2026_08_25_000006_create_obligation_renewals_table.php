<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('obligation_renewals')) {
            Schema::create('obligation_renewals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('obligation_id')->constrained()->cascadeOnDelete();
                $table->date('previous_expiry_date');
                $table->date('new_start_date');
                $table->date('new_expiry_date');
                $table->date('renewal_date');
                $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('cost', 15, 2)->nullable();
                $table->string('currency')->default('BDT');
                $table->string('purchase_reference')->nullable();
                $table->string('invoice_reference')->nullable();
                $table->text('remarks')->nullable();
                $table->foreignId('renewed_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('obligation_renewals');
    }
};
