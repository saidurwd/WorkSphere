<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->enum('document_type', ['Invoice', 'Warranty', 'Manual', 'AMC', 'Insurance', 'Photo'])->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_path')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->timestamp('uploaded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_documents');
    }
};
