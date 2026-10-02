<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 *
 * Both `name` and `slug` are unique. The slug is DERIVED from the name rather
 * than generated independently, because `Tag::findOrCreateByName()` does the same
 * thing — a factory that produced a name and an unrelated slug would model a tag
 * the application can never create.
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'slug' => Tag::slugify($name).'-'.Str::lower(Str::random(4)),
            'color' => fake()->randomElement(['#ef4444', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6']),
        ];
    }

    /**
     * A tag with an exact name, so `Tag::slugify()` is exercised by the fixture.
     */
    public function called(string $name, ?string $color = null): static
    {
        return $this->state(fn (): array => [
            'name' => $name,
            'slug' => Tag::slugify($name).'-'.Str::lower(Str::random(4)),
            'color' => $color,
        ]);
    }
}
