<?php

namespace Modules\Tasks\Filament\Resources\Tasks\Pages;

use App\Enums\WorkItemStatus;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Tasks\Events\TaskCreated;
use Modules\Tasks\Filament\Resources\Tasks\TaskResource;

class CreateTask extends CreateRecord
{
    protected static string $resource = TaskResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        if (($data['status'] ?? null) === WorkItemStatus::Completed->value) {
            $data['completed_at'] = now();
        }

        $task = auth()->user()->tasks()->create($data);

        event(new TaskCreated($task));

        return $task;
    }
}
