<?php

namespace Modules\Tasks\Filament\Resources\NotificationLogs;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Modules\Tasks\Filament\Resources\NotificationLogs\Pages\ListTaskNotificationLogs;
use Modules\Tasks\Models\TaskNotificationLog;

/**
 * Delivery log for task notifications. Viewing is `task.view_notification_logs`;
 * deleting is the narrower `task.delete_notification_logs`.
 */
class TaskNotificationLogResource extends Resource
{
    protected static ?string $model = TaskNotificationLog::class;

    protected static ?string $modelLabel = 'task notification log';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Task notification log';

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'task-notification-logs';

    public static function canViewAny(): bool
    {
        return Gate::allows('task.view_notification_logs');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return Gate::allows('task.delete_notification_logs');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['task:id,title,task_no', 'user:id,name']))
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('task.title')->label('Task')->limit(40)->searchable(),
                TextColumn::make('user.name')->label('To')->placeholder('-')->toggleable(),
                TextColumn::make('notification_type')->badge(),
                TextColumn::make('channel')->badge()->toggleable(),
                TextColumn::make('status')->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'sent', 'delivered' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('subject')->limit(50)->searchable(),
                TextColumn::make('error_message')->limit(80)->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(fn (): array => TaskNotificationLog::query()->whereNotNull('status')->distinct()->pluck('status', 'status')->all()),
                SelectFilter::make('channel')->options(fn (): array => TaskNotificationLog::query()->whereNotNull('channel')->distinct()->pluck('channel', 'channel')->all()),
                SelectFilter::make('notification_type')->label('Type')->options(fn (): array => TaskNotificationLog::query()->whereNotNull('notification_type')->distinct()->pluck('notification_type', 'notification_type')->all()),
            ])
            ->recordActions([
                Action::make('remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => Gate::allows('task.delete_notification_logs'))
                    ->action(function (TaskNotificationLog $record): void {
                        abort_unless(Gate::allows('task.delete_notification_logs'), 403);

                        $record->delete();

                        Notification::make()->title('Notification log deleted')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaskNotificationLogs::route('/'),
        ];
    }
}
