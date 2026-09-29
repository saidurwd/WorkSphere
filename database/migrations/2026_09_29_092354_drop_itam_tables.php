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
        'maintenance_history',
        'maintenance_requests',
        'software_installations',
        'software_licenses',
        'software_products',
        'goods_receipt_details',
        'goods_receipts',
        'purchase_order_details',
        'purchase_orders',
        'asset_documents',
        'asset_audit_details',
        'asset_audits',
        'asset_disposals',
        'asset_transfers',
        'asset_assignments',
        'assets',
        'asset_sub_categories',
        'asset_categories',
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
                        ->where(function ($permissions) {
                            $permissions->where('permission_name', 'like', 'asset.%')
                                ->orWhere('permission_name', 'like', 'maintenance.%')
                                ->orWhere('permission_name', 'maintenance.manage')
                                ->orWhere('permission_name', 'audit.manage');
                        });
                })
                ->delete();

            DB::table('permissions')
                ->where(function ($query) {
                    $query->where('permission_name', 'like', 'asset.%')
                        ->orWhere('permission_name', 'like', 'maintenance.%')
                        ->orWhere('permission_name', 'maintenance.manage')
                        ->orWhere('permission_name', 'audit.manage');
                })
                ->delete();
        }

        if (Schema::hasTable('activity_logs')) {
            DB::table('activity_logs')
                ->whereIn('module_name', [
                    'assets',
                    'asset_assignments',
                    'asset_transfers',
                    'asset_audits',
                    'asset_disposals',
                    'asset_documents',
                    'maintenance_requests',
                    'maintenance_history',
                    'purchase_orders',
                    'purchase_order_details',
                    'goods_receipts',
                    'goods_receipt_details',
                    'software_installations',
                    'software_licenses',
                    'software_products',
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
