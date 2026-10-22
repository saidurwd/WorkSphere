<?php

namespace Modules\Meetings\Filament\Resources\Types\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Meetings\Filament\Resources\Types\MeetingTypeResource;

class EditMeetingType extends EditRecord
{
    protected static string $resource = MeetingTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data + ['updated_by' => auth()->id()];
    }
}
