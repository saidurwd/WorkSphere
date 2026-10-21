<?php

namespace App\Filament\Pages\System;

use App\Console\WorkSphereSchedule;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use Illuminate\Console\Scheduling\Event;

/**
 * The application's own scheduled events, with the protections each one carries.
 *
 * An event without overlap protection, single-server protection or an explicit
 * timezone is flagged: those are the three ways a scheduled job misbehaves in
 * production.
 */
class SystemSchedule extends SystemPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Schedule';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Schedule';

    protected static ?string $slug = 'system/schedule';

    protected string $view = 'filament.pages.system.schedule';

    protected static function ability(): string
    {
        return 'system.schedule';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'timezone' => (string) config('app.timezone'),
            'events' => collect(WorkSphereSchedule::events())
                ->map(fn (Event $event): array => $this->row($event))
                ->sortBy('next_run')
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Event $event): array
    {
        $protections = [
            'withoutOverlapping' => ! $event->withoutOverlapping || $event->mutex === null,
            'onOneServer' => ! (bool) $event->onOneServer,
            'timezone' => $event->timezone === null,
        ];

        $next = $event->nextRunDate();

        return [
            'command' => $this->command($event),
            'description' => trim((string) ($event->description ?? '')),
            'expression' => $event->getExpression(),
            'next_run' => $next?->toDateTimeString(),
            'next_run_human' => $next?->diffForHumans(),
            'timezone' => $event->timezone ?? (string) config('app.timezone'),
            'missing' => array_keys(array_filter($protections)),
        ];
    }

    protected function command(Event $event): string
    {
        $command = trim((string) ($event->command ?? ''));

        if ($command !== '' && preg_match("#artisan'?\s+(.+)$#", $command, $matches) === 1) {
            return trim($matches[1]);
        }

        return $command;
    }
}
