<?php

namespace App\Filament\Workspace\Pages;

use App\Support\NotificationPresenter;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\DatabaseNotification;

/**
 * The signed-in user's own notifications.
 *
 * Reads Laravel's `notifications` table, limited to the application's notification
 * classes and to the current user, so one person can never see or clear another's.
 * Titles are escaped by the table, because the payload is stored data.
 */
class Notifications extends Page implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions, InteractsWithSchemas, InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Notifications';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Notifications';

    protected static ?string $slug = 'notifications';

    protected string $view = 'filament.workspace.notifications';

    public static function getNavigationBadge(): ?string
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        $unread = $user->unreadNotifications()->whereIn('type', NotificationPresenter::NOTIFICATION_CLASSES)->count();

        return $unread > 0 ? (string) $unread : null;
    }

    protected function ownNotifications(): Builder
    {
        return DatabaseNotification::query()
            ->where('notifiable_type', auth()->user()->getMorphClass())
            ->where('notifiable_id', auth()->id())
            ->whereIn('type', NotificationPresenter::NOTIFICATION_CLASSES);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => $this->ownNotifications())
            ->columns([
                IconColumn::make('read_at')->label('Unread')->boolean()
                    ->trueIcon('heroicon-o-check')->falseIcon('heroicon-o-bell-alert')
                    ->trueColor('gray')->falseColor('primary'),
                TextColumn::make('data.title')
                    ->label('Notification')
                    ->state(fn (DatabaseNotification $record): string => NotificationPresenter::present($record)['title'])
                    ->weight(fn (DatabaseNotification $record): string => $record->read_at === null ? 'bold' : 'normal')
                    ->wrap(),
                TextColumn::make('created_at')->label('When')->since()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('unread')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNull('read_at'),
                        false: fn (Builder $query): Builder => $query->whereNotNull('read_at'),
                    ),
            ])
            ->recordActions([
                Action::make('open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (DatabaseNotification $record): string => NotificationPresenter::present($record)['url']),
                Action::make('markRead')
                    ->label('Mark as read')
                    ->icon('heroicon-o-check')
                    ->visible(fn (DatabaseNotification $record): bool => $record->read_at === null)
                    ->action(fn (DatabaseNotification $record) => $record->markAsRead()),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markAllRead')
                ->label('Mark all as read')
                ->icon('heroicon-o-check-circle')
                ->visible(fn (): bool => $this->ownNotifications()->whereNull('read_at')->exists())
                ->action(function (): void {
                    $this->ownNotifications()->whereNull('read_at')->update(['read_at' => now()]);

                    Notification::make()->title('All notifications marked as read')->success()->send();
                }),
        ];
    }
}
