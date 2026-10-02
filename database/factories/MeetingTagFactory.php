<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Meetings\Models\MeetingTag;

/**
 * @extends Factory<MeetingTag>
 *
 * The LEGACY tag vocabulary, replaced by the shared `tags` / `taggables` pair in
 * Phase 7. `name` is unique here as well as on `tags`, and the two must not be
 * given the same value by accident — the dual-write test asserts the mapping, and
 * a fixture that collided first would fail for the wrong reason.
 */
class MeetingTagFactory extends Factory
{
    protected $model = MeetingTag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'color' => fake()->randomElement(['#ef4444', '#3b82f6', '#10b981']),
            'is_active' => true,
        ];
    }

    public function called(string $name, ?string $color = null): static
    {
        return $this->state(fn (): array => [
            'name' => $name.'-'.Str::lower(Str::random(4)),
            'color' => $color,
        ]);
    }
}
