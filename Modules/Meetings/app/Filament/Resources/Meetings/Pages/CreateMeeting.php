<?php

namespace Modules\Meetings\Filament\Resources\Meetings\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Meetings\Events\MeetingCreated;
use Modules\Meetings\Filament\Resources\Meetings\MeetingResource;
use Modules\Meetings\Services\MeetingService;

class CreateMeeting extends CreateRecord
{
    protected static string $resource = MeetingResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $meeting = app(MeetingService::class)->create($data);

        event(new MeetingCreated($meeting));

        return $meeting;
    }
}
