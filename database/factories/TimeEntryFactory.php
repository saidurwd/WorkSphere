<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TimeEntry;

/**
 * @extends Factory<TimeEntry>
 */
class TimeEntryFactory extends Factory
{
    protected $model = TimeEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'minutes' => fake()->numberBetween(5, 240),
            'logged_on' => now()->subDays(fake()->numberBetween(0, 6))->format('Y-m-d'),
            'note' => fake()->optional()->sentence(6),
        ];
    }

    public function on(string $date): static
    {
        return $this->state(fn (): array => ['logged_on' => $date]);
    }

    public function minutes(int $minutes): static
    {
        return $this->state(fn (): array => ['minutes' => $minutes]);
    }
}
