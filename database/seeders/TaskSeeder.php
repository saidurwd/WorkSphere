<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::orderBy('name')->get();
        $user = $users->firstOrFail();

        $now = Carbon::now();

        $templates = [
            ['title' => 'Update project documentation', 'priority' => 'high', 'status' => 'pending'],
            ['title' => 'Review pull request #42', 'priority' => 'medium', 'status' => 'completed'],
            ['title' => 'Fix authentication bug', 'priority' => 'high', 'status' => 'in_progress'],
            ['title' => 'Write unit tests for API', 'priority' => 'medium', 'status' => 'pending'],
            ['title' => 'Design system color palette update', 'priority' => 'low', 'status' => 'pending'],
            ['title' => 'Prepare monthly report', 'priority' => 'medium', 'status' => 'pending'],
            ['title' => 'Deploy staging environment', 'priority' => 'high', 'status' => 'completed'],
            ['title' => 'Refactor user dashboard', 'priority' => 'medium', 'status' => 'in_progress'],
            ['title' => 'Optimize database queries', 'priority' => 'high', 'status' => 'pending'],
            ['title' => 'Implement password reset flow', 'priority' => 'high', 'status' => 'in_progress'],
            ['title' => 'Create onboarding tutorial', 'priority' => 'low', 'status' => 'pending'],
            ['title' => 'Update privacy policy page', 'priority' => 'medium', 'status' => 'completed'],
            ['title' => 'Migrate legacy API endpoints', 'priority' => 'high', 'status' => 'pending'],
            ['title' => 'Add CSV export feature', 'priority' => 'medium', 'status' => 'pending'],
            ['title' => 'Fix mobile navigation bug', 'priority' => 'high', 'status' => 'in_progress'],
        ];

        $dueDateRanges = [
            'overdue' => fn () => $now->subDays(rand(1, 14))->toDateString(),
            'today' => fn () => $now->toDateString(),
            'this_week' => fn () => $now->addDays(rand(1, 6))->toDateString(),
            'this_month' => fn () => $now->addDays(rand(7, 25))->toDateString(),
            'future' => fn () => $now->addDays(rand(26, 60))->toDateString(),
        ];

        $ranges = array_keys($dueDateRanges);
        $weights = [15, 10, 25, 25, 25];
        $createdAtBase = $now->copy()->subDays(30);

        for ($i = 0; $i < 50; $i++) {
            $template = $templates[$i % count($templates)];
            $rangeIndex = $this->weightedRandom($weights);
            $range = $ranges[$rangeIndex];
            $dueDate = $dueDateRanges[$range]();

            $createdAt = $createdAtBase->copy()->addDays(rand(0, 30))->addHours(rand(0, 23))->toDateTimeString();

            Task::create([
                'user_id' => $user->id,
                'responsible_user_id' => $users->random()->id,
                'title' => $template['title'].' #'.($i + 1),
                'description' => fake()->sentence(rand(6, 15)),
                'priority' => $template['priority'],
                'status' => $template['status'],
                'due_date' => $dueDate,
                'completed_at' => $template['status'] === 'completed' ? $createdAt : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }

    private function weightedRandom(array $weights): int
    {
        $total = array_sum($weights);
        $random = rand(1, $total);

        foreach ($weights as $index => $weight) {
            $random -= $weight;
            if ($random <= 0) {
                return $index;
            }
        }

        return array_key_last($weights);
    }
}
