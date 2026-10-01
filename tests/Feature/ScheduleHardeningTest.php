<?php

namespace Tests\Feature;

use App\Console\WorkSphereSchedule;
use Illuminate\Console\Scheduling\Event as ScheduledEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * GAP-046. The schedule is the one part of the application where a mistake stays
 * invisible until 09:00 on the day, so it is asserted structurally rather than by
 * reading `bootstrap/app.php` and nodding.
 *
 * The schedule is registered against a **fresh** `Schedule` instance rather than
 * the container's. Resolving Artisan re-binds the `Schedule` singleton and the
 * booting callback that populates it only fires once per process, so a schedule
 * read from the container depends on which test ran first — a real trap hit
 * while building this. `WorkSphereSchedule` is now a class precisely so it can be
 * exercised in isolation; `ScheduleHardeningTest` proves it is also what the
 * application actually registers.
 *
 * Each protection closes a distinct failure:
 *
 *   withoutOverlapping() — a run that outlives its interval must not have a
 *                           second run piled on top of it.
 *   onOneServer()          — more than one scheduler must not each run the job.
 *   timezone()             — `dailyAt('09:00')` is evaluated in this app's
 *                           timezone; without it the meaning depends on the
 *                           server's, which is how a reminder fires six hours
 *                           early.
 */
class ScheduleHardeningTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The events this application actually registers, from a fresh Schedule.
     *
     * @return Collection<int, ScheduledEvent>
     */
    private function events(): Collection
    {
        $schedule = new Schedule($this->app->make('cache'));

        (new WorkSphereSchedule($schedule))->register();

        return collect($schedule->events())
            ->filter(fn ($event): bool => is_string($event->command) && $event->command !== '')
            ->values();
    }

    public function test_the_schedule_has_entries(): void
    {
        $this->assertGreaterThan(
            0,
            $this->events()->count(),
            'No scheduled commands were found — is the schedule being registered at all?',
        );
    }

    public function test_every_scheduled_command_is_without_overlapping(): void
    {
        $offenders = $this->events()
            ->reject(fn (ScheduledEvent $event): bool => $event->withoutOverlapping === true)
            ->map(fn (ScheduledEvent $event): string => $this->commandName($event))
            ->values()
            ->all();

        $this->assertSame(
            [],
            $offenders,
            "A run that outlives its interval would have a second run piled on it:\n".implode("\n", $offenders),
        );
    }

    public function test_every_scheduled_command_is_on_one_server(): void
    {
        $offenders = $this->events()
            ->reject(fn (ScheduledEvent $event): bool => $event->onOneServer === true)
            ->map(fn (ScheduledEvent $event): string => $this->commandName($event))
            ->values()
            ->all();

        $this->assertSame(
            [],
            $offenders,
            "With more than one scheduler each job would run once per host:\n".implode("\n", $offenders),
        );
    }

    public function test_every_scheduled_command_pins_the_business_timezone(): void
    {
        $offenders = $this->events()
            ->reject(fn (ScheduledEvent $event): bool => $event->timezone === config('app.timezone'))
            ->map(fn (ScheduledEvent $event): string => $this->commandName($event).' => '.var_export($event->timezone, true))
            ->values()
            ->all();

        $this->assertSame(
            [],
            $offenders,
            "`dailyAt('09:00')` would be evaluated in the server's timezone:\n".implode("\n", $offenders),
        );
    }

    public function test_every_scheduled_command_exists_and_has_a_real_frequency(): void
    {
        $registered = array_keys(Artisan::all());
        $problems = [];

        foreach ($this->events() as $event) {
            $name = $this->commandName($event);

            if ($name !== '' && ! in_array($name, $registered, true)) {
                $problems[] = "{$name} is scheduled but not registered — the entry would fail every night";
            }

            if ($event->expression === '') {
                $problems[] = "{$name} has an empty cron expression";
            }
        }

        $this->assertSame([], $problems, implode("\n", $problems));
    }

    public function test_the_reminder_pipeline_runs_every_minute(): void
    {
        $dispatcher = $this->events()
            ->first(fn (ScheduledEvent $event): bool => $this->commandName($event) === 'reminders:dispatch');

        $this->assertNotNull($dispatcher, 'reminders:dispatch is not scheduled.');

        $this->assertSame(
            '* * * * *',
            $dispatcher->expression,
            'A reminder can be set for any minute, so the dispatcher must run every minute.',
        );
    }

    public function test_the_new_runtime_commands_are_scheduled(): void
    {
        $scheduled = $this->events()
            ->map(fn (ScheduledEvent $event): string => $this->commandName($event))
            ->all();

        foreach (['reminders:dispatch', 'todos:overdue', 'todos:due-soon', 'todos:generate'] as $command) {
            $this->assertContains($command, $scheduled, "{$command} is registered but not scheduled.");
        }
    }

    public function test_the_five_legacy_commands_remain_scheduled_and_callable(): void
    {
        // Phase 6 keeps them until Phase 8 confirms parity; dropping them now would
        // silently stop obligations and task reminders.
        $scheduled = $this->events()
            ->map(fn (ScheduledEvent $event): string => $this->commandName($event))
            ->all();

        $registered = array_keys(Artisan::all());

        foreach (['obligations:process', 'actions:remind', 'actions:overdue', 'tasks:remind', 'tasks:overdue'] as $command) {
            $this->assertContains($command, $registered, "{$command} is no longer callable.");
            $this->assertContains($command, $scheduled, "{$command} is registered but no longer scheduled.");
        }
    }

    public function test_the_business_timezone_is_plus_six(): void
    {
        // Phase 6 moved the app from UTC, where `dailyAt('09:00')` meant 09:00 UTC
        // and a reminder fired three hours before the office opened.
        $this->assertSame('Asia/Dhaka', config('app.timezone'));

        $this->assertSame(
            '+06:00',
            (new \DateTime('now', new \DateTimeZone(config('app.timezone'))))->format('P'),
        );
    }

    public function test_the_legacy_delivery_log_tables_are_untouched(): void
    {
        // Consolidating the *delivery* logs is Phase 7/8 work. The administrative
        // screens still read them, so Phase 6 must not drop them.
        foreach (['task_notification_logs', 'meeting_notification_logs', 'notification_logs'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "{$table} was dropped before the Phase 8 consolidation.");
        }
    }

    /**
     * The bare command name from an event, e.g. `todos:overdue`.
     *
     * Matched against the registered list rather than parsed: `->command()` holds
     * an escaped absolute interpreter path, so extracting the name from the string
     * is guesswork.
     */
    private function commandName(ScheduledEvent $event): string
    {
        $line = (string) $event->command;

        $found = array_values(array_filter(
            array_keys(Artisan::all()),
            static fn (string $name): bool => str_contains($line, $name),
        ));

        usort($found, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return $found[0] ?? trim(explode(' ', $line)[0]);
    }
}
