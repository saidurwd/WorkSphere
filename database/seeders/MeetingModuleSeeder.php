<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MeetingModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MeetingPermissionSeeder::class,
            MeetingTypeSeeder::class,
            MeetingTagSeeder::class,
            // MeetingSeeder::class,
        ]);
    }
}
