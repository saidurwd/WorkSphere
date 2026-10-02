<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskTransfer;

/**
 * @extends Factory<TaskTransfer>
 *
 * A transfer is a HISTORY row: it records who moved a task to whom, and the two
 * users are distinct on purpose. A factory that gave the same user on both sides
 * would model a transfer that cannot happen and would hide a bug that writes one.
 */
class TaskTransferFactory extends Factory
{
    protected $model = TaskTransfer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'from_user_id' => User::factory(),
            'to_user_id' => User::factory(),
            'transferred_by' => User::factory(),
            'reason' => fake()->sentence(10),
            'remarks' => fake()->optional()->sentence(),
            'transfer_date' => now(),
        ];
    }
}
