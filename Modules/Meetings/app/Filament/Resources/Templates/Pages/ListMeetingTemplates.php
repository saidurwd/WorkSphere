<?php

namespace Modules\Meetings\Filament\Resources\Templates\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Meetings\Filament\Resources\Templates\MeetingTemplateResource;

class ListMeetingTemplates extends ListRecords
{
    protected static string $resource = MeetingTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
