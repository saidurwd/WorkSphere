<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Services\HealthCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The public health endpoints, and the screen that shows them.
 *
 * The three endpoints are deliberately separate rather than one endpoint with a
 * parameter:
 *
 *   GET /up          — Laravel's own. Kept, because an orchestrator or a platform
 *                      health check may already be pointed at it.
 *   GET /livez       — liveness. 503 means "restart me". Deliberately does not
 *                      touch the database.
 *   GET /readyz      — readiness. 503 means "stop sending me traffic".
 *
 * `livez` and `readyz` are unauthenticated because an orchestrator probing them
 * cannot hold a session. Neither exposes anything a stranger should not know: the
 * documents contain the application's name, environment, PHP version and whether
 * checks passed — no DSNs, no credentials, no row counts.
 */
class HealthController extends Controller
{
    public function __construct(private readonly HealthCheck $health) {}

    /**
     * The administration screen.
     */
    public function index(Request $request): View
    {
        $this->authorize('system.health');

        $report = $this->health->full();

        return view('admin.system.health', [
            'report' => $report,
            'ok' => $report['status'] === 'ok',
            'labels' => $this->labelsFor($report['checks']),
            'icons' => self::ICONS,
            'failing' => array_filter(
                $report['checks'],
                fn (array $check): bool => $check['status'] === 'fail',
            ),
            'warning' => array_filter(
                $report['checks'],
                fn (array $check): bool => $check['status'] === 'warn',
            ),
            'refresh' => (int) $request->integer('refresh', 30),
        ]);
    }

    /**
     * Liveness. 503 when the process itself is unhealthy.
     */
    public function livez(): JsonResponse
    {
        return $this->respond($this->health->liveness());
    }

    /**
     * Readiness. 503 when the process cannot serve traffic.
     */
    public function readyz(): JsonResponse
    {
        return $this->respond($this->health->readiness());
    }

    /**
     * Human labels for a set of checks, keyed BY CHECK NAME.
     *
     * `array_map()` cannot build this. It preserves the input's keys only when the
     * callback returns an ARRAY; returning a string re-indexes the result 0..n, so
     * every lookup missed and every card fell back to the raw name — which is how
     * nine checks rendered as `php`, `cache`, `queue` on a screen meant for a
     * person.
     *
     * @param  array<string, mixed>  $checks
     * @return array<string, string>
     */
    protected function labelsFor(array $checks): array
    {
        $labels = [];

        foreach (array_keys($checks) as $name) {
            $labels[$name] = self::label($name);
        }

        return $labels;
    }

    /**
     * A glyph per check, so a card is recognisable before its text is read.
     *
     * @var array<string, string>
     */
    private const ICONS = [
        'php' => 'filetype-php',
        'extensions' => 'plug',
        'configuration' => 'sliders',
        'database' => 'database',
        'migrations' => 'diagram-2',
        'cache' => 'lightning-charge',
        'storage' => 'hdd-stack',
        'queue' => 'list-task',
        'scheduler' => 'calendar-week',
    ];

    /**
     * One sentence explaining what a check's verdict MEANS.
     *
     * "Queue: warn" is a label. "3 failed jobs have exhausted their retries" is
     * something an operator can act on, and it is the difference between a status
     * page and a diagnostic one.
     *
     * @param  array<string, mixed>  $check
     */
    public static function explain(string $name, array $check): string
    {
        return match ($name) {
            'extensions' => 'Missing: '.implode(', ', (array) ($check['missing'] ?? [])),
            'configuration' => implode(' ', (array) ($check['problems'] ?? ['No problems detected.'])),
            'database' => (string) ($check['message'] ?? 'Reachable in '.($check['latency_ms'] ?? '?').' ms.'),
            'schema' => ((int) ($check['mismatches'] ?? 0)) > 0
                ? ((int) $check['mismatches']).' model(s) declare a column this database does not have. Run `php artisan doctor:schema`.'
                : 'Every column the models declare exists in this database.',
            'migrations' => ($check['pending_count'] ?? 0) > 0
                ? ($check['pending_count']).' migration(s) not applied: '.implode(', ', array_slice((array) ($check['pending'] ?? []), 0, 3))
                : 'Schema matches the codebase.',
            'cache' => 'The '.($check['store'] ?? 'configured').' store did not complete a write-and-read round trip.',
            'storage' => 'Not writable: '.implode(', ', (array) ($check['unwritable'] ?? [])).'.',
            'queue' => (int) ($check['failed'] ?? 0) > 0
                ? (int) $check['failed'].' job(s) have exhausted their retries.'
                : 'Nothing waiting for over an hour. Oldest job is '.($check['oldest_pending_minutes'] ?? 0).' minutes old.',
            // Nested ternaries need parentheses at every level; PHP will not infer
            // the grouping.
            'scheduler' => ((int) ($check['events'] ?? 0) === 0)
                ? 'Nothing is scheduled, so reminders and notifications will never fire.'
                : (((int) ($check['unguarded_events'] ?? 0)) > 0
                    ? ((int) $check['unguarded_events']).' event(s) lack overlap or single-server protection.'
                    : ((int) $check['events']).' event(s) scheduled, all guarded.'),
            default => match ($check['status']) {
                'pass' => 'Healthy.',
                'warn' => 'Needs attention.',
                default => 'Check failed.',
            },
        };
    }

    /**
     * A human label for a check name.
     *
     * `str_replace('_', ' ', $name)` alone renders `php` and `database` in lower
     * case on a screen a person reads, and `Php` is worse than either. These are
     * acronyms, so they are spelled as such.
     */
    public static function label(string $name): string
    {
        $words = ucwords(str_replace('_', ' ', $name));

        return str_replace(
            ['Php', 'Db', 'Cpu', 'Ram'],
            ['PHP', 'DB', 'CPU', 'RAM'],
            $words,
        );
    }

    /**
     * @param  array<string, mixed>  $document
     */
    protected function respond(array $document): JsonResponse
    {
        return response()->json(
            $document,
            $document['status'] === 'ok' ? 200 : 503,
        );
    }
}
