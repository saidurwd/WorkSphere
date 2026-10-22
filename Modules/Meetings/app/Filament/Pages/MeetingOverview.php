<?php

namespace Modules\Meetings\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\Meetings\Services\MeetingInsights;

/**
 * The meeting overview: what is coming up, what minutes are waiting, what is late.
 */
class MeetingOverview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static ?string $navigationLabel = 'Meeting overview';

    protected static ?int $navigationSort = 39;

    protected static ?string $title = 'Meeting overview';

    protected static ?string $slug = 'meeting-overview';

    protected string $view = 'filament.meeting-overview';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return app(MeetingInsights::class)->overview(auth()->user());
    }
}
