<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingDecision;

/**
 * @extends Factory<MeetingDecision>
 *
 * `decision_no` numbers decisions within a meeting; `decision_date` defaults to
 * now() rather than a random past date because an approved decision dated in the
 * future is a state the application would reject.
 */
class MeetingDecisionFactory extends Factory
{
    protected $model = MeetingDecision::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'agenda_id' => null,
            'discussion_id' => null,
            'decision_no' => 1,
            'decision_title' => fake()->sentence(5),
            'decision_description' => fake()->optional()->paragraph(),
            'decision_type' => 'noted',
            'decision_status' => 'active',
            'decision_date' => now()->toDateString(),
            'approved_by' => null,
            'effective_date' => null,
            'remarks' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function approvedBy(User $user): static
    {
        return $this->state(fn (): array => [
            'decision_status' => 'approved',
            'approved_by' => $user->id,
        ]);
    }
}
