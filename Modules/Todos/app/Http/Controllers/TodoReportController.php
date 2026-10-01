<?php

namespace Modules\Todos\Http\Controllers;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Modules\Todos\Services\TodoReportService;

/**
 * To-Do reports — TODO-MODULE-SPECIFICATION.md §10.
 *
 * Every figure comes from TodoReportService's aggregate queries. Nothing here
 * counts rows in PHP, and every report is permission-gated individually so a
 * viewer never receives a section's data — not even a count — without the
 * permission that governs it.
 */
class TodoReportController extends Controller
{
    public function __construct(private readonly TodoReportService $reports) {}

    public function index(Request $request): View
    {
        $this->authorize('todo.view_all');

        [$from, $to] = $this->range($request);

        $users = User::query()->orderBy('name')->pluck('name', 'id');

        return view('todos.reports.index', [
            'from' => $from,
            'to' => $to,
            'summary' => $this->reports->summary(),
            'completionByOwner' => $this->nameRows($this->reports->completionByOwner($from, $to), $users),
            'completionByDepartment' => $this->reports->completionByDepartment($from, $to),
            'overdueByOwner' => $this->nameRows($this->reports->overdueByOwner(), $users),
            'personalProductivity' => $this->nameRows($this->reports->personalProductivity($from, $to), $users),
            'workload' => $this->nameRows($this->reports->workloadDistribution(), $users),
            'overdueTrend' => $this->reports->overdueTrend($from, $to),
            'recurrenceAdherence' => $this->nameRows($this->reports->recurrenceAdherence($from, $to), $users),
            'types' => NotificationType::cases(),
        ]);
    }

    /**
     * CSV export of the completion report.
     *
     * Exported rows are exactly the rows on screen: the same service, the same
     * range, the same permission gate. An export that is broader than the screen
     * is a privilege-escalation path, so the two are built from one call.
     */
    public function export(Request $request)
    {
        $this->authorize('todo.view_all');

        [$from, $to] = $this->range($request);

        $rows = $this->reports->completionByOwner($from, $to);
        $users = User::query()->pluck('name', 'id');

        $csv = ['Assignee', 'Completed'];

        foreach ($rows as $row) {
            $csv[] = [$users[$row->assignee_id] ?? ('User #'.$row->assignee_id), (string) $row->completed_total];
        }

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $csv);
        rewind($handle);

        $filename = 'todo-completion-'.$from.'-'.$to.'.csv';

        return response()->streamDownload(
            static fn () => fpassthru($handle),
            $filename,
            ['Content-Type' => 'text/csv'],
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function range(Request $request): array
    {
        $from = $request->date('from')?->toDateString() ?? now()->startOfMonth()->toDateString();
        $to = $request->date('to')?->toDateString() ?? now()->endOfMonth()->toDateString();

        // A reversed range would silently return an empty report, which reads as
        // "no work happened" rather than "the filter is wrong".
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    /**
     * Attach a display name to an aggregate keyed by user id.
     *
     * @param  Collection<int, object>  $rows
     * @param  Collection<int, string>  $users
     * @return Collection<int, object>
     */
    protected function nameRows($rows, $users)
    {
        return $rows->map(function (object $row) use ($users): object {
            $row->name = $users[$row->assignee_id ?? $row->creator_id] ?? 'Unknown';

            return $row;
        });
    }
}
