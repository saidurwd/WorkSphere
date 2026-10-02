<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingRecurrence;

/**
 * @extends Factory<MeetingRecurrence>
 *
 * `start_date` is today and `next_occurrence` is tomorrow, so a fixture built from
 * this factory is one the occurrence generator would actually act on. A
 * `next_occurrence` in the past would make an occurrence-generation test pass by
 * generating nothing.
 */
class MeetingRecurrenceFactory extends Factory
{
    protected $model = MeetingRecurrence::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'recurrence_type' => 'weekly',
            'recurrence_interval' => 1,
            'day_of_week' => 1,
            'day_of_month' => null,
            'start_date' => now()->toDateString(),
            'end_date' => null,
            'occurrences' => null,
            'next_occurrence' => now()->addDay()->toDateString(),
            'is_active' => true,
        ];
    }

    public function exhausted(): static
    {
        return $this->state(fn (): array => [
            'occurrences' => 0,
            'is_active' => false,
        ]);
    }
}
