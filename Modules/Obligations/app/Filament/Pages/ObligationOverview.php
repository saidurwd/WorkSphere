<?php

namespace Modules\Obligations\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Modules\Obligations\Services\ObligationInsights;
use UnitEnum;

/**
 * The obligation overview: what is active, what is about to lapse, and what has.
 */
class ObligationOverview extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartBar;

    protected static string|UnitEnum|null $navigationGroup = 'Obligations';

    protected static ?string $navigationLabel = 'Obligation overview';

    protected static ?int $navigationSort = 29;

    protected static ?string $title = 'Obligation overview';

    protected static ?string $slug = 'obligation-overview';

    protected string $view = 'filament.obligation-overview';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return app(ObligationInsights::class)->overview(auth()->user());
    }
}
