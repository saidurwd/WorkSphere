<?php

namespace App\Services;

use Illuminate\Console\Scheduling\Schedule;
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
 *     checks what is broken about the process itself — memory, PHP extensions,
 *     whether the compiled-view directory is readable. It deliberately does NOT
 *     touch the database: a database blip must not cause an orchestrator to
 *     restart every replica and turn a recoverable outage into a total one.
 *   - **Readiness** (`/readyz`) answers "should traffic be sent here?". It DOES
 *     check the database, the cache and the queue, because a process that cannot
 *     reach those cannot serve a page and should be taken out of rotation rather
 *     than killed.
 *
 * THE STATUS CODE IS THE POINT. An endpoint that returns 200 while reporting
 * failures is worse than none, because a monitor checking the status code sees a
 * healthy system. `degraded` returns 503 so it cannot be ignored.
 */
class HealthCheck
{
    public function readiness(): array
    {
        return $this->document([
            'database' => $this->database(),
            'cache' => $this->cache(),
            'queue' => $this->queue(),
            'storage' => $this->storage(),
        ]);
    }

    public function liveness(): array
    {
        return $this->document([
            'php' => $this->php(),
            'application' => $this->application(),
        ]);
    }

    /**
     * Everything, for the administration screen.
     *
     * @return array<string, mixed>
     */
    public function full(): array
    {
        return $this->document([
            'php' => $this->php(),
            'application' => $this->application(),
            'database' => $this->database(),
            'cache' => $this->cache(),
            'queue' => $this->queue(),
            'storage' => $this->storage(),
            'schedule' => $this->schedule(),
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
                'environment' => app()->environment(),
                // RFC 3339, i.e. ISO 8601 with an offset. An unqualified timestamp
                // in a health document is ambiguous precisely when somebody is
                // comparing two regions' worth of output.
                'time' => now()->toIso8601String(),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function php(): array
    {
        return $this->record(
            (memory_limit_bytes() === -1 || memory_get_usage(true) < memory_limit_bytes()),
            [
                'version' => PHP_VERSION,
                'memory_usage_bytes' => memory_get_usage(true),
                'memory_limit_bytes' => memory_limit_bytes(),
                'extensions' => [
                    'pdo' => extension_loaded('pdo'),
                    'mbstring' => extension_loaded('mbstring'),
                    'openssl' => extension_loaded('openssl'),
                ],
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function application(): array
    {
        return $this->record(
            is_readable(storage_path('framework/views')),
            [
                'debug' => (bool) config('app.debug'),
                'timezone' => (string) config('app.timezone'),
                'locale' => app()->getLocale(),
                'compiled_views_writable' => is_writable(storage_path('framework/views')),
            ],
        );
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
     * @return array<string, mixed>
     */
    protected function cache(): array
    {
        $started = microtime(true);

        try {
            $key = 'system:health:probe';

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
     * A backlog is a WARNING, not a failure: a queue with work in it is a queue
     * doing its job. Only an unreachable queue fails readiness, because then
     * nothing is being processed at all.
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

            $oldestMinutes = $oldest === null
                ? null
                : (int) floor((time() - (int) $oldest) / 60);

            return [
                'status' => ($oldestMinutes !== null && $oldestMinutes >= 60) ? 'warn' : 'pass',
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
     * @return array<string, mixed>
     */
    protected function storage(): array
    {
        $path = storage_path('app');
        $writable = is_dir($path) ? is_writable($path) : is_writable(storage_path());

        return $this->record($writable, [
            'path' => $path,
            'writable' => $writable,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function schedule(): array
    {
        try {
            return $this->record(true, ['events' => count(app(Schedule::class)->events())]);
        } catch (Throwable) {
            return $this->record(false, ['events' => 0]);
        }
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
