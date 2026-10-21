<?php

namespace App\Filament\Pages\System;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Queue depth, failed jobs and batches.
 *
 * Retrying and discarding go through Artisan, so the behaviour is the framework's
 * own. A job PAYLOAD is never rendered: it can carry credentials or personal data,
 * so only the job's class name and the first line of its error are shown.
 */
class SystemQueue extends SystemPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'Queue';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Queue';

    protected static ?string $slug = 'system/queue';

    protected string $view = 'filament.pages.system.queue';

    protected static function ability(): string
    {
        return 'system.queue';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'connection' => (string) config('queue.default'),
            'queues' => $this->queues(),
            'failed' => $this->failed(),
            'batches' => $this->batches(),
        ];
    }

    public function retry(string $id): void
    {
        abort_unless(static::canAccess(), 403);

        $exit = Artisan::call('queue:retry', ['id' => [$id]]);

        $this->report($exit === 0, 'Job queued for retry.', trim(Artisan::output()));
    }

    public function retryAll(): void
    {
        abort_unless(static::canAccess(), 403);

        $count = $this->failedCount();
        $exit = Artisan::call('queue:retry', ['id' => ['all']]);

        $this->report($exit === 0, "{$count} failed ".($count === 1 ? 'job' : 'jobs').' queued for retry.', trim(Artisan::output()));
    }

    public function forget(string $id): void
    {
        abort_unless(static::canAccess(), 403);

        Artisan::call('queue:forget', ['id' => $id]);

        $this->report(true, 'Failed job discarded.');
    }

    public function flush(): void
    {
        abort_unless(static::canAccess(), 403);

        $count = $this->failedCount();
        Artisan::call('queue:flush');

        $this->report(true, "{$count} failed ".($count === 1 ? 'job' : 'jobs').' discarded.');
    }

    protected function report(bool $ok, string $message, string $error = ''): void
    {
        Notification::make()
            ->title($ok ? $message : ($error !== '' ? $error : 'The command failed.'))
            ->status($ok ? 'success' : 'danger')
            ->send();
    }

    /**
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
            'oldest_minutes' => $row->oldest === null ? null : (int) floor((time() - (int) $row->oldest) / 60),
        ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    protected function failed(): array
    {
        try {
            $rows = DB::table('failed_jobs')->orderByDesc('failed_at')->limit(50)->get();
        } catch (Throwable) {
            return [];
        }

        return $rows->map(fn ($row): array => [
            // `queue:retry` and `queue:forget` look jobs up by UUID, not by the row id.
            'id' => (string) ($row->uuid ?? $row->id),
            'queue' => (string) ($row->queue ?? ''),
            'display_name' => $this->describe((string) ($row->payload ?? '')),
            'attempts' => (int) ($row->attempts ?? 0),
            'failed_at' => $this->asDate($row->failed_at ?? null),
            'error' => $this->firstLine((string) ($row->exception ?? '')),
        ])->all();
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
                    'name' => (string) $row->name,
                    'total' => (int) $row->total_jobs,
                    'pending' => (int) $row->pending_jobs,
                    'failed' => (int) $row->failed_jobs,
                    'progress' => $row->total_jobs > 0
                        ? (int) round(((int) $row->total_jobs - (int) $row->pending_jobs) / (int) $row->total_jobs * 100)
                        : 0,
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

    protected function describe(string $payload): string
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            return 'Unrecognised payload';
        }

        $command = $decoded['data']['commandName'] ?? $decoded['displayName'] ?? null;

        return is_string($command) && $command !== '' ? class_basename($command) : 'Unrecognised payload';
    }

    protected function firstLine(string $exception): string
    {
        $line = trim(strtok($exception, "\n") ?: '');

        return mb_strlen($line) > 200 ? mb_substr($line, 0, 197).'…' : $line;
    }

    protected function asDate(mixed $timestamp): ?string
    {
        if (! is_numeric($timestamp)) {
            return is_string($timestamp) ? $timestamp : null;
        }

        return Carbon::createFromTimestamp((int) $timestamp)->toDayDateTimeString();
    }
}
