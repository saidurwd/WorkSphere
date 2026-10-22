<?php

namespace Modules\Meetings\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Modules\Meetings\Services\MeetingInsights;

/**
 * Accountability by person and by department. Needs `meeting.view_reports`, and
 * covers only the action items the viewer may see.
 */
class MeetingReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Meeting reports';

    protected static ?int $navigationSort = 47;

    protected static ?string $title = 'Meeting reports';

    protected static ?string $slug = 'meeting-reports';

    protected string $view = 'filament.meeting-reports';

    public static function canAccess(): bool
    {
        return Gate::allows('meeting.view_reports');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        $insights = app(MeetingInsights::class);

        return [
            'people' => $insights->personWise(auth()->user()),
            'departments' => $insights->departmentWise(auth()->user()),
        ];
    }
}
