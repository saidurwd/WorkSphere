<?php

namespace Modules\Tasks\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\Tasks\Services\TaskDashboard;

/**
 * The task overview: totals, what is due, and how the work breaks down.
 */
class TaskOverview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static ?string $navigationLabel = 'Task overview';

    protected static ?int $navigationSort = 9;

    protected static ?string $title = 'Task overview';

    protected static ?string $slug = 'task-overview';

    protected string $view = 'filament.task-overview';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return app(TaskDashboard::class)->forUser(auth()->user());
    }
}
