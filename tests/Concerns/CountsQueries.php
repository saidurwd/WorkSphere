<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

/**
 * Counts the queries a callback issues.
 *
 * Several suites need this — the N+1 detector, the performance baseline, the
 * reference-cache tests — and each had grown its own copy. They differed in a way
 * that mattered: some reset a counter, some did not, and one registered a listener
 * that kept counting into a stale variable. Sharing one implementation means the
 * comparisons are between pages, not between harnesses.
 *
 * The listener is REMOVED afterwards. `DB::listen` attaches to the connection, and
 * a listener left behind keeps firing for the rest of the test — harmless while it
 * increments a variable nobody reads, but it means the connection is no longer
 * being observed by only one thing, which is exactly the sort of thing that makes
 * a later measurement quietly wrong.
 */
trait CountsQueries
{
    /**
     * @param  callable(): mixed  $callback
     */
    protected function countQueries(callable $callback): int
    {
        $count = 0;

        $listener = function () use (&$count): void {
            $count++;
        };

        DB::listen($listener);

        try {
            $callback();
        } finally {
            $this->forgetQueryListener();
        }

        return $count;
    }

    /**
     * Every query issued during a callback, in order.
     *
     * @param  callable(): mixed  $callback
     * @return list<array{sql: string, bindings: array<mixed>, time: float}>
     */
    protected function captureQueries(callable $callback): array
    {
        $captured = [];

        $listener = function ($query) use (&$captured): void {
            $captured[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time,
            ];
        };

        DB::listen($listener);

        try {
            $callback();
        } finally {
            $this->forgetQueryListener();
        }

        return $captured;
    }

    /**
     * How many captured queries match a pattern.
     *
     * @param  list<array{sql: string, bindings: array<mixed>, time: float}>  $captured
     */
    protected function countMatching(array $captured, string $pattern): int
    {
        return count(array_filter(
            $captured,
            fn (array $query): bool => preg_match($pattern, $query['sql']) === 1,
        ));
    }

    /**
     * Detach every `QueryExecuted` listener registered during a measurement.
     */
    private function forgetQueryListener(): void
    {
        DB::connection()->getEventDispatcher()?->forget('Illuminate\Database\Events\QueryExecuted');
    }
}
