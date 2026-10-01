<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskWatcher;

/**
 * @extends Factory<TaskWatcher>
 */
class TaskWatcherFactory extends Factory
{
    protected $model = TaskWatcher::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
        ];
    }
}
