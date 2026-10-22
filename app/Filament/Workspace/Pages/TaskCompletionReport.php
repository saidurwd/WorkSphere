<?php

namespace App\Filament\Workspace\Pages;

use App\Services\ReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Task completion by person and by project, and the status/priority distribution,
 * over a date range. Needs `report.view`; {@see ReportService} narrows every figure
 * to what the viewer may see. The CSV export is a plain, rate-limited route.
 */
class TaskCompletionReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Tasks';

    protected static ?string $navigationLabel = 'Task completion report';

    protected static ?int $navigationSort = 15;

    protected static ?string $title = 'Task completion report';

    protected static ?string $slug = 'task-completion-report';

    protected string $view = 'filament.workspace.task-completion-report';

    #[Url]
    public ?string $from = null;

    #[Url]
    public ?string $to = null;

    public static function canAccess(): bool
    {
        return Gate::allows('report.view');
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function range(): array
    {
        $from = $this->parse($this->from) ?? now()->startOfMonth()->toDateString();
        $to = $this->parse($this->to) ?? now()->endOfMonth()->toDateString();

        return $from > $to ? [$to, $from] : [$from, $to];
    }

    protected function parse(?string $value): ?string
    {
        return $value !== null && $value !== '' && strtotime($value) !== false ? date('Y-m-d', strtotime($value)) : null;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        [$from, $to] = $this->range();
        $user = auth()->user();
        $reports = app(ReportService::class);

        return [
            'rangeFrom' => $from,
            'rangeTo' => $to,
            'byOwner' => $reports->taskCompletionByOwner($from, $to, $user),
            'byProject' => $reports->taskCompletionByProject($from, $to, $user),
            'distribution' => $reports->taskDistribution($user),
            'exportUrl' => route('reports.tasks.export', ['from' => $from, 'to' => $to]),
            'distributionExportUrl' => route('reports.distribution.export'),
        ];
    }
}
