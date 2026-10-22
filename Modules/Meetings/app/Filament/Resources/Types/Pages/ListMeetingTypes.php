<?php

namespace Modules\Meetings\Filament\Resources\Types\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Meetings\Filament\Resources\Types\MeetingTypeResource;

class ListMeetingTypes extends ListRecords
{
    protected static string $resource = MeetingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
