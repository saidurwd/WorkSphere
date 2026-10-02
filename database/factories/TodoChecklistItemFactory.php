<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoChecklistItem;

/**
 * @extends Factory<TodoChecklistItem>
 *
 * Incomplete by default, with `completed_at` and `completed_by` NULL. Those two
 * columns are the pair the model keeps in step, so a fixture that filled them
 * together would model a half-completed item the service can never produce.
 */
class TodoChecklistItemFactory extends Factory
{
    protected $model = TodoChecklistItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'todo_id' => Todo::factory(),
            'title' => fake()->sentence(4),
            'is_completed' => false,
            'completed_at' => null,
            'completed_by' => null,
            'sort_order' => 1,
        ];
    }

    public function completed(?User $user = null): static
    {
        return $this->state(fn (): array => [
            'is_completed' => true,
            'completed_at' => now(),
            'completed_by' => $user?->id ?? User::factory(),
        ]);
    }

    public function at(int $order): static
    {
        return $this->state(fn (): array => ['sort_order' => $order]);
    }
}
