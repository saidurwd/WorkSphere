<?php

namespace Modules\Todos\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Modules\Todos\Services\TodoReportService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * To-Do completion, overdue and productivity reports.
 *
 * Gated on `todo.view_all`: these reports aggregate across everyone's work, which
 * is more than the per-user visibility the list enforces.
 */
class TodoReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'To-Do reports';

    protected static ?string $slug = 'todo-reports';

    protected string $view = 'filament.todo-reports';

    public ?string $from = null;

    public ?string $to = null;

    public static function canAccess(): bool
    {
        return Gate::allows('todo.view_all');
    }

    public function mount(): void
    {
        $this->from = now()->startOfMonth()->toDateString();
        $this->to = now()->endOfMonth()->toDateString();
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function range(): array
    {
        $from = $this->parseDate($this->from) ?? now()->startOfMonth()->toDateString();
        $to = $this->parseDate($this->to) ?? now()->endOfMonth()->toDateString();

        return $from > $to ? [$to, $from] : [$from, $to];
    }

    protected function parseDate(?string $value): ?string
    {
        return $value !== null && strtotime($value) !== false ? date('Y-m-d', strtotime($value)) : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        [$from, $to] = $this->range();
        $reports = app(TodoReportService::class);
        $users = User::query()->orderBy('name')->pluck('name', 'id');

        $name = fn (object $row): string => $users[$row->assignee_id ?? $row->creator_id ?? 0] ?? 'Unknown';

        return [
            'summary' => $reports->summary(),
            'sections' => [
                'Completion by person' => $this->bars($reports->completionByOwner($from, $to), 'completed_total', fn ($r) => $name($r)),
                'Overdue by person' => $this->bars($reports->overdueByOwner(), 'overdue_total', fn ($r) => $name($r)),
                'Completion by department' => $this->bars($reports->completionByDepartment($from, $to), 'completed_total', fn ($r) => 'Department #'.$r->department_id),
                'Workload distribution' => $this->bars($reports->workloadDistribution(), 'open_total', fn ($r) => $name($r).' — '.$r->open_total.' open, '.$r->overdue_total.' overdue'),
                'Personal productivity' => $this->rates($reports->personalProductivity($from, $to), 'completion_rate', fn ($r) => $name($r).' — '.$r->created_total.' created, '.$r->completed_total.' done'),
                'Recurrence adherence' => $this->rates($reports->recurrenceAdherence($from, $to), 'adherence_rate', fn ($r) => $name($r).' — '.$r->generated_total.' generated, '.$r->completed_total.' completed'),
                'Overdue trend' => $this->bars($reports->overdueTrend($from, $to), 'overdue_total', fn ($r) => 'Week '.$r->period),
            ],
        ];
    }

    /**
     * Rows scaled against the largest value in the set.
     *
     * @return list<array{label: string, value: string, pct: int}>
     */
    protected function bars(Collection $rows, string $column, callable $label): array
    {
        $max = (float) $rows->max($column);

        return $rows->map(fn (object $row): array => [
            'label' => (string) $label($row),
            'value' => (string) $row->{$column},
            'pct' => $max > 0 ? (int) round($row->{$column} / $max * 100) : 0,
        ])->all();
    }

    /**
     * Rows whose value is already a percentage.
     *
     * @return list<array{label: string, value: string, pct: int}>
     */
    protected function rates(Collection $rows, string $column, callable $label): array
    {
        return $rows->map(fn (object $row): array => [
            'label' => (string) $label($row),
            'value' => $row->{$column}.'%',
            'pct' => (int) $row->{$column},
        ])->all();
    }

    public function exportCompletion(): StreamedResponse
    {
        abort_unless(static::canAccess(), 403);

        [$from, $to] = $this->range();
        $users = User::query()->pluck('name', 'id');
        $rows = app(TodoReportService::class)->completionByOwner($from, $to);

        return response()->streamDownload(function () use ($rows, $users): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Assignee', 'Completed']);

            foreach ($rows as $row) {
                fputcsv($out, [$users[$row->assignee_id] ?? ('User #'.$row->assignee_id), (string) $row->completed_total]);
            }

            fclose($out);
        }, "todo-completion-{$from}-{$to}.csv", ['Content-Type' => 'text/csv']);
    }
}
