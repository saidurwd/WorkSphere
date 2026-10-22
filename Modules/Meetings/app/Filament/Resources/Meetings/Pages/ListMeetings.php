<?php

namespace Modules\Meetings\Filament\Resources\Meetings\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Meetings\Filament\Resources\Meetings\MeetingResource;

class ListMeetings extends ListRecords
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
