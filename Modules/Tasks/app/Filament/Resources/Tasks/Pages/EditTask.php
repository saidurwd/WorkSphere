<?php

namespace Modules\Tasks\Filament\Resources\Tasks\Pages;

use App\Enums\WorkItemStatus;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Tasks\Events\TaskAssigned;
use Modules\Tasks\Events\TaskCompleted;
use Modules\Tasks\Events\TaskUpdated;
use Modules\Tasks\Filament\Resources\Tasks\TaskResource;
use Modules\Tasks\Models\Task;

class EditTask extends EditRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    /**
     * @param  Task  $record
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($data['status'] === WorkItemStatus::Completed->value && ! $record->completed_at) {
            $data['completed_at'] = now();
        } elseif ($data['status'] !== WorkItemStatus::Completed->value) {
            $data['completed_at'] = null;
        }

        $wasCompleted = $record->status === WorkItemStatus::Completed;
        $previousResponsible = $record->responsible_user_id;

        $record->update($data);

        // One event per save, in the order the Blade controller chose: completion
        // wins over reassignment, which wins over a plain update.
        if (! $wasCompleted && $record->status === WorkItemStatus::Completed) {
            event(new TaskCompleted($record));
        } elseif ($previousResponsible != $record->responsible_user_id) {
            event(new TaskAssigned($record));
        } else {
            event(new TaskUpdated($record));
        }

        return $record;
    }
}
