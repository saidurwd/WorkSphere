<?php

namespace Modules\Meetings\Filament\Resources\Meetings\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Meetings\Events\MeetingUpdated;
use Modules\Meetings\Filament\Resources\Meetings\MeetingResource;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Services\MeetingService;

class EditMeeting extends EditRecord
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->hidden(fn (Meeting $record): bool => in_array($record->status, ['completed', 'approved'], true)),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $meeting = app(MeetingService::class)->update($record, $data);

        event(new MeetingUpdated($meeting));

        return $meeting;
    }
}
