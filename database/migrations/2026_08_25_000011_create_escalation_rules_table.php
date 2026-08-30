<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('escalation_rules')) {
            Schema::create('escalation_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('obligation_type_id')->nullable()->constrained()->nullOnDelete();
                $table->integer('days_before_expiry')->nullable();
                $table->integer('days_after_expiry')->nullable();
                $table->string('escalation_level');
                $table->string('recipient_type');
                $table->string('channel')->default('IN_APP');
                $table->boolean('active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('escalation_rules');
    }
};
