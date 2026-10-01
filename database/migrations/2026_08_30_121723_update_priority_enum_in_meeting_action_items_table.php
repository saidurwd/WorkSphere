<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE meeting_action_items MODIFY COLUMN priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE meeting_action_items MODIFY COLUMN priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal'");
    }
};
