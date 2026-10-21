<?php

namespace Modules\Obligations\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Modules\Obligations\Services\ObligationInsights;

/**
 * Expiry, department, vendor, renewal and risk reports. Needs
 * `obligation.view_reports`, and covers only obligations the viewer can see.
 */
class ObligationReports extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $navigationLabel = 'Obligation reports';

    protected static ?int $navigationSort = 33;

    protected static ?string $title = 'Obligation reports';

    protected static ?string $slug = 'obligation-reports';

    protected string $view = 'filament.obligation-reports';

    public static function canAccess(): bool
    {
        return Gate::allows('obligation.view_reports');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        abort_unless(static::canAccess(), 403);

        return app(ObligationInsights::class)->reports(auth()->user());
    }
}
