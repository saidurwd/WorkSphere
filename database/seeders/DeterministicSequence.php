<?php

namespace Database\Seeders;

/**
 * A reproducible source of "random" values for the volume seeders.
 *
 * `fake()` is not repeatable: a demo database seeded twice has different names,
 * different dates and different distributions, so a screenshot taken this morning
 * no longer matches the data this afternoon and a bug seen in the data cannot be
 * reproduced.
 *
 * `mt_srand()` would fix that but it resets global state, which leaks into every
 * other `rand()` in the process — including the ones Laravel uses for ids and
 * cache keys. This is a self-contained linear congruential generator instead: the
 * same seed always produces the same sequence, and nothing outside this object is
 * affected.
 *
 * The distribution is uniform, which is good enough for shaping a dataset and is
 * all that is claimed. It is not suitable for anything that needs real randomness.
 */
final class DeterministicSequence
{
    private int $state;

    /**
     * @param  int  $seed  Any integer. The same seed always yields the same sequence.
     */
    public function __construct(int $seed = 1)
    {
        // 0 is a fixed point of the generator's step, so it would return the
        // same number forever. Shift any seed onto a usable state.
        $this->state = ($seed % 2_147_483_647 + 2_147_483_647) % 2_147_483_647 ?: 1;
    }

    /**
     * The next value in the sequence.
     */
    public function next(): int
    {
        // Numerical Recipes' constants: a full 32-bit LCG.
        $this->state = ($this->state * 16_807) % 2_147_483_647;

        return $this->state;
    }

    /**
     * An integer between the bounds, inclusive.
     */
    public function between(int $min, int $max): int
    {
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }

        return $min + ($this->next() % ($max - $min + 1));
    }

    /**
     * An index into the list, chosen with the given relative weights.
     *
     * @param  list<int>  $weights  One weight per entry, e.g. [70, 20, 10].
     * @return int<0, max>
     */
    public function weighted(array $weights): int
    {
        $total = array_sum($weights);

        if ($total <= 0) {
            return 0;
        }

        $roll = $this->between(1, $total);

        foreach ($weights as $index => $weight) {
            $roll -= $weight;

            if ($roll <= 0) {
                return $index;
            }
        }

        return (int) array_key_last($weights);
    }

    /**
     * One of the values, chosen with the given relative weights.
     *
     * @template T
     *
     * @param  list<T>  $values
     * @param  list<int>|null  $weights
     * @return T
     */
    public function pick(array $values, ?array $weights = null): mixed
    {
        $index = $weights === null
            ? $this->between(0, count($values) - 1)
            : $this->weighted($weights);

        return $values[$index];
    }

    /**
     * Whether a percentage chance succeeded.
     */
    public function chance(int $percent): bool
    {
        return $this->between(1, 100) <= $percent;
    }

    /**
     * A subset of at most `$count` distinct values, keeping the source order.
     *
     * @template T
     *
     * @param  list<T>  $values
     * @return list<T>
     */
    public function sample(array $values, int $count): array
    {
        if ($count >= count($values)) {
            return array_values($values);
        }

        $picked = [];

        while (count($picked) < $count) {
            $index = $this->between(0, count($values) - 1);

            if (! array_key_exists($index, $picked)) {
                $picked[$index] = true;
            }
        }

        $picked = array_keys($picked);
        sort($picked);

        return array_values(array_map(fn (int $index): mixed => $values[$index], $picked));
    }
}
