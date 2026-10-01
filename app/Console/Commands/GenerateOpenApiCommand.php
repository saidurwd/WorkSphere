<?php

namespace App\Console\Commands;

use App\Http\OpenApiGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Writes the generated OpenAPI 3.1 document to disk.
 *
 * The document is generated, never committed: a checked-in spec drifts from the
 * routes and nothing notices, because a YAML file has no compiler. The command
 * exists for the two cases a live endpoint does not cover — handing a file to a
 * client team, and diffing the contract in CI.
 *
 * `--check` is the CI mode: it fails when the committed file differs from what the
 * code would produce. That is only meaningful if a copy IS committed, which is the
 * one sanctioned exception to "never commit it" — the file is then a reviewed
 * artefact rather than a hand-maintained one.
 */
class GenerateOpenApiCommand extends Command
{
    protected $signature = 'api:openapi
                            {--output=public/openapi.json : Where to write the document}
                            {--check : Verify the committed document matches the code; fail if not}';

    protected $description = 'Generate the OpenAPI 3.1 document from the routes, Form Requests and API Resources.';

    public function handle(OpenApiGenerator $generator): int
    {
        // `base_path()` on an already-absolute path would prepend the project root, so
        // `--output=/tmp/spec.json` has to be honoured as given.
        $requested = (string) $this->option('output');

        $path = str_starts_with($requested, '/') ? $requested : base_path($requested);

        // Pretty-printed, because this file exists to be read by a human at some
        // point. The generator's array output is stable across runs, which is what
        // makes `--check` meaningful rather than noise.
        $json = json_encode(
            $generator->generate(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ).PHP_EOL;

        if ($this->option('check')) {
            if (! File::exists($path)) {
                $this->error("No document at {$path}. Run `php artisan api:openapi` to create it.");

                return self::FAILURE;
            }

            if (File::get($path) !== $json) {
                $this->error("{$path} is out of date. Run `php artisan api:openapi` and commit the result.");

                return self::FAILURE;
            }

            $this->info("{$path} matches the implemented routes.");

            return self::SUCCESS;
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $json);

        $document = json_decode($json, true);

        $this->info(sprintf(
            'Wrote %s (%d paths, %d schemas).',
            $this->option('output'),
            count($document['paths'] ?? []),
            count($document['components']['schemas'] ?? []),
        ));

        return self::SUCCESS;
    }
}
