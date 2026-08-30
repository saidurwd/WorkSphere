<?php

namespace Database\Seeders;

use App\Models\MeetingType;
use Illuminate\Database\Seeder;

class MeetingTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Management Meeting', 'code' => 'MGMT', 'description' => 'Management team meetings', 'color' => '#3b82f6', 'sort_order' => 1],
            ['name' => 'IT Meeting', 'code' => 'IT', 'description' => 'IT department meetings', 'color' => '#10b981', 'sort_order' => 2],
            ['name' => 'Finance Meeting', 'code' => 'FIN', 'description' => 'Finance department meetings', 'color' => '#f59e0b', 'sort_order' => 3],
            ['name' => 'HR Meeting', 'code' => 'HR', 'description' => 'Human resources meetings', 'color' => '#ef4444', 'sort_order' => 4],
            ['name' => 'Procurement Meeting', 'code' => 'PROC', 'description' => 'Procurement meetings', 'color' => '#8b5cf6', 'sort_order' => 5],
            ['name' => 'Project Meeting', 'code' => 'PROJ', 'description' => 'Project-specific meetings', 'color' => '#06b6d4', 'sort_order' => 6],
            ['name' => 'Department Meeting', 'code' => 'DEPT', 'description' => 'General department meetings', 'color' => '#f97316', 'sort_order' => 7],
            ['name' => 'Emergency Meeting', 'code' => 'EMER', 'description' => 'Urgent emergency meetings', 'color' => '#dc2626', 'sort_order' => 8],
            ['name' => 'Monthly Review', 'code' => 'MONTH', 'description' => 'Monthly review meetings', 'color' => '#6366f1', 'sort_order' => 9],
            ['name' => 'Board Meeting', 'code' => 'BRD', 'description' => 'Board of directors meetings', 'color' => '#1d4ed8', 'sort_order' => 10],
        ];

        foreach ($types as $type) {
            MeetingType::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}
