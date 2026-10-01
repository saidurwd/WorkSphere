<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\WorkItemStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tasks\Models\Task;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'responsible_user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'priority' => fake()->randomElement(Priority::values()),
            'status' => 'pending',
            'due_date' => fake()->dateTimeBetween('now', '+30 days')->format('Y-m-d'),
        ];
    }

    /**
     * A task owned and carried out by the same person.
     */
    public function ownedBy(User $user): static
    {
        return $this->state(fn (): array => [
            'user_id' => $user->id,
            'responsible_user_id' => $user->id,
        ]);
    }

    /**
     * A task the given user is accountable for but does not own.
     */
    public function assignedTo(User $user): static
    {
        return $this->state(fn (): array => [
            'responsible_user_id' => $user->id,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * A sub-task. The parent is passed in rather than generated so a test can build
     * an explicit hierarchy.
     */
    public function withParent(Task $parent): static
    {
        return $this->state(fn (): array => ['parent_id' => $parent->id]);
    }

    /**
     * A task with no deadline. `due_date` is nullable from Phase 8 — GAP-026 — so
     * this is a real state, not a fixture oddity.
     */
    public function undated(): static
    {
        return $this->state(fn (): array => ['due_date' => null]);
    }

    /**
     * Every status `tasks.status` allows after the Phase 8 widening, so a view
     * that cannot render one of them is caught here rather than in production.
     */
    public function inAnyStatus(): static
    {
        return $this->state(fn (): array => [
            'status' => fake()->randomElement([
                WorkItemStatus::Pending->value,
                WorkItemStatus::InProgress->value,
                WorkItemStatus::OnHold->value,
                WorkItemStatus::Completed->value,
                WorkItemStatus::Cancelled->value,
            ]),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'status' => 'pending',
            'completed_at' => null,
            'due_date' => now()->subDays(3)->format('Y-m-d'),
        ]);
    }
}
