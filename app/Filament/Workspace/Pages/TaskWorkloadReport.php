<?php

namespace App\Filament\Workspace\Pages;

use App\Services\ReportService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use UnitEnum;

/**
 * Open work carried by each person. Needs `task.view_all`, because it reports on
 * other people's tasks.
 */
class TaskWorkloadReport extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'Tasks';

    protected static ?string $navigationLabel = 'Task workload report';

    protected static ?int $navigationSort = 16;

    protected static ?string $title = 'Team workload';

    protected static ?string $slug = 'task-workload-report';

    protected string $view = 'filament.workspace.task-workload-report';

    public static function canAccess(): bool
    {
        return Gate::allows('task.view_all');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        return [
            'rows' => app(ReportService::class)->taskWorkload(auth()->user()),
            'exportUrl' => route('reports.workload.export'),
        ];
    }
}
