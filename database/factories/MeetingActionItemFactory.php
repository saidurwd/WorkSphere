<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingActionItem;

/**
 * @extends Factory<MeetingActionItem>
 *
 * `action_no` is numbered per meeting, so it is 1 by default. A random number
 * would let a second action item in the same meeting collide on nothing and read
 * as if numbering were arbitrary, when the whole point of the column is that it
 * is sequential within the meeting.
 */
class MeetingActionItemFactory extends Factory
{
    protected $model = MeetingActionItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'agenda_id' => null,
            'discussion_id' => null,
            'decision_id' => null,
            'action_no' => 1,
            'title' => fake()->sentence(5),
            'description' => fake()->optional()->paragraph(),
            'assigned_to' => null,
            'assigned_department_id' => null,
            'priority' => 'normal',
            'start_date' => null,
            'due_date' => null,
            'status' => 'open',
            'completion_percentage' => 0,
            'task_id' => null,
            'completed_at' => null,
            'completed_by' => null,
            'remarks' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (): array => ['assigned_to' => $user->id]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'completed',
            'completion_percentage' => 100,
            'completed_at' => now(),
        ]);
    }
}
