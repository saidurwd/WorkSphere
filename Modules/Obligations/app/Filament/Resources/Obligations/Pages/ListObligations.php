<?php

namespace Modules\Obligations\Filament\Resources\Obligations\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Obligations\Filament\Resources\Obligations\ObligationResource;

class ListObligations extends ListRecords
{
    protected static string $resource = ObligationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
