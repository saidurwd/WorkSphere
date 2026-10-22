<?php

namespace Modules\Todos\Filament\Resources\NotificationLogs;

use App\Enums\NotificationType;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Modules\Obligations\Models\NotificationLog;
use Modules\Todos\Filament\Resources\NotificationLogs\Pages\ListTodoNotificationLogs;
use Modules\Todos\Models\Todo;
use UnitEnum;

/**
 * Delivery log for To-Do notifications.
 *
 * `notification_logs` is shared by several modules through a polymorphic subject,
 * so the query is pinned to To-Do subjects. Viewing needs `todo.view_all`;
 * deleting is a separate, narrower ability (`todo.delete_notification_logs`).
 */
class TodoNotificationLogResource extends Resource
{
    protected static ?string $model = NotificationLog::class;

    protected static ?string $modelLabel = 'notification log';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static string|UnitEnum|null $navigationGroup = 'To-Dos';

    protected static ?string $navigationLabel = 'Notification log';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'todo-notification-logs';

    public static function canViewAny(): bool
    {
        return Gate::allows('todo.view_all');
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
        return Gate::allows('todo.delete_notification_logs');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('subject_type', Todo::class);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('notification_type')->badge(),
                TextColumn::make('channel')->badge()->toggleable(),
                TextColumn::make('status')->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'sent', 'delivered' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('subject')->limit(60)->searchable(),
                TextColumn::make('error_message')->limit(80)->placeholder('-')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                SelectFilter::make('notification_type')
                    ->label('Type')
                    ->options(collect(NotificationType::cases())->mapWithKeys(fn (NotificationType $t): array => [$t->value => $t->name])->all()),
                SelectFilter::make('status')
                    ->options(fn (): array => NotificationLog::query()->where('subject_type', Todo::class)->whereNotNull('status')->distinct()->pluck('status', 'status')->all()),
            ])
            ->recordActions([
                Action::make('remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => Gate::allows('todo.delete_notification_logs'))
                    ->action(function (NotificationLog $record): void {
                        abort_unless(Gate::allows('todo.delete_notification_logs'), 403);

                        $record->delete();

                        Notification::make()->title('Notification log removed')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTodoNotificationLogs::route('/'),
        ];
    }
}
