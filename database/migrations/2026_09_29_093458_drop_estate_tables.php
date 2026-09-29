<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @var list<string>
     */
    private array $tables = [
        'estate_staff',
        'estate_divisions',
        'estate_residence_types',
        'estates',
    ];

    public function up(): void
    {
        $this->disableForeignKeyConstraints();

        foreach ($this->tables as $table) {
            Schema::dropIfExists($table);
        }

        $this->enableForeignKeyConstraints();

        if (Schema::hasTable('role_permissions') && Schema::hasTable('permissions')) {
            DB::table('role_permissions')
                ->whereIn('permission_id', function ($query) {
                    $query->select('id')
                        ->from('permissions')
                        ->where('permission_name', 'like', 'residence.%');
                })
                ->delete();

            DB::table('permissions')
                ->where('permission_name', 'like', 'residence.%')
                ->delete();
        }

        if (Schema::hasTable('activity_logs')) {
            DB::table('activity_logs')
                ->whereIn('module_name', [
                    'estates',
                    'estate_divisions',
                    'estate_residence_types',
                    'estate_staff',
                ])
                ->delete();
        }
    }

    public function down(): void
    {
        $this->disableForeignKeyConstraints();

        foreach (array_reverse($this->tables) as $table) {
            Schema::dropIfExists($table);
        }

        $this->enableForeignKeyConstraints();
    }

    private function disableForeignKeyConstraints(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }
    }

    private function enableForeignKeyConstraints(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
};
