<?php

namespace Modules\Tasks\Filament\Resources\Transfers\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Tasks\Filament\Resources\Transfers\TaskTransferResource;

class ListTaskTransfers extends ListRecords
{
    protected static string $resource = TaskTransferResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Transfer a task'),
        ];
    }
}
