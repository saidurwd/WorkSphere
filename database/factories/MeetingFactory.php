<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingType;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
{
    protected $model = Meeting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startHour = fake()->numberBetween(8, 15);

        return [
            'meeting_no' => 'MTG-'.fake()->unique()->numerify('########'),
            'title' => fake()->sentence(4),
            'meeting_type_id' => MeetingType::factory(),
            'organizer_id' => User::factory(),
            'department_id' => Department::factory(),
            'location' => fake()->optional()->city(),
            'meeting_date' => now()->addDays(fake()->numberBetween(1, 14))->format('Y-m-d'),
            'start_time' => sprintf('%02d:00', $startHour),
            'end_time' => sprintf('%02d:00', $startHour + 1),
            'timezone' => 'UTC',
            'status' => 'scheduled',
            'priority' => fake()->randomElement(['normal', 'important', 'urgent']),
            'minutes_status' => 'draft',
        ];
    }

    public function organisedBy(User $user): static
    {
        return $this->state(fn (): array => ['organizer_id' => $user->id]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => 'completed',
            'minutes_status' => 'approved',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => ['status' => 'approved']);
    }
}
