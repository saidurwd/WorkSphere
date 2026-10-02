<?php

namespace App\Services;

use App\Console\WorkSphereSchedule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The application's health, as a set of checks with a verdict.
 *
 * FOLLOWING A RECOGNISED SHAPE rather than inventing one. The document is the
 * Kubernetes-style status document that enterprise platforms converge on — a
 * top-level `status` plus a list of named checks, each with its own verdict —
 * because anything monitoring a system expects to parse one of those, and an
 * invented shape is one more parser to write.
 *
 * LIVENESS AND READINESS ANSWER DIFFERENT QUESTIONS and are checked separately:
 *
 *   - **Liveness** (`/livez`) answers "should this process be restarted?". It
 *     checks what is broken about the process itself — memory, the compiled-view
 *     directory. It deliberately does NOT touch the database: a database blip must
 *     not cause an orchestrator to restart every replica and turn a recoverable
 *     outage into a total one.
 *   - **Readiness** (`/readyz`) answers "should traffic be sent here?". It DOES
 *     check the database, the cache and the queue, because a process that cannot
 *     reach those cannot serve a page and should be taken out of rotation rather
 *     than killed.
 *
 * EVERY CHECK NAMES SOMETHING AN OPERATOR CAN ACT ON. "Database: pass" is only
 * useful next to the latency that produced it; "Extensions: pass" is only useful
 * next to WHICH extension is missing. A check that reports a verdict and nothing
 * else makes the reader go and find the detail somewhere else.
 *
 * THE STATUS CODE IS THE POINT. An endpoint that returns 200 while reporting
 * failures is worse than none, because a monitor checking the status code sees a
 * healthy system. `degraded` returns 503 so it cannot be ignored.
 *
 * `warn` is a first-class verdict. A queue holding work, or migrations waiting to
 * be applied, are not failures — they are facts somebody needs to see, and folding
 * them into `pass` hides them while folding them into `fail` cries wolf.
 */
class HealthCheck
{
    /**
     * Extensions the application cannot run without.
     *
     * `pdo` and a driver are listed separately because the driver is chosen by
     * `DB_CONNECTION` and "pdo is loaded but pdo_mysql is not" is precisely the
     * failure this list exists to catch.
     *
     * @var list<string>
     */
    private const REQUIRED_EXTENSIONS = [
        'pdo',
        'mbstring',
        'openssl',
        'json',
        'tokenizer',
        'ctype',
        'xml',
        'fileinfo',
    ];

    /**
     * Extensions that are not required but that a given deployment is likely to
     * want, reported so their absence is visible rather than discovered.
     *
     * @var array<string, string>
     */
    private const OPTIONAL_EXTENSIONS = [
        'intl' => 'Locale-aware dates and numbers.',
        'bcmath' => 'Arbitrary-precision arithmetic.',
        'curl' => 'Faster outbound HTTP than the stream wrapper.',
        'gd' => 'Image manipulation for attachments.',
        'zip' => 'In-memory archive handling.',
    ];

    public function __construct(
        private readonly Application $app,
        private readonly Filesystem $files,
        private readonly SchemaInspector $inspector,
    ) {}

    // ---- Documents ----------------------------------------------------------

    /**
     * Readiness: can this process serve traffic at all?
     *
     * @return array<string, mixed>
     */
    public function readiness(): array
    {
        return $this->document([
            'database' => $this->database(),
            // Schema sits next to migrations: both answer "does the database match
            // what the code expects?", and an operator reading the two together is
            // far better served than hunting for one in the middle of a list.
            'migrations' => $this->migrations(),
            'schema' => $this->schema(),
            'cache' => $this->cache(),
            'queue' => $this->queue(),
            'storage' => $this->storage(),
            'scheduler' => $this->scheduler(),
            'configuration' => $this->configuration(),
        ]);
    }

    /**
     * Liveness: should this process be restarted?
     *
     * @return array<string, mixed>
     */
    public function liveness(): array
    {
        return $this->document([
            'php' => $this->php(),
            'extensions' => $this->extensions(),
            'storage' => $this->storage(),
        ]);
    }

