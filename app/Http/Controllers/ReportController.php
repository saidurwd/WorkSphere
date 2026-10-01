<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Shared reports — GAP-036, and CSV export for all of them (GAP-050).
 *
 * The Tasks module had no reports at all before this; Meetings and Obligations
 * keep their own screens and are not migrated here, because rewriting a working
 * screen is a different piece of work from adding the missing one.
 *
 * SECURITY: every report is permission-gated, and the export endpoints carry the
 * same gate as the screen they sit on. An export that is broader than the screen
 * is a privilege-escalation path, so both read from one service call.
 */
class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reports) {}

    public function tasks(Request $request): View
    {
        $this->authorize('report.view');

        [$from, $to] = $this->range($request);

        return view('reports.tasks', [
            'from' => $from,
            'to' => $to,
            'byOwner' => $this->reports->taskCompletionByOwner($from, $to, $request->user()),
            'byProject' => $this->reports->taskCompletionByProject($from, $to, $request->user()),
            'distribution' => $this->reports->taskDistribution($request->user()),
        ]);
    }

    public function taskWorkload(Request $request): View
    {
        // A different permission from the general report: this one reports on
        // other people's work.
        $this->authorize('task.view_all');

        return view('reports.task-workload', [
            'rows' => $this->reports->taskWorkload($request->user()),
        ]);
    }

    public function exportCompletion(Request $request): StreamedResponse
    {
        $this->authorize('report.view');

        [$from, $to] = $this->range($request);

        return $this->reports->exportTaskCompletionByOwner($from, $to, $request->user());
    }

    public function exportWorkload(Request $request): StreamedResponse
    {
        $this->authorize('task.view_all');

        return $this->reports->exportTaskWorkload($request->user());
    }

    public function exportDistribution(Request $request): StreamedResponse
    {
        $this->authorize('report.view');

        return $this->reports->exportTaskDistribution($request->user());
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function range(Request $request): array
    {
        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->endOfMonth()->toDateString();

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
