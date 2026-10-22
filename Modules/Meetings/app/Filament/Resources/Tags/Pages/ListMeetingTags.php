<?php

namespace Modules\Meetings\Filament\Resources\Tags\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Meetings\Filament\Resources\Tags\MeetingTagResource;

class ListMeetingTags extends ListRecords
{
    protected static string $resource = MeetingTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
