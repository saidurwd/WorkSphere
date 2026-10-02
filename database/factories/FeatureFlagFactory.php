<?php

namespace Database\Factories;

use App\Models\FeatureFlag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FeatureFlag>
 *
 * A flag is CREATED OFF. That is the safe default for a fixture: a test that
 * wants the behaviour on says so explicitly, and a test that forgets does not
 * silently flip a rollout on for the whole suite.
 *
 * `key` is a random string rather than a Faker pool for the same reason as
 * `SettingFactory`: a unique column backed by a small pool exhausts over a long
 * run and the failure lands on an unrelated test.
 */
class FeatureFlagFactory extends Factory
{
    protected $model = FeatureFlag::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::headline(fake()->words(2, true));

        return [
            'key' => 'test.flag.'.Str::lower(Str::random(10)),
            'name' => $name,
            'description' => fake()->optional()->sentence(),
            'type' => 'boolean',
            'value' => true,
            'is_enabled' => false,
            'rollout_percentage' => 100,
            'variants' => null,
            'target_roles' => null,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function enabled(bool $enabled = true): static
    {
        return $this->state(fn (): array => ['is_enabled' => $enabled]);
    }

    /**
     * A canary: on, and visible to only `percent` of users.
     */
    public function rollout(int $percent): static
    {
        return $this->state(fn (): array => [
            'is_enabled' => true,
            'rollout_percentage' => $percent,
        ]);
    }

    /**
     * A flag aimed at specific roles.
     *
     * @param  list<string>  $slugs
     */
    public function targeting(array $slugs): static
    {
        return $this->state(fn (): array => [
            'target_roles' => $slugs,
        ]);
    }

    public function named(string $key): static
    {
        return $this->state(fn (): array => ['key' => $key]);
    }
}
