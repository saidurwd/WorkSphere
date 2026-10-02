<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingMinutesApproval;

/**
 * @extends Factory<MeetingMinutesApproval>
 *
 * `step_no` is the position in the approval CHAIN, so the default is 1 and a
 * later step is built with `step()`. `approver_id` is deliberately NULL: Phase 2
 * softened this column to nullOnDelete, so an unassigned step is a state rows
 * really are in, and a fixture that always filled it would hide that.
 */
class MeetingMinutesApprovalFactory extends Factory
{
    protected $model = MeetingMinutesApproval::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'step_no' => 1,
            'approver_id' => null,
            'status' => 'pending',
            'comments' => null,
            'action_at' => null,
        ];
    }

    public function step(int $step): static
    {
        return $this->state(fn (): array => ['step_no' => $step]);
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (): array => ['approver_id' => $user->id]);
    }

    public function approved(User $user): static
    {
        return $this->state(fn (): array => [
            'approver_id' => $user->id,
            'status' => 'approved',
            'action_at' => now(),
        ]);
    }
}
