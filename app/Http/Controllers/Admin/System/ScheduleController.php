<?php

namespace App\Http\Controllers\Admin\System;

use App\Console\WorkSphereSchedule;
use App\Http\Controllers\Controller;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
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
                ->map(fn (Event $event): array => $this->row($event))
                ->sortBy('next_run')
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Event $event): array
    {
        $missing = [];

        foreach (self::PROTECTIONS as $method) {
            if ($this->lacks($event, $method)) {
                $missing[] = $method;
            }
        }

        $next = $event->nextRunDate();

        return [
            'description' => $this->describe($event),
            'command' => $this->command($event),
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
     * What to show as the event's name.
     *
     * `Event::$description` is the WRONG field for a scheduled command. Laravel
     * wraps `Schedule::command('reminders:dispatch')` in a callback, so the
     * description is the literal string `Closure` unless the caller overrides it —
     * and `WorkSphereSchedule` does not. Reading it produced a table of identical
     * "Closure" rows, which is why this screen could not show what it was listing.
     *
     * The command string is the real identifier, so that is what is shown, with the
     * description appended only when it says something the command does not.
     */
    protected function describe(Event $event): string
    {
        $command = $this->command($event);
        $description = trim((string) ($event->description ?? ''));

        // 'Closure' is Laravel's placeholder, not a description.
        if ($description === '' || $description === 'Closure') {
            return $command;
        }

        return $command === '' ? $description : $command.' — '.$description;
    }

    /**
     * The Artisan command an event runs, without the interpreter prefix.
     */
    protected function command(Event $event): string
    {
        $command = trim((string) ($event->command ?? ''));

        if ($command === '') {
            return '';
        }

        /**
         * Strip the interpreter, not just the first word.
         *
         * `Application::formatCommandString()` shell-quotes the PHP binary, so the
         * raw value is `'…/Herd/bin/php' 'artisan' reminders:dispatch` — and a
         * path containing a space (Herd's, on macOS) defeats a leading `\S+` match.
         * Anchoring on `artisan` and taking everything after it is what actually
         * identifies the command.
         */
        if (preg_match("#artisan'?\s+(.+)$#", $command, $matches) === 1) {
            return trim($matches[1]);
        }

        return $command;
    }

    /**
     * Whether an event lacks a given protection.
     *
     * Read as PROPERTIES, never by calling the setters: calling
     * `withoutOverlapping()` here would add the guard the screen claims to be
     * checking for, and the check would then pass for every entry.
     *
     * `withoutOverlapping` is two facts, not one: the flag AND the mutex object it
     * creates. A flag with no mutex does nothing, so both are required.
     *
     * @param  string  $protection  One of {@see self::PROTECTIONS}'s values.
     */
    protected function lacks(Event $event, string $protection): bool
    {
        return match ($protection) {
            'withoutOverlapping' => ! $event->withoutOverlapping || $event->mutex === null,
            // A boolean in this framework version, NOT a `$server` array. Reading a
            // property that does not exist made every entry report "missing",
            // which is worse than not checking at all.
            'onOneServer' => ! (bool) $event->onOneServer,
            'timezone' => $event->timezone === null,
            default => false,
        };
    }
}
