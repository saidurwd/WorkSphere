<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Meetings\Models\MeetingTemplate;
use Modules\Meetings\Models\MeetingType;

/**
 * @extends Factory<MeetingTemplate>
 *
 * A template that Phase 8 finally wired to routes. It is INACTIVE by default: a
 * fixture that is active by default would appear in the template picker, and a
 * test asserting "this template is scheduled from" would pass because the template
 * was listed rather than because it was selected.
 */
class MeetingTemplateFactory extends Factory
{
    protected $model = MeetingTemplate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(3),
            'meeting_type_id' => MeetingType::factory(),
            'description' => fake()->optional()->paragraph(),
            'default_duration' => fake()->randomElement([30, 45, 60, 90]),
            'default_location' => fake()->randomElement(['Room 1', 'Room 2', 'Boardroom']),
            'default_priority' => 'normal',
            'is_active' => false,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['is_active' => true]);
    }
}
