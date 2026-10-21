<?php

namespace Modules\Obligations\Filament\Resources\NotificationLogs\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Gate;
use Modules\Obligations\Filament\Resources\NotificationLogs\ObligationNotificationLogResource;

class ListObligationNotificationLogs extends ListRecords
{
    protected static string $resource = ObligationNotificationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Deletes exactly the rows the table is showing — its search and
            // filters — and nothing else. The Blade version built its OR-ed search
            // clause without grouping, so a search plus a filter could delete rows
            // the screen was not showing.
            Action::make('deleteFiltered')
                ->label('Delete all shown')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (): bool => Gate::allows('obligation.delete_notification_logs'))
                ->action(function (): void {
                    abort_unless(Gate::allows('obligation.delete_notification_logs'), 403);

                    $count = $this->getFilteredTableQuery()->count();

                    $this->getFilteredTableQuery()->delete();

                    Notification::make()->title("Deleted {$count} notification(s)")->success()->send();
                }),
        ];
    }
}
