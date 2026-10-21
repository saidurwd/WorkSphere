<?php

namespace Modules\Tasks\Filament\Resources\NotificationLogs\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Gate;
use Modules\Tasks\Filament\Resources\NotificationLogs\TaskNotificationLogResource;

class ListTaskNotificationLogs extends ListRecords
{
    protected static string $resource = TaskNotificationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Deletes exactly the rows the table is currently showing — its search
            // and filters — and nothing else. The Blade version built its OR-ed
            // search clause without grouping, so a search plus a filter could
            // delete rows the screen was not showing.
            Action::make('deleteFiltered')
                ->label('Delete all shown')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Delete every notification log matching the current search and filters?')
                ->visible(fn (): bool => Gate::allows('task.delete_notification_logs'))
                ->action(function (): void {
                    abort_unless(Gate::allows('task.delete_notification_logs'), 403);

                    $count = $this->getFilteredTableQuery()->count();

                    $this->getFilteredTableQuery()->delete();

                    Notification::make()->title("Deleted {$count} notification log(s)")->success()->send();
                }),
        ];
    }
}
