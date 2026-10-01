<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        $this->call(TaskSeeder::class);
        $this->call(FoundationSeeder::class);
        $this->call(ComplianceObligationSeeder::class);
        $this->call(MeetingModuleSeeder::class);
        $this->call(MeetingTagSeeder::class);
        $this->call(MeetingSeeder::class);
        $this->call(ObligationSeeder::class);
        $this->call(TodoSeeder::class);
        // Last: ProjectPermissionSeeder deletes any permission that is not in its
        // own allow-list, so nothing that creates permissions may run after it.
        $this->call(ProjectPermissionSeeder::class);
    }
}
