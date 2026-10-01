<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Todos\Models\Todo;

/**
 * @extends Factory<Todo>
 */
class TodoFactory extends Factory
{
    protected $model = Todo::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // `creator_id` is NOT NULL and restricts on delete, so a To-Do always
            // has an author. `assignee_id` and `department_id` stay null by default:
            // an unassigned To-Do is a valid state, and a nullable FK that is
            // always populated in tests hides the null path entirely.
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => WorkItemStatus::Inbox,
            'priority' => Priority::Medium,
            'visibility' => Visibility::Personal,
            'creator_id' => User::factory(),
            'assignee_id' => null,
            'department_id' => null,
            'start_date' => null,
            'due_date' => null,
        ];
    }

    /**
     * The quick-capture shape: a title and nothing else. Every other column
     * falls back to the schema default, which is what the index page posts.
     */
    public function titleOnly(string $title = 'Quick capture'): static
    {
        // Quick capture is a title and nothing else, so the optional text is
        // explicitly cleared rather than left to the 10%-or-so `optional()`.
        return $this->state(fn (): array => [
            'title' => $title,
            'description' => null,
        ]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (): array => ['assignee_id' => $user->id]);
    }

    public function createdBy(User $user): static
    {
        return $this->state(fn (): array => ['creator_id' => $user->id]);
    }

    public function inStatus(WorkItemStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    public function withVisibility(Visibility $visibility): static
    {
        return $this->state(fn (): array => ['visibility' => $visibility]);
    }

    public function dueOn(string $date): static
    {
        return $this->state(fn (): array => ['due_date' => $date]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => WorkItemStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'status' => WorkItemStatus::InProgress,
            'completed_at' => null,
            'due_date' => now()->subDays(2)->format('Y-m-d'),
        ]);
    }
}
