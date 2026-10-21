<?php

namespace Modules\Todos\Filament\Resources\Todos\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Modules\Todos\Filament\Resources\Todos\TodoResource;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoService;

class CreateTodo extends CreateRecord
{
    protected static string $resource = TodoResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        // Defence in depth: the field is hidden without the permission, but a hidden
        // field is not an authorisation check.
        if (($data['assignee_id'] ?? null) !== null) {
            abort_unless(Gate::allows('createForOthers', Todo::class), 403);
        }

        return app(TodoService::class)->create(auth()->user(), [
            ...$data,
            'assignee_id' => $data['assignee_id'] ?? null,
        ]);
    }
}
