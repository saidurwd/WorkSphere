<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The result of seeding the benchmark dataset: who the measurement is taken as, and
 * the records the detail pages point at.
 */
final class SeededData
{
    /**
     * @param  array<string, Model>  $records
     */
    public function __construct(
        public readonly User $viewer,
        public readonly array $records = [],
    ) {}

    public function record(string $name): Model
    {
        return $this->records[$name]
            ?? throw new \LogicException("The benchmark has no record named {$name}.");
    }
}
