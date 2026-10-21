<?php

namespace Modules\Todos\Filament\Resources\Todos\Pages;

use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Modules\Todos\Filament\Resources\Todos\TodoResource;
use Modules\Todos\Services\TodoService;

class EditTodo extends EditRecord
{
    protected static string $resource = TodoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->using(function (Model $record): bool {
                    app(TodoService::class)->destroy(auth()->user(), $record);

                    return true;
                }),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Reassignment has its own ability and its own service call.
        unset($data['assignee_id']);

        return app(TodoService::class)->update(auth()->user(), $record, $data);
    }
}
