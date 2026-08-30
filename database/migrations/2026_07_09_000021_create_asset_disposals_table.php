<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('disposal_date')->nullable();
            $table->decimal('book_value', 12, 2)->nullable();
            $table->decimal('sale_value', 12, 2)->nullable();
            $table->enum('disposal_reason', ['Obsolete', 'Damaged', 'Lost', 'Sold', 'Scrapped'])->nullable();
            $table->string('approved_by')->nullable();
            $table->text('remarks')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_disposals');
    }
};
