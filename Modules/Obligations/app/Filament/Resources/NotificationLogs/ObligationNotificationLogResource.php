<?php

namespace Modules\Obligations\Filament\Resources\NotificationLogs;

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
use Modules\Obligations\Filament\Resources\NotificationLogs\Pages\ListObligationNotificationLogs;
use Modules\Obligations\Models\NotificationLog;
use UnitEnum;

/**
 * Delivery log for obligation notifications. Viewing is
 * `obligation.view_notification_logs`; deleting is the narrower
 * `obligation.delete_notification_logs`. The shared table also holds rows for other
 * modules, so only rows tied to an obligation are listed.
 */
class ObligationNotificationLogResource extends Resource
{
    protected static ?string $model = NotificationLog::class;

    protected static ?string $modelLabel = 'obligation notification';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static string|UnitEnum|null $navigationGroup = 'Obligations';

    protected static ?string $navigationLabel = 'Obligation notifications';

    protected static ?int $navigationSort = 32;

    protected static ?string $slug = 'obligation-notifications';

    public static function canViewAny(): bool
    {
        return Gate::allows('obligation.view_notification_logs');
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
        return Gate::allows('obligation.delete_notification_logs');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereNotNull('obligation_id');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['obligation:id,obligation_no,title', 'user:id,name']))
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('obligation.obligation_no')->label('Obligation')->searchable(),
                TextColumn::make('obligation.title')->label('Title')->limit(40)->searchable(),
                TextColumn::make('user.name')->label('To')->placeholder('-')->toggleable(),
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
                SelectFilter::make('status')->options(fn (): array => NotificationLog::query()->whereNotNull('obligation_id')->whereNotNull('status')->distinct()->pluck('status', 'status')->all()),
                SelectFilter::make('channel')->options(fn (): array => NotificationLog::query()->whereNotNull('obligation_id')->whereNotNull('channel')->distinct()->pluck('channel', 'channel')->all()),
            ])
            ->recordActions([
                Action::make('remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (): bool => Gate::allows('obligation.delete_notification_logs'))
                    ->action(function (NotificationLog $record): void {
                        abort_unless(Gate::allows('obligation.delete_notification_logs'), 403);

                        $record->delete();

                        Notification::make()->title('Notification deleted')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListObligationNotificationLogs::route('/'),
        ];
    }
}
