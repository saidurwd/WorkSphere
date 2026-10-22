<?php

namespace App\Filament\Workspace\Pages;

use App\Dashboard\DashboardWidget;
use App\Dashboard\WidgetRegistry;
use Filament\Pages\Dashboard;
use Illuminate\Support\Collection;

/**
 * The workspace home.
 *
 * Rendered from {@see WidgetRegistry}, so the dashboard keeps what the old one
 * had: each widget is shown only to people holding its permissions, is cached per
 * user, and resolves its own data. This page only draws what the registry returns.
 */
class WorkspaceDashboard extends Dashboard
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $slug = 'dashboard';

    protected string $view = 'filament.workspace.dashboard';

    /**
     * Group order on the page; any group not listed sorts after these.
     *
     * @var list<string>
     */
    private const GROUP_ORDER = ['personal', 'compliance', 'management'];

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Collection<string, list<array{widget: DashboardWidget, data: mixed}>> $grouped */
        $grouped = app(WidgetRegistry::class)->groupedFor(auth()->user());

        $entries = $grouped->flatten(1)->keyBy(fn (array $entry): string => $entry['widget']->key());

        return [
            'grouped' => $grouped->sortBy(fn ($rows, string $group): int => array_search($group, self::GROUP_ORDER, true) === false ? 99 : array_search($group, self::GROUP_ORDER, true)),
            'entries' => $entries,
        ];
    }
}
