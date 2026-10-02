<?php

namespace App\Services;

use App\Models\FeatureFlag;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves feature flags for a user.
 *
 * THE ORDER MATTERS and it is the whole design:
 *
 *   1. Unknown key          -> the DEFAULT. Absent, not off.
 *   2. Not targeted         -> the DEFAULT. Never "off by targeting", so a flag
 *                              aimed at administrators does not reveal itself to
 *                              anyone else.
 *   3. Not enabled          -> the DEFAULT.
 *   4. Weighted variants    -> the user's bucket decides, deterministically.
 *   5. Percentage rollout   -> the user's bucket decides, deterministically.
 *   6. Otherwise            -> the flag's value.
 *
 * **AN ABSENT FLAG IS NOT A DISABLED FLAG.** `FeatureFlags::enabled('new-thing')`
 * returns false for a flag nobody created, and the DEFAULT is used instead — so a
 * flag deleted after its rollout does not silently switch off a code path that has
 * shipped. Each call site therefore states a default:
 *
 *     FeatureFlags::enabled('meetings.templates', default: true)
 *
 * **THE BUCKET IS STABLE.** `crc32` of the key and the user id, not a random
 * number. A flag that answers differently on two consecutive requests from the
 * same person cannot be debugged, cannot be support-reproduced, and makes a
 * canary's error rate meaningless.
 */
class FeatureFlags
{
    private const CACHE_KEY = 'system:feature-flags:v';

    /**
     * Every flag as stored, keyed by key.
     *
     * @return array<string, array<string, mixed>>
     */
    public function definitions(): array
    {
        // Arrays of attributes, never `FeatureFlag` models.
        //
        // `config('cache.serializable_classes')` is `false`, so the database store
        // unserializes with `allowed_classes => false` and every cached object comes
        // back as `__PHP_Incomplete_Class`. The failure here is quieter and worse
        // than a thrown error: `typed()` reads `$flag->type` and `$flag->value`, both
        // of which are null on an incomplete class, so every flag silently evaluated
        // to its caller's DEFAULT — a feature flag that was on reported as off, with
        // nothing logged and nothing to grep for.
        //
        // Invisible to the suite for the same reason as the reference lists:
        // `phpunit.xml` sets `CACHE_STORE=array`, and the array store returns the
        // same object it was given, never unserializing anything.
        $definitions = Cache::remember(
            self::CACHE_KEY,
            300,
            fn (): array => FeatureFlag::query()->get()
                ->mapWithKeys(fn (FeatureFlag $flag): array => [
                    // `getAttributes()`, NOT `attributesToArray()`.
                    //
                    // `attributesToArray()` applies the model's casts, so `value`
                    // and `target_roles` come back already json_decoded into PHP
                    // arrays. Feeding those back through `newFromBuilder()` casts
                    // them a second time — `json_decode(array)` — and every flag
                    // with targeting threw. The cached form has to be the RAW
                    // column values, because the model is what applies casts, once.
                    $flag->key => $flag->getAttributes(),
                ])
                ->all(),
        );

        // Rehydrated on read, so the cached entry is plain scalars and a flag
        // edited in the database is picked up the moment the cache is dropped.
        $flags = [];

        foreach ($definitions as $key => $attributes) {
            if ($attributes instanceof FeatureFlag) {
                $flags[$key] = $attributes;

                continue;
            }

            $flag = (new FeatureFlag)->newFromBuilder((array) $attributes);
            $flag->setConnection((new FeatureFlag)->getConnection()->getName());
            $flag->setTable((new FeatureFlag)->getTable());

            $flags[$key] = $flag;
        }

        return $flags;
    }

    /**
     * The cached value as written, before rehydration.
     *
     * Exposed so a test can assert the STORED shape rather than inferring it from
     * `definitions()`, which rehydrates and would therefore pass whether the cache
     * held models or arrays. The distinction is the whole point: a model in here
     * unserializes to `__PHP_Incomplete_Class`, and the bug that follows is silent.
     *
     * @return array<string, array<string, mixed>>
     */
    public function rawPayload(): array
    {
        return Cache::get(self::CACHE_KEY) ?? [];
    }

    /**
     * The resolved value of a flag for a user.
     *
     * @param  mixed  $default  Used when the flag does not exist or does not apply.
     *                          Stated per call site rather than defaulted to `false`,
     *                          because "the flag is missing" and "the flag is off"
     *                          are different answers.
     */
    public function value(string $key, ?User $user = null, mixed $default = false): mixed
    {
        $flag = $this->definitions()[$key] ?? null;

        if (! $flag instanceof FeatureFlag) {
            return $default;
        }

        if (! $flag->is_enabled || ! $flag->targets($user)) {
            return $default;
        }

        return $this->resolve($flag, $user);
    }

    public function enabled(string $key, ?User $user = null, bool $default = false): bool
    {
        return (bool) $this->value($key, $user, $default);
    }

    /**
     * Create or update a flag.
     */
    public function put(array $attributes, ?int $actorId = null): FeatureFlag
    {
        $flag = FeatureFlag::query()->updateOrCreate(
            ['key' => $attributes['key']],
            [
                'name' => $attributes['name'] ?? $attributes['key'],
                'description' => $attributes['description'] ?? null,
                'type' => $attributes['type'] ?? 'boolean',
                'value' => $attributes['value'] ?? true,
                'is_enabled' => $attributes['is_enabled'] ?? false,
                'rollout_percentage' => $attributes['rollout_percentage'] ?? 100,
                'variants' => $attributes['variants'] ?? null,
                'target_roles' => $attributes['target_roles'] ?? null,
                'created_by' => $flag->created_by ?? $actorId,
                'updated_by' => $actorId,
            ],
        );

        $this->flush();

        return $flag;
    }

    public function delete(string $key): void
    {
        FeatureFlag::query()->where('key', $key)->delete();

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Decide which value this user gets.
     */
    protected function resolve(FeatureFlag $flag, ?User $user): mixed
    {
        $bucket = $flag->bucketFor($user);

        $variants = $flag->variants;

        if (is_array($variants) && $variants !== []) {
            return $this->weighted($variants, $bucket, $flag);
        }

        // A percentage below 100 means "this user is in the canary or not". At 100
        // every user is in, which is the ordinary case and worth no branching.
        if ($flag->rollout_percentage < 100 && $bucket >= $flag->rollout_percentage) {
            return false;
        }

        return $this->typed($flag);
    }

    /**
     * Pick from a weighted split.
     *
     * The cumulative walk means a bucket always lands in exactly one bucket, and
     * a set of weights that does not total 100 simply leaves the remainder on the
     * flag's own value rather than falling off the end.
     *
     * @param  list<array{value: mixed, weight: int}>  $variants
     */
    protected function weighted(array $variants, int $bucket, FeatureFlag $flag): mixed
    {
        $cursor = 0;

        foreach ($variants as $variant) {
            $cursor += max(0, (int) ($variant['weight'] ?? 0));

            if ($bucket < $cursor) {
                return $variant['value'] ?? $this->typed($flag);
            }
        }

        return $this->typed($flag);
    }

    protected function typed(FeatureFlag $flag): mixed
    {
        return match ($flag->type) {
            'integer' => (int) $flag->value,
            'float' => (float) $flag->value,
            'string' => (string) $flag->value,
            'json', 'array' => $flag->value,
            default => (bool) $flag->value,
        };
    }
}
