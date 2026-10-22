<?php

namespace Modules\Meetings\Filament\Resources\Decisions\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Meetings\Filament\Resources\Decisions\MeetingDecisionResource;

class ListMeetingDecisions extends ListRecords
{
    protected static string $resource = MeetingDecisionResource::class;
}
