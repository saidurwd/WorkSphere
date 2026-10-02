<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingParticipant;

/**
 * @extends Factory<MeetingParticipant>
 *
 * (meeting_id, user_id) is unique — a user is invited to a meeting once. The
 * `participant_type` default is `member` rather than something like `chair`,
 * because membership is what confers the visibility the policy checks.
 */
class MeetingParticipantFactory extends Factory
{
    protected $model = MeetingParticipant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'user_id' => User::factory(),
            'participant_type' => 'member',
            'attendance_status' => 'invited',
            'invited_at' => now(),
            'responded_at' => null,
            'joined_at' => null,
            'left_at' => null,
            'remarks' => null,
        ];
    }

    public function present(): static
    {
        return $this->state(fn (): array => [
            'attendance_status' => 'present',
            'responded_at' => now(),
            'joined_at' => now(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (): array => [
            'attendance_status' => 'declined',
            'responded_at' => now(),
        ]);
    }
}
