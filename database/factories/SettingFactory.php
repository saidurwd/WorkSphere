<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 *
 * `key` is unique and is derived from the row's own group and label rather than a
 * Faker pool, because a pool of `setting.key.####` runs out over a long suite and
 * the exhaustion surfaces as an unrelated test failing.
 *
 * The declared `type` and the value are kept consistent: an integer setting whose
 * fixture holds a string is precisely the mismatch `FactoryVocabularyTest` exists
 * to catch, so a factory must never manufacture one.
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $label = 'Setting '.ucfirst(fake()->word());

        return [
            'key' => 'test.'.fake()->unique()->slug(2).'.'.fake()->unique()->numerify('####'),
            'value' => fake()->word(),
            'type' => 'string',
            'group' => 'General',
            'label' => $label,
            'description' => fake()->optional()->sentence(),
            'is_encrypted' => false,
            'updated_by' => null,
        ];
    }

    public function integer(int $value = 300): static
    {
        return $this->state(fn (): array => [
            'value' => $value,
            'type' => 'integer',
            'label' => 'Integer setting',
        ]);
    }

    public function boolean(bool $value = true): static
    {
        return $this->state(fn (): array => [
            'value' => $value,
            'type' => 'boolean',
            'label' => 'Boolean setting',
        ]);
    }

    /**
     * A setting the application actually declares, so `Settings::set()` accepts it.
     */
    public function declared(string $key, mixed $value = null): static
    {
        return $this->state(function () use ($key, $value): array {
            $definition = \App\Services\Settings::DEFAULTS[$key] ?? null;

            return $definition === null ? [] : [
                'key' => $key,
                'value' => $value ?? $definition['default'],
                'type' => $definition['type'],
                'group' => $definition['group'],
                'label' => $definition['label'],
                'description' => $definition['description'],
            ];
        });
    }
}
