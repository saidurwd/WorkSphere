<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 *
 * `roles.slug` is unique, so it is random rather than `Role 1`.
 */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = ucfirst(fake()->unique()->jobTitle());

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(6)),
            'description' => fake()->optional()->sentence(),
        ];
    }

    /**
     * A named role, for tests that need a specific slug.
     */
    public function named(string $slug, ?string $name = null): static
    {
        return $this->state(fn (): array => [
            'slug' => $slug,
            'name' => $name ?? ucfirst(str_replace('-', ' ', $slug)),
        ]);
    }
}
