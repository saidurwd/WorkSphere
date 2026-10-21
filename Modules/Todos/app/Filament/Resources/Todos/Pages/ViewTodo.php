<?php

namespace Modules\Todos\Filament\Resources\Todos\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Modules\Todos\Filament\Resources\Todos\TodoResource;

class ViewTodo extends ViewRecord
{
    protected static string $resource = TodoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            TodoResource::lifecycleAction('complete', 'Complete', 'heroicon-o-check', 'success'),
            TodoResource::lifecycleAction('reopen', 'Reopen', 'heroicon-o-arrow-path', 'gray'),
            TodoResource::lifecycleAction('archive', 'Archive', 'heroicon-o-archive-box', 'gray'),
            TodoResource::lifecycleAction('restore', 'Restore', 'heroicon-o-arrow-uturn-left', 'gray'),
        ];
    }
}
