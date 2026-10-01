<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskRemark;

/**
 * @extends Factory<TaskRemark>
 */
class TaskRemarkFactory extends Factory
{
    protected $model = TaskRemark::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'user_id' => User::factory(),
            'remark' => fake()->sentence(8),
        ];
    }
}
