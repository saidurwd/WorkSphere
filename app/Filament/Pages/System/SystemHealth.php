<?php

namespace App\Filament\Pages\System;

use App\Http\Controllers\Admin\System\HealthController;
use App\Services\HealthCheck;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * System health: database, migrations, cache, storage, queue, scheduler,
 * configuration and PHP.
 *
 * The page polls, because a health page that must be reloaded by hand is a health
 * page nobody checks during an incident. The verdict is text as well as colour.
 */
class SystemHealth extends SystemPage
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHeart;

    protected static ?string $navigationLabel = 'Health';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'System health';

    protected static ?string $slug = 'system/health';

    protected string $view = 'filament.pages.system.health';

    protected static function ability(): string
    {
        return 'system.health';
    }

    /**
     * @return array{report: array<string, mixed>, ok: bool, checks: list<array<string, mixed>>}
     */
    protected function getViewData(): array
    {
        $report = app(HealthCheck::class)->full();

        $checks = [];

        foreach ($report['checks'] as $name => $check) {
            $checks[] = [
                'name' => $name,
                'label' => HealthController::label($name),
                'status' => $check['status'],
                'color' => match ($check['status']) {
                    'pass' => 'success',
                    'warn' => 'warning',
                    default => 'danger',
                },
                'explanation' => HealthController::explain($name, $check),
                'facts' => $this->facts($check),
            ];
        }

        return [
            'report' => $report,
            'ok' => $report['status'] === 'ok',
            'checks' => $checks,
        ];
    }

    /**
     * The scalar and list facts worth showing for one check.
     *
     * Sensitive values never reach this far: the app key is reported as
     * present/missing by the check itself.
     *
     * @param  array<string, mixed>  $check
     * @return array<string, string>
     */
    protected function facts(array $check): array
    {
        $facts = [];

        foreach ($check as $key => $value) {
            if (in_array($key, ['status', 'message'], true)) {
                continue;
            }

            $facts[str_replace('_', ' ', (string) $key)] = match (true) {
                is_bool($value) => $value ? 'yes' : 'no',
                is_array($value) => $value === []
                    ? '-'
                    : implode(', ', array_map(
                        fn (mixed $v, int|string $k): string => (is_string($k) ? $k.': ' : '').(is_bool($v) ? ($v ? 'yes' : 'no') : (is_scalar($v) ? (string) $v : json_encode($v))),
                        $value,
                        array_keys($value),
                    )),
                $value === null => '-',
                default => (string) $value,
            };
        }

        return $facts;
    }
}
