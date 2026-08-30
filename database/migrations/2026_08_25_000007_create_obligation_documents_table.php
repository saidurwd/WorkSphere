<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('obligation_documents')) {
            Schema::create('obligation_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('obligation_id')->constrained()->cascadeOnDelete();
                $table->string('document_type');
                $table->string('file_name');
                $table->string('file_path');
                $table->unsignedBigInteger('file_size')->nullable();
                $table->string('mime_type')->nullable();
                $table->date('document_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('obligation_documents');
    }
};
