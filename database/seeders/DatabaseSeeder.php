<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Builds a complete, demonstrable organisation from nothing.
 *
 * The order below is a dependency graph, not a preference. Each entry needs the
 * rows the ones before it created:
 *
 * - `FoundationSeeder` — departments, locations, vendors and the employee
 *   roster. Everything else needs people and places to point at.
 * - `UserSeeder` — the accounts and their role assignments, one per roster
 *   member. Runs before the modules because every module seeds ownership, and
 *   `users.employee_id` is NOT NULL so an account cannot exist without one.
 * - `ObligationSeeder` — before `TaskSeeder`, because a share of tasks link to
 *   the obligation that produced them and the foreign key needs the row first.
 * - `TaskSeeder`, `MeetingSeeder`, `TodoSeeder` — the work modules, each
 *   independent of the others but each building the child rows their detail
 *   screens read.
 * - `PlatformActivitySeeder`, `SystemConfigurationSeeder` — the platform
 *   tables: the activity, sign-in and audit trails, the notifications, and the
 *   settings and flags.
 * - `ProjectPermissionSeeder` — last, always. It seeds the permission
 *   catalogue and then deletes every permission not on its own list, so nothing
 *   that creates permissions may run after it.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            FoundationSeeder::class,
            UserSeeder::class,
            ObligationSeeder::class,
            TaskSeeder::class,
            MeetingSeeder::class,
            TodoSeeder::class,
            PlatformActivitySeeder::class,
            SystemConfigurationSeeder::class,
            // Last: ProjectPermissionSeeder deletes any permission that is not in
            // its own allow-list, so nothing that creates permissions may run
            // after it.
            ProjectPermissionSeeder::class,
        ]);
    }
}
