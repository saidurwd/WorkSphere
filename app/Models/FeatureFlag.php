<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A behaviour that can be turned on, off, or part-way on without a deploy.
 *
 * The intent differs from {@see Setting} and the difference is worth preserving: a
 * setting is a value an operator chose and stays; a flag is a behaviour being
 * rolled out and is expected to be deleted when the rollout finishes.
 *
 * `variants` and `rollout_percentage` are alternative ways to express the same
 * thing — a weighted split for more than two groups, a percentage for a simple
 * canary. `variants` wins when both are present, and `App\Services\FeatureFlags`
 * is the only place that decides.
 *
 * @property int $id
 * @property string $key
 * @property string $name
 * @property string|null $description
 * @property string $type
 * @property array<mixed>|null $value
 * @property bool $is_enabled
 * @property int $rollout_percentage
 * @property list<array{value: mixed, weight: int}>|null $variants
 * @property list<string>|null $target_roles
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class FeatureFlag extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'key',
        'name',
        'description',
        'type',
        'value',
        'is_enabled',
        'rollout_percentage',
        'variants',
        'target_roles',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'variants' => 'array',
            'target_roles' => 'array',
            'is_enabled' => 'boolean',
            'rollout_percentage' => 'integer',
        ];
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true);
    }

    /**
     * Whether this flag is aimed at the given user at all.
     *
     * Targetting is checked before rollout, so a flag aimed at administrators is
     * never *resolved* for anyone else — as opposed to being resolved and then
     * discarded, which leaks the fact that the flag exists and what it holds.
     */
    public function targets(?User $user): bool
    {
        $roles = $this->target_roles;

        if ($roles === null || $roles === []) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        foreach ($roles as $slug) {
            if ($user->hasRole($slug)) {
                return true;
            }
        }

        return false;
    }

    /**
     * A stable bucket in `0..99` for a user, for percentage rollouts.
     *
     * `crc32` rather than `random_int`: the bucket must be the SAME on every
     * request from the same user, or a canary flips on and off for one person and
     * the rollout becomes impossible to reason about.
     */
    public function bucketFor(?User $user): int
    {
        $identity = $user?->getAuthIdentifier() ?? 'anonymous';

        return crc32($this->key.':'.$identity) % 100;
    }
}
