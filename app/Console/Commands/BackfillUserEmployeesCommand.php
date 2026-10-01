<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Console\Command;

/**
 * Links every user to an employee record — GAP-040.
 *
 * `users.employee_id` is nullable and, on the installs this runs against, mostly
 * NULL, while `employees` holds rows nobody is attached to. The two tables are
 * supposed to describe the same people, and today they only half do.
 *
 * The command is a REPORT by default and only writes with `--link`. That is
 * deliberate: a backfill that invents an employee record for every unlinked user
 * is a destructive act dressed as a convenience, and a report is enough to decide
 * whether it is wanted.
 *
 * Matching, in order of confidence:
 *   1. an unlinked employee with the SAME email — reliable;
 *   2. an employee with the SAME name and no user — probable but ambiguous, so it
 *      is only applied with `--link` and never guessed silently.
 *
 * Re-runnable: it only ever attaches users who have no employee yet.
 */
class BackfillUserEmployeesCommand extends Command
{
    protected $signature = 'users:backfill-employees
                            {--link : Actually write the links. Without it, this only reports.}
                            {--force : Link by name match as well as email, where the name is unambiguous.}';

    protected $description = 'Report (and optionally repair) users with no linked employee record.';

    public function handle(): int
    {
        $write = $this->option('link');
        $includeName = $this->option('force');

        $unlinked = User::query()->whereNull('employee_id')->orderBy('id')->get();

        if ($unlinked->isEmpty()) {
            $this->info('Every user already has an employee record.');

            return self::SUCCESS;
        }

        $byEmail = Employee::query()
            ->whereIn('email', $unlinked->pluck('email')->filter()->unique())
            ->get()
            ->keyBy(fn (Employee $employee): string => strtolower((string) $employee->email));

        $linked = 0;
        $matchedByName = 0;
        $orphans = 0;

        $rows = [];

        foreach ($unlinked as $user) {
            $employee = $byEmail->get(strtolower((string) $user->email));

            if ($employee === null && $includeName) {
                // Only where the name match is unambiguous — two employees with the
                // same name, or a name already attached to somebody else, is not a
                // match worth acting on.
                $candidates = Employee::query()
                    ->where('employee_name', $user->name)
                    ->whereDoesntHave('users')
                    ->limit(2)
                    ->get();

                if ($candidates->count() === 1) {
                    $employee = $candidates->first();
                    $matchedByName++;
                }
            }

            if ($employee === null) {
                $orphans++;

                $rows[] = [$user->id, $user->name, $user->email, '—'];

                continue;
            }

            $rows[] = [$user->id, $user->name, $user->email, $employee->employee_code.($write ? ' (linked)' : ' (available)')];

            if ($write) {
                $user->forceFill(['employee_id' => $employee->id])->saveQuietly();
                $linked++;
            }
        }

        $this->table(['User', 'Name', 'Email', 'Employee'], $rows);

        $this->newLine();

        if ($write) {
            $this->info(sprintf(
                'Linked %d user(s) by email and %d by name. %d still have no employee record.',
                $linked - $matchedByName,
                $matchedByName,
                $orphans,
            ));

            if ($orphans > 0) {
                $this->warn('The remaining users need an employee record created for them, or the constraint will keep being refused.');
            }

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Report only — nothing was written. %d user(s) could be linked, %d could not. Re-run with --link to apply.',
            $linked + $matchedByName,
            $orphans,
        ));

        return self::SUCCESS;
    }
}
