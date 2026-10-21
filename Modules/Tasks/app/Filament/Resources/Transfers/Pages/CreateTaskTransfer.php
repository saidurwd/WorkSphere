<?php

namespace Modules\Tasks\Filament\Resources\Transfers\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Modules\Tasks\Filament\Resources\Transfers\TaskTransferResource;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskTransfer;

class CreateTaskTransfer extends CreateRecord
{
    protected static string $resource = TaskTransferResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $task = Task::query()->findOrFail($data['task_id']);

        // Creating tasks is not the same as being allowed to hand THIS one over.
        abort_unless(Gate::allows('transfer', $task), 403);

        $data['from_user_id'] ??= $task->responsible_user_id;
        $data['transferred_by'] = auth()->id();

        return DB::transaction(function () use ($data, $task): TaskTransfer {
            $transfer = TaskTransfer::query()->create($data);

            $task->update(['responsible_user_id' => $data['to_user_id']]);

            return $transfer;
        });
    }
}
