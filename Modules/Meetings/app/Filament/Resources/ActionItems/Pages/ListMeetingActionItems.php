<?php

namespace Modules\Meetings\Filament\Resources\ActionItems\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Meetings\Filament\Resources\ActionItems\MeetingActionItemResource;

class ListMeetingActionItems extends ListRecords
{
    protected static string $resource = MeetingActionItemResource::class;
}
