<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;

/**
 * The application schedule — GAP-046.
 *
 * Lives in a class rather than a closure in `bootstrap/app.php` for two reasons.
 * First, `bootstrap/app.php` is not unit-testable: the schedule is bound into the
 * container during booting, and resolving Artisan re-binds the `Schedule`
 * singleton, so anything reading it lazily sees a set that depends on which test
 * ran first. Second, a closure is reviewed far less carefully than a named class
 * with docblocks, and this file is the one place where a mistake stays invisible
 * until a given hour on a given day.
 *
 * Every entry gets the same four protections, because each closes a distinct
 * failure:
 *
 *   withoutOverlapping() — a run that outlives its interval must not have a
 *                           second run piled on top of it.
 *   onOneServer()          — more than one scheduler (a cluster, a queue worker
 *                           promoted to cron) must not each run the same job.
 *   timezone()             — `dailyAt('09:00')` is evaluated in this app's
 *                           timezone. Without it the meaning depends on the
 *                           server's, which is how a reminder fires six hours
 *                           early.
 *   a real frequency       — an entry that never fires is not a working entry.
 */
class WorkSphereSchedule
{
    public function __construct(private readonly Schedule $schedule) {}

    public function register(): void
    {
        $timezone = config('app.timezone');

        // The shared reminder pipeline. Every minute, because a reminder can be
        // set for any minute; chunked and idempotent inside the command, so a
        // double fire costs nothing.
        $this->everyMinute('reminders:dispatch');

        // To-Do notifications. Daily is right for these: they warn about a state
        // that lasts for hours, not about a moment that passes.
        $this->daily('todos:overdue', '09:00');
        $this->daily('todos:due-soon', '08:00');

        // Recurrence backstop for a series whose occurrence fell past its date
        // without a completion — the case completion-time generation cannot cover.
        $this->daily('todos:generate', '02:00');

        // The five legacy module commands stay scheduled until Phase 8 confirms
        // the reminder pipeline covers their parity. They are kept callable, not
        // deleted (TODO-MODULE-SPECIFICATION §6, task 7).
        $this->daily('obligations:process', '08:00');
        $this->daily('actions:remind', '09:00');
        $this->daily('actions:overdue', '09:30');
        $this->daily('tasks:remind', '09:00');
        $this->daily('tasks:overdue', '09:30');
    }

    private function everyMinute(string $command): void
    {
        $this->schedule->command($command)
            ->everyMinute()
            ->withoutOverlapping()
            ->onOneServer()
            ->timezone(config('app.timezone'));
    }

    private function daily(string $command, string $at): void
    {
        $this->schedule->command($command)
            ->dailyAt($at)
            ->withoutOverlapping()
            ->onOneServer()
            ->timezone(config('app.timezone'));
    }
}
