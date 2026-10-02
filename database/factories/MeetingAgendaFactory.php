<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingAgenda;

/**
 * @extends Factory<MeetingAgenda>
 *
 * `agenda_no` is the presentation order within the meeting and `sort_order` is the
 * display order. They are separate columns because a reordering that renumbers
 * would rewrite history; the factory keeps them equal, which is the common case.
 */
class MeetingAgendaFactory extends Factory
{
    protected $model = MeetingAgenda::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'agenda_no' => 1,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'presented_by' => null,
            'estimated_minutes' => fake()->randomElement([5, 10, 15, 30]),
            'status' => 'pending',
            'sort_order' => 1,
        ];
    }

    public function presentedBy(User $user): static
    {
        return $this->state(fn (): array => ['presented_by' => $user->id]);
    }
}
