<?php

namespace Database\Factories;

use App\Enums\LinkType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tasks\Models\Task;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoLink;

/**
 * @extends Factory<TodoLink>
 *
 * (todo_id, linkable_type, linkable_id) is unique. The default target is a Task,
 * the cross-module case Phase 7 built the table for — a To-Do linked to another
 * To-Do would exercise a different code path in `TodoLinkService::resolveReverse`.
 */
class TodoLinkFactory extends Factory
{
    protected $model = TodoLink::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'todo_id' => Todo::factory(),
            'linkable_type' => (new Task)->getMorphClass(),
            'linkable_id' => Task::factory(),
            'link_type' => LinkType::RelatesTo->value,
        ];
    }

    public function toTask(Task $task, LinkType|string $type = LinkType::Blocks): static
    {
        return $this->state(fn (): array => [
            'linkable_type' => $task->getMorphClass(),
            'linkable_id' => $task->getKey(),
            'link_type' => $type instanceof LinkType ? $type->value : $type,
        ]);
    }
}
