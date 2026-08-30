<?php

namespace Database\Seeders;

use App\Models\MeetingTag;
use Illuminate\Database\Seeder;

class MeetingTagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = [
            ['name' => 'ERP', 'color' => '#3b82f6'],
            ['name' => 'Sage', 'color' => '#10b981'],
            ['name' => 'Cyber Security', 'color' => '#ef4444'],
            ['name' => 'Procurement', 'color' => '#8b5cf6'],
            ['name' => 'HR', 'color' => '#f59e0b'],
            ['name' => 'Garden', 'color' => '#22c55e'],
            ['name' => 'Management', 'color' => '#6366f1'],
            ['name' => 'Project', 'color' => '#06b6d4'],
            ['name' => 'Finance', 'color' => '#f97316'],
            ['name' => 'IT', 'color' => '#1d4ed8'],
        ];

        foreach ($tags as $tag) {
            MeetingTag::firstOrCreate(['name' => $tag['name']], $tag);
        }
    }
}