    /**
     * Everything, for the administration screen. The screen and the probes read the
     * same checks, so what an operator sees and what a monitor polls cannot differ.
     *
     * @return array<string, mixed>
     */
    public function full(): array
    {
        return $this->document([
            'php' => $this->php(),
            'extensions' => $this->extensions(),
            'configuration' => $this->configuration(),
            'database' => $this->database(),
            'migrations' => $this->migrations(),
            'schema' => $this->schema(),
            'cache' => $this->cache(),
            'storage' => $this->storage(),
            'queue' => $this->queue(),
            'scheduler' => $this->scheduler(),
        ]);
    }

    /**
     * The verdict is the AND of every check, so a partial failure cannot read as
     * healthy.
     *
     * @param  array<string, array<string, mixed>>  $checks
     * @return array<string, mixed>
     */
    protected function document(array $checks): array
    {
        $ok = true;

        foreach ($checks as $check) {
            $ok = $ok && $check['status'] === 'pass';
        }

        return [
            // The verdict first, so a reader of the top line gets it before detail.
            'status' => $ok ? 'ok' : 'degraded',
            'checks' => $checks,
            'application' => [
                'name' => config('app.name'),
                'environment' => $this->app->environment(),
                // RFC 3339, i.e. ISO 8601 with an offset. An unqualified timestamp
                // in a health document is ambiguous precisely when somebody is
                // comparing two regions' worth of output.
                'time' => now()->toIso8601String(),
            ],
        ];
    }

    // ---- Checks -------------------------------------------------------------

    /**
     * PHP runtime.
     *
     * @return array<string, mixed>
     */
    protected function php(): array
    {
        $limit = $this->memoryLimitBytes();
        $usage = memory_get_usage(true);

        return $this->record(
            $limit === 0 || $usage < $limit,
            [
                'version' => PHP_VERSION,
                'memory_usage_bytes' => $usage,
                'memory_limit_bytes' => $limit,
                'sapi' => PHP_SAPI,
            ],
        );
    }

    /**
     * Required and wanted PHP extensions, each named.
     *
     * A separate check from `php` because the remedies differ completely: a memory
     * limit is a `php.ini` change, a missing extension is a package or a build
     * flag. Folding them together leaves an operator knowing something is wrong and
     * not knowing which.
     *
     * @return array<string, mixed>
     */
    protected function extensions(): array
    {
        $required = [];

        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            $required[$extension] = extension_loaded($extension);
        }

        // The driver is chosen per deployment, so it is checked against what is
        // actually configured rather than a fixed list.
        $required[$this->configuredDriverExtension()] = extension_loaded($this->configuredDriverExtension());

        $missing = array_keys(array_filter($required, fn (bool $loaded): bool => ! $loaded));

        $optional = [];

        foreach (self::OPTIONAL_EXTENSIONS as $extension => $why) {
            $optional[$extension] = extension_loaded($extension) ? 'loaded' : 'not loaded';
        }

