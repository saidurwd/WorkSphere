<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('obligation_responsibilities')) {
            Schema::create('obligation_responsibilities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('obligation_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('responsibility_type');
                $table->integer('escalation_level')->nullable();
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->unique(['obligation_id', 'user_id', 'responsibility_type'], 'obligation_resp_unique');
                $table->index('obligation_id');
                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('obligation_responsibilities');
    }
};
