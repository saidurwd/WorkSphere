<?php

namespace Modules\Meetings\Filament\Resources\Tags\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Meetings\Filament\Resources\Tags\MeetingTagResource;

class EditMeetingTag extends EditRecord
{
    protected static string $resource = MeetingTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
