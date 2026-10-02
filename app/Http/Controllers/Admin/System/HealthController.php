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
        $this->authorize('system.manage');

        $report = $this->health->full();

        return view('admin.system.health', [
            'report' => $report,
            'ok' => $report['status'] === 'ok',
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
