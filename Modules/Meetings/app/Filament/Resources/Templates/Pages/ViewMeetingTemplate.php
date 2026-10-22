<?php

namespace Modules\Meetings\Filament\Resources\Templates\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Meetings\Filament\Resources\Templates\MeetingTemplateResource;

class ViewMeetingTemplate extends ViewRecord
{
    protected static string $resource = MeetingTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            MeetingTemplateResource::scheduleAction(),
        ];
    }
}
