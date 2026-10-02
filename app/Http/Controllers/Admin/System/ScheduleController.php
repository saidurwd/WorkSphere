<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Console\WorkSphereSchedule;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Scheduled-task visibility.
 *
 * READ FROM THE APPLICATION'S OWN SCHEDULE DEFINITION, not from a table. The
 * alternative — recording what was scheduled — produces a second source of truth
 * that drifts the first time someone edits `WorkSphereSchedule` and forgets to
 * update the record. Here the list is what WILL RUN, by construction.
 *
 * `next_run_date()` is evaluated against the schedule's own timezone. A schedule
 * entry with no timezone shows a time in the server's zone, which is how a
 * 09:00 reminder ends up firing at 03:00 — the exact failure `WorkSphereSchedule`
 * documents and guards against.
 *
 * The screen also names, for each entry, whether it carries the four protections
 * that class insists on. An entry missing one is shown as such rather than
 * quietly listed, because the absence is invisible until the hour it matters.
 */
class ScheduleController extends Controller
{
    /** The guarantees `WorkSphereSchedule` applies to every entry. */
    private const PROTECTIONS = [
        'withoutOverlapping' => 'withoutOverlapping',
        'onOneServer' => 'onOneServer',
        'timezone' => 'timezone',
    ];

    public function index(): View
    {
        $this->authorize('system.schedule');

        $schedule = new Schedule(now()->timezone(config('app.timezone')));

        (new WorkSphereSchedule($schedule))->register();

        return view('admin.system.schedule', [
            'timezone' => (string) config('app.timezone'),
            'events' => collect($schedule->events())
                ->map(fn (Event $event): array => $this->describe($event))
                ->sortBy('next_run')
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function describe(Event $event): array
    {
        $missing = [];

        foreach (self::PROTECTIONS as $method) {
            if ($this->lacks($event, $method)) {
                $missing[] = $method;
            }
        }

        $next = $event->nextRunDate();

        return [
            'description' => $event->description ?? '(no description)',
            'expression' => $event->getExpression(),
            // Null for a one-shot event that has already run. Rendering "never"
            // would be a claim; null is a fact.
            'next_run' => $next?->toIso8601String(),
            'next_run_human' => $next?->diffForHumans(),
            'timezone' => $event->timezone ?? (string) config('app.timezone'),
            'without_overlapping' => ! $this->lacks($event, 'withoutOverlapping'),
            'on_one_server' => ! $this->lacks($event, 'onOneServer'),
            'has_timezone' => ! $this->lacks($event, 'timezone'),
            'missing_protections' => $missing,
        ];
    }

    /**
     * Whether an event lacks a given protection.
     *
     * `withoutOverlapping()` adds a `Mutex`, so its absence is the absence of that
     * object. `onOneServer()` and `timezone()` set plain properties. Reading them
     * as properties rather than by calling the setter is what makes this a check
     * rather than a mutation.
     */
    protected function lacks(Event $event, string $protection): bool
    {
        return match ($protection) {
            'withoutOverlapping' => $event->mutex === null,
            'onOneServer' => empty($event->server),
            'timezone' => $event->timezone === null,
            default => false,
        };
    }
}
