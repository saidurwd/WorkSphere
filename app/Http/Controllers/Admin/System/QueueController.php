<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * Queue and failed-job monitoring.
 *
 * Every action here is a QUEUE OPERATION rather than a query: retrying is
 * `queue:retry`, flushing is `queue:flush`. Re-implementing either over the
 * `jobs` table would re-implement Laravel's payload decoding, its retry backoff
 * and its lock release, and would do it slightly differently.
 *
 * `failed_jobs.payload` is NEVER rendered. It is the serialised job, which
 * routinely contains model identifiers, notification bodies and mail queue keys —
 * showing it on an administration screen puts that data in front of every
 * administrator and into every screenshot taken of the screen during an incident.
 * What is shown is what the job IS, derived from the payload and nothing more.
 */
class QueueController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('system.queue');

        return view('admin.system.queue', [
            'connection' => (string) config('queue.default'),
            'queues' => $this->queues(),
            'failed' => $this->failed($request),
            'batches' => $this->batches(),
        ]);
    }

    public function retry(Request $request): RedirectResponse
    {
        $this->authorize('system.queue');

        $validated = $request->validate([
            'id' => ['required', 'string', 'max:255'],
        ]);

        // Run through Artisan rather than reimplementing: this is the code path
        // that decodes the payload and re-queues it with the original attempts
        // count and backoff.
        $exit = \Artisan::call('queue:retry', ['id' => [$validated['id']], '--queue' => null]);

        return back()->with(
            $exit === 0 ? 'success' : 'error',
            $exit === 0
                ? 'Job queued for retry.'
                : \Artisan::output(),
        );
    }

    public function retryAll(): RedirectResponse
    {
        $this->authorize('system.queue');

        $count = $this->failedCount();
        $exit = \Artisan::call('queue:retry', ['id' => [], '--queue' => null]);

        return back()->with(
            $exit === 0 ? 'success' : 'error',
            $exit === 0
                ? "{$count} failed ".($count === 1 ? 'job' : 'jobs').' queued for retry.'
                : \Artisan::output(),
        );
    }

    public function forget(Request $request): RedirectResponse
    {
        $this->authorize('system.queue');

        $validated = $request->validate([
            'id' => ['required', 'string', 'max:255'],
        ]);

        \Artisan::call('queue:forget', ['id' => $validated['id']]);

        return back()->with('success', 'Failed job discarded.');
    }

    public function flush(): RedirectResponse
    {
        $this->authorize('system.queue');

        $count = $this->failedCount();
        \Artisan::call('queue:flush');

        return back()->with('success', "{$count} failed ".($count === 1 ? 'job' : 'jobs').' discarded.');
    }

    /**
     * Pending depth per queue.
     *
     * @return list<array{queue: string, pending: int, oldest_minutes: int|null}>
     */
    protected function queues(): array
    {
        try {
            $rows = DB::table('jobs')
                ->selectRaw('queue, COUNT(*) as pending, MIN(created_at) as oldest')
                ->groupBy('queue')
                ->orderBy('queue')
                ->get();
        } catch (Throwable) {
            return [];
        }

        return $rows->map(fn ($row): array => [
            'queue' => (string) $row->queue,
            'pending' => (int) $row->pending,
            'oldest_minutes' => $row->oldest === null
                ? null
                : (int) floor((time() - (int) $row->oldest) / 60),
        ])->all();
    }

    /**
     * Failed jobs, newest first.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    protected function failed(Request $request): \Illuminate\Support\Collection
    {
        $limit = min(100, max(10, $request->integer('limit', 50)));

        try {
            $rows = DB::table('failed_jobs')
                ->orderByDesc('failed_at')
                ->limit($limit)
                ->get();
        } catch (Throwable) {
            return collect();
        }

        return $rows->map(fn ($row): array => [
            'id' => (string) $row->id,
            'connection' => (string) ($row->connection ?? ''),
            'queue' => (string) ($row->queue ?? ''),
            'display_name' => $this->describe($row->payload ?? ''),
            'attempts' => (int) ($row->attempts ?? 0),
            'failed_at' => $this->asDate($row->failed_at ?? null),
            'error' => $this->firstLine((string) ($row->exception ?? '')),
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function batches(): array
    {
        try {
            return DB::table('job_batches')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get()
                ->map(fn ($row): array => [
                    'id' => (string) $row->id,
                    'name' => (string) $row->name,
                    'total' => (int) $row->total_jobs,
                    'pending' => (int) $row->pending_jobs,
                    'failed' => (int) $row->failed_jobs,
                    'progress' => $row->total_jobs > 0
                        ? (int) round(((int) $row->total_jobs - (int) $row->pending_jobs) / (int) $row->total_jobs * 100)
                        : 0,
                    'created_at' => $this->asDate($row->created_at ?? null),
                ])
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    protected function failedCount(): int
    {
        try {
            return (int) DB::table('failed_jobs')->count();
        } catch (Throwable) {
            return 0;
        }
    }

    /**
     * What a job IS, taken from its payload — never the payload itself.
     */
    protected function describe(string $payload): string
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return 'Unrecognised payload';
        }

        $command = $decoded['data']['commandName'] ?? $decoded['displayName'] ?? null;

        if (is_string($command) && $command !== '') {
            return $this->shortClass($command);
        }

        return is_string($decoded['displayName'] ?? null) && $decoded['displayName'] !== ''
            ? (string) $decoded['displayName']
            : 'Unrecognised payload';
    }

    /**
     * The first line of an exception, which is where the class and message are.
     *
     * A stack trace on this screen would be several hundred lines per row and is
     * available from the log, which is where a stack trace belongs.
     */
    protected function firstLine(string $exception): string
    {
        $line = trim(strtok($exception, "\n") ?: '');

        return mb_strlen($line) > 200 ? mb_substr($line, 0, 197).'…' : $line;
    }

    /**
     * `App\Jobs\SendMailJob` reads better in a list than the FQCN.
     */
    protected function shortClass(string $class): string
    {
        return class_basename($class);
    }

    protected function asDate(mixed $timestamp): ?string
    {
        if (! is_numeric($timestamp)) {
            return null;
        }

        return \Illuminate\Support\Carbon::createFromTimestamp((int) $timestamp)->toIso8601String();
    }
}
