<?php

namespace Modules\Todos\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Narrows a time-based notification query to work that has not already been
 * warned about for the given day.
 *
 * The `dedupe_key` on `notification_logs` is the correctness guarantee — a
 * second send cannot be recorded. This is the cheap half: without it a scheduler
 * that fires hourly queues the same To-Do on every tick, filling the queue with
 * jobs that will each be discarded on execution. `last_reminded_at` is written by
 * the job only after a successful delivery, so this filter can never suppress a
 * notification that has not actually gone out.
 */
final class NotifiedFilter
{
    /**
     * @param  Builder<Model>  $query
     */
    public static function apply(Builder $query, string $day): Builder
    {
        return $query->where(function (Builder $inner) use ($day): void {
            $inner->whereNull('last_reminded_at')
                ->orWhereDate('last_reminded_at', '<', $day);
        });
    }
}
