<?php

namespace App\Console\Commands;

use App\Services\SchemaInspector;
use Illuminate\Console\Command;

/**
 * `php artisan doctor:schema` — do the models and THIS database agree?
 *
 * The test suite cannot answer that. It migrates a fresh database from the same
 * migration files the application ships, so a column added by EDITING a migration
 * that had already run is present in the test database and absent everywhere else.
 * That is how `roles.description` passed more than a thousand tests while every
 * role save failed against MySQL.
 *
 * This command runs against whichever database is configured — development,
 * staging or production — and is therefore the only place the disagreement can be
 * seen. It is a REPORT, not a repair: it tells an operator what is missing so they
 * can write the migration that adds it, because guessing at the intended type on a
 * live database is how a schema drifts in a second direction.
 *
 * Exit code is non-zero when something disagrees, so it can gate a deploy.
 */
class SchemaDoctorCommand extends Command
{
    protected $signature = 'doctor:schema
                            {--json : Machine-readable output, for a deploy gate}';

    protected $description = 'Compare the columns every model declares against the columns this database actually has.';

    public function handle(SchemaInspector $inspector): int
    {
        $report = $inspector->report();

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            return $report['healthy'] ? self::SUCCESS : self::FAILURE;
        }

        $this->line(sprintf(
            '<info>%d models checked against %s.</info>',
            $report['models'],
            config('database.default'),
        ));

        if ($report['healthy']) {
            $this->line('  <fg=green>Every declared column exists.</>');

            return self::SUCCESS;
        }

        $this->error(sprintf('%d mismatch(es) between the models and this database:', count($report['mismatches'])));
        $this->newLine();

        foreach ($report['mismatches'] as $mismatch) {
            $this->line(sprintf('  <fg=yellow>%s</>  %s.%s', $mismatch['attribute'], $mismatch['table'], ''));
            $this->line('    '.$mismatch['model']);
            $this->line('    <fg=gray>'.$mismatch['problem'].'</>');
            $this->newLine();
        }

        $this->warn(
            'A column the model declares but the table lacks is usually a migration that was EDITED '
            .'after it had already run here. Laravel records a migration by name and will never '
            .'re-run it, so the fix is a NEW migration — not another edit to the create-migration.',
        );

        return self::FAILURE;
    }
}
