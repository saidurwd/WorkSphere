<?php

namespace Modules\Obligations\Filament\Resources\Obligations\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Obligations\Filament\Resources\Obligations\ObligationResource;

class ViewObligation extends ViewRecord
{
    protected static string $resource = ObligationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
