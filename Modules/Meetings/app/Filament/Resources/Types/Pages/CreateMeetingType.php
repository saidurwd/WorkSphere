<?php

namespace Modules\Meetings\Filament\Resources\Types\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Meetings\Filament\Resources\Types\MeetingTypeResource;

class CreateMeetingType extends CreateRecord
{
    protected static string $resource = MeetingTypeResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $data + ['created_by' => auth()->id(), 'updated_by' => auth()->id()];
    }
}
