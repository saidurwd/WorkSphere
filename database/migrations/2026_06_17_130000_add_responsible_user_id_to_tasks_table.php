<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('responsible_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->index('responsible_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['responsible_user_id']);
            $table->dropForeign(['responsible_user_id']);
            $table->dropColumn('responsible_user_id');
        });
    }
};
