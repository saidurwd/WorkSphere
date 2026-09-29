<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MeetingPermissionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ProjectPermissionSeeder::class);
    }
}