        return $this->record($missing === [], [
            'required_total' => count($required),
            'required_loaded' => count($required) - count($missing),
            'missing' => $missing,
            'optional' => $optional,
        ]);
    }

    /**
     * Runtime configuration an operator should be able to confirm at a glance.
     *
     * These are the settings whose misconfiguration produces a confusing symptom
     * rather than an error: `APP_DEBUG=true` in production leaks stack traces,
     * a missing `APP_KEY` breaks encryption in a way that surfaces much later, and
     * `SESSION_DRIVER=file` on a read-only container fails only on login.
     *
     * The application key is reported as PRESENT OR ABSENT and never printed. A
     * health endpoint is exposed more widely than the application, and a key in the
     * response would be a credential in a monitoring system.
     *
     * @return array<string, mixed>
     */
    protected function configuration(): array
    {
        $problems = [];

        $debug = (bool) config('app.debug');

        if ($debug && ! $this->app->environment(['local', 'testing'])) {
            $problems[] = 'APP_DEBUG is on outside local/testing.';
        }

        $key = (string) config('app.key');

        if ($key === '') {
            $problems[] = 'APP_KEY is not set.';
        }

        if (config('app.env') === 'production' && config('app.url') === 'http://localhost') {
            $problems[] = 'APP_URL is still the local default.';
        }

        return [
            'status' => $problems === [] ? 'pass' : 'fail',
            'environment' => (string) config('app.env'),
            'debug' => $debug,
            'app_key' => $key === '' ? 'missing' : 'set',
            'url' => (string) config('app.url'),
            'timezone' => (string) config('app.timezone'),
            'locale' => $this->app->getLocale(),
            'drivers' => [
                'cache' => (string) config('cache.default'),
                'session' => (string) config('session.driver'),
                'queue' => (string) config('queue.default'),
                'database' => (string) config('database.default'),
            ],
            'problems' => $problems,
        ];
    }

    /**
     * Do the models and THIS database agree?
     *
     * The one check the test suite cannot do. It migrates a fresh database from the
     * same files the application ships, so a column added by editing a migration
     * that had already run is present in every test and absent everywhere else.
     *
     * @return array<string, mixed>
     */
    protected function schema(): array
    {
        try {
            $report = $this->inspector->report();
        } catch (Throwable) {
            return $this->record(false, ['mismatches' => null]);
        }

        $mismatches = $report['mismatches'];

        return [
            'status' => $mismatches === [] ? 'pass' : 'fail',
            'models_checked' => $report['models'],
            'mismatches' => count($mismatches),
            // Named, capped: an operator needs to know WHICH, and the first few are
            // usually the whole story.
            'detail' => array_slice(
                array_map(
                    fn (array $m): string => $m['model'].' expects '.$m['table'].'.'.$m['attribute'],
                    $mismatches,
                ),
                0,
                5,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function database(): array
    {
        $started = microtime(true);

        try {
            DB::connection()->getPdo();

            $reachable = true;
            $message = null;
            $driver = DB::connection()->getDriverName();
        } catch (Throwable) {
            $reachable = false;
            $driver = (string) config('database.default');
            // Generic on purpose. A health endpoint is often exposed more widely
            // than the application, and the useful version of this error — DSN,
            // driver message, credentials — is exactly what must not be in it.
            $message = 'The database is not reachable.';
        }

        return $this->record($reachable, [
            'driver' => $driver,
            'latency_ms' => round((microtime(true) - $started) * 1000, 2),
            'message' => $message,
        ]);
    }

    /**
     * Whether every migration in the codebase has been applied.
     *
     * PENDING IS A WARNING, NOT A FAILURE. An unapplied migration means the schema
     * is behind the code — worth seeing immediately — but the application serves
     * traffic meanwhile, and taking it out of rotation for a schema lag would turn
     * a deploy into an outage. UNREADABLE is a failure, because then nothing can be
     * said about the schema at all.
     *
     * @return array<string, mixed>
     */
    protected function migrations(): array
    {
        try {
            $ran = $this->app->make('migrator')->getRepository()->getRan();
        } catch (Throwable) {
            return $this->record(false, [
                'status_detail' => 'The migrations table could not be read.',
                'pending' => null,
            ]);
        }

        $files = $this->app->make(Migrator::class)->paths()
            ? $this->pendingMigrationNames($ran)
            : [];

        return [
            'status' => $files === [] ? 'pass' : 'warn',
            'applied' => count($ran),
            'pending_count' => count($files),
            // Names, not counts alone: "3 pending" tells an operator nothing about
            // whether the next deploy is the one that applies them.
            'pending' => array_slice($files, 0, 10),
        ];
    }

    /**
     * Migration files present on disk that the `migrations` table does not record.
     *
     * @param  list<string>  $ran
     * @return list<string>
     */
    protected function pendingMigrationNames(array $ran): array
    {
        $files = [];

        foreach ($this->app->make(Migrator::class)->paths() as $path) {
            foreach ($this->files->glob($path.'/*.php') as $file) {
                $files[] = basename($file, '.php');
            }
        }

        sort($files);

        return array_values(array_diff($files, $ran));
    }

    /**
     * @return array<string, mixed>
     */
    protected function cache(): array
    {
        $started = microtime(true);

        try {
            $key = 'system:health:probe';

            // A write AND a read: a store that accepts writes and returns nothing
            // is not a cache, and a read-only probe would call it healthy.
            cache()->put($key, 'ok', 10);

            $roundTrips = cache()->pull($key) === 'ok';

            return $this->record($roundTrips, [
                'store' => (string) config('cache.default'),
                'latency_ms' => round((microtime(true) - $started) * 1000, 2),
            ]);
        } catch (Throwable) {
            return $this->record(false, ['store' => (string) config('cache.default')]);
        }
    }

    /**
     * Storage writability, for the paths the application actually writes to.
     *
     * @return array<string, mixed>
     */
    protected function storage(): array
    {
        $paths = [
            'framework' => storage_path('framework'),
            'app' => storage_path('app'),
            'logs' => storage_path('logs'),
        ];

        $unwritable = [];

        foreach ($paths as $label => $path) {
            if (! is_dir($path) || ! is_writable($path)) {
                $unwritable[] = $label;
            }
        }

        return $this->record($unwritable === [], [
            'unwritable' => $unwritable,
            // Readable even when nothing is writable, so an operator can see WHERE
            // the problem is rather than being told only that there is one.
            'paths' => $paths,
        ]);
    }

    /**
     * The queue: depth, failures and the age of the oldest waiting job.
     *
     * A backlog is a WARNING, not a failure: a queue with work in it is a queue
     * doing its job. Only an unreachable queue fails, because then nothing is being
     * processed at all.
     *
     * @return array<string, mixed>
     */
    protected function queue(): array
    {
        $connection = (string) config('queue.default');

        try {
            $pending = (int) DB::table('jobs')->count();
            $failed = (int) DB::table('failed_jobs')->count();
            $oldest = DB::table('jobs')->min('created_at');

            $oldestMinutes = $oldest === null ? null : (int) floor((time() - (int) $oldest) / 60);

            // Failed jobs are a failure: each one is work the application promised
            // and will not now do, and the count only grows.
            $status = $failed > 0 ? 'fail' : (($oldestMinutes !== null && $oldestMinutes >= 60) ? 'warn' : 'pass');

            return [
                'status' => $status,
                'connection' => $connection,
                'pending' => $pending,
                'failed' => $failed,
                'oldest_pending_minutes' => $oldestMinutes,
            ];
        } catch (Throwable) {
            return $this->record(false, ['connection' => $connection]);
        }
    }

    /**
     * The scheduler: what is registered, in which zone, and when it next fires.
     *
     * @return array<string, mixed>
     */
    protected function scheduler(): array
    {
        try {
            // From the class that defines them, not the container's singleton —
            // see `WorkSphereSchedule::events()` for why that distinction matters.
            // Laravel populates the singleton lazily, so in an ordinary HTTP request
            // it is empty, and this check reported "nothing is scheduled" on a
            // system whose scheduler was working perfectly.
            $events = WorkSphereSchedule::events();
        } catch (Throwable) {
            return $this->record(false, ['events' => 0]);
        }

        $soonest = null;
        $unguarded = 0;

        foreach ($events as $event) {
            $next = $event->nextRunDate();

            if ($next !== null && ($soonest === null || $next < $soonest)) {
                $soonest = $next;
            }

            // An entry without overlap or single-server protection runs twice the
            // moment there is more than one scheduler.
            if (! $event->withoutOverlapping || ! $event->onOneServer) {
                $unguarded++;
            }
        }

        return [
            // No events at all means nothing is scheduled, which is a configuration
            // failure rather than a healthy empty queue.
            'status' => $events === [] ? 'fail' : ($unguarded > 0 ? 'warn' : 'pass'),
            'events' => count($events),
            'unguarded_events' => $unguarded,
            'timezone' => (string) config('app.timezone'),
            'next_run' => $soonest?->toIso8601String(),
        ];
    }

    // ---- Helpers ------------------------------------------------------------

    /**
     * `ini_get('memory_limit')` returns a shorthand string — `128M`, `-1` — not a
     * number, and comparing that against `memory_get_usage()` is a type error
     * rather than a wrong answer. Parsed here, and `-1` normalised to 0 so
     * "unlimited" is one comparison rather than two.
     */
    protected function memoryLimitBytes(): int
    {
        $raw = trim((string) ini_get('memory_limit'));

        if ($raw === '' || (int) $raw === -1) {
            return 0;
        }

        $value = (int) $raw;

        return match (strtolower(substr($raw, -1))) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    /**
     * The PDO driver extension implied by the configured connection.
     */
    protected function configuredDriverExtension(): string
    {
        return match ((string) config('database.default')) {
            'mysql', 'mariadb' => 'pdo_mysql',
            'pgsql', 'postgres' => 'pdo_pgsql',
            'sqlsrv' => 'pdo_sqlsrv',
            default => 'pdo_sqlite',
        };
    }

    /**
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>
     */
    protected function record(bool $ok, array $detail): array
    {
        return ['status' => $ok ? 'pass' : 'fail'] + $detail;
    }
}
