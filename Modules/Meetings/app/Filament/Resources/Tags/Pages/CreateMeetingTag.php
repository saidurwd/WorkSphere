<?php

namespace Modules\Meetings\Filament\Resources\Tags\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Meetings\Filament\Resources\Tags\MeetingTagResource;

class CreateMeetingTag extends CreateRecord
{
    protected static string $resource = MeetingTagResource::class;
}
