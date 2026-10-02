<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingAgenda;
use Modules\Meetings\Models\MeetingDiscussion;

/**
 * @extends Factory<MeetingDiscussion>
 *
 * A discussion hangs off an agenda, so both parents are created here rather than
 * left to an FK default. `agenda_id` is NOT NULL, and a discussion with no agenda
 * is not a state the application can produce.
 */
class MeetingDiscussionFactory extends Factory
{
    protected $model = MeetingDiscussion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $meeting = Meeting::factory();

        return [
            'meeting_id' => $meeting,
            'agenda_id' => MeetingAgenda::factory()->for($meeting),
            'topic' => fake()->sentence(4),
            'discussion' => fake()->paragraph(),
            'key_points' => fake()->optional()->paragraph(),
            'discussion_by' => null,
            'sort_order' => 1,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
