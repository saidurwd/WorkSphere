<?php

namespace Modules\Meetings\Filament\Resources\NotificationLogs;

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
use Modules\Meetings\Filament\Resources\NotificationLogs\Pages\ListMeetingNotificationLogs;
use Modules\Meetings\Models\MeetingNotificationLog;

/**
 * Delivery log for meeting notifications. Viewing is
 * `meeting.view_notification_logs`; deleting is the narrower
 * `meeting.delete_notification_logs`.
 */
class MeetingNotificationLogResource extends Resource
{
    protected static ?string $model = MeetingNotificationLog::class;

    protected static ?string $modelLabel = 'meeting notification log';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Meeting notification log';

    protected static ?int $navigationSort = 44;

    protected static ?string $slug = 'meeting-notification-logs';

    public static function canViewAny(): bool
    {
        return Gate::allows('meeting.view_notification_logs');
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
        return Gate::allows('meeting.delete_notification_logs');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['meeting:id,meeting_no,title', 'user:id,name']))
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('meeting.title')->label('Meeting')->limit(40)->searchable(),
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
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(fn (): array => MeetingNotificationLog::query()->whereNotNull('status')->distinct()->pluck('status', 'status')->all()),
                SelectFilter::make('channel')->options(fn (): array => MeetingNotificationLog::query()->whereNotNull('channel')->distinct()->pluck('channel', 'channel')->all()),
                SelectFilter::make('notification_type')->label('Type')->options(fn (): array => MeetingNotificationLog::query()->whereNotNull('notification_type')->distinct()->pluck('notification_type', 'notification_type')->all()),
            ])
            ->recordActions([
                Action::make('remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => Gate::allows('meeting.delete_notification_logs'))
                    ->action(function (MeetingNotificationLog $record): void {
                        abort_unless(Gate::allows('meeting.delete_notification_logs'), 403);

                        $record->delete();

                        Notification::make()->title('Notification log deleted')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetingNotificationLogs::route('/'),
        ];
    }
}
