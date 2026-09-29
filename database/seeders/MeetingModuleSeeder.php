<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MeetingModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            MeetingTypeSeeder::class,
            MeetingTagSeeder::class,
            // MeetingSeeder::class,
        ]);
    }
}
