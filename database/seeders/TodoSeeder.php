<?php

namespace Database\Seeders;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Models\Department;
use App\Models\User;
use Database\Factories\TodoFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Local development data for the To-Do module.
 *
 * Deliberately contains no PII and no credentials: titles are generated and
 * every user referenced is created with the stock factory. Nothing here is
 * required by the application — it exists so a developer has something to look at
 * after `migrate --seed`.
 */
class TodoSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->take(5)->get();

        if ($users->count() < 5) {
            $users = User::factory()->count(5 - $users->count())->create()->merge($users);
        }

        $department = Department::query()->first() ?? Department::factory()->create();

        DB::transaction(function () use ($users, $department): void {
            // The inbox: captured, not yet triaged, and mostly unassigned.
            TodoFactory::new()->count(25)->create();

            // Work in flight, with a start date and a deadline a week out.
            foreach ($users as $user) {
                TodoFactory::new()
                    ->createdBy($user)
                    ->assignedTo($user)
                    ->inStatus(WorkItemStatus::InProgress)
                    ->dueOn(now()->addWeek()->format('Y-m-d'))
                    ->create([
                        'start_date' => now()->subDays(2)->format('Y-m-d'),
                    ]);
            }

            // Done.
            TodoFactory::new()->count(4)->completed()->create([
                'priority' => Priority::High,
                'completed_at' => now()->subDay(),
            ]);

            // Overdue: past due, still open. Drives the overdue widget.
            TodoFactory::new()->count(3)->overdue()->create();

            // Shared with a department, which is the only visibility that widens
            // the audience beyond the To-Do's own parties.
            TodoFactory::new()->count(3)->withVisibility(Visibility::Team)->create([
                'department_id' => $department->id,
            ]);
        });
    }
}
