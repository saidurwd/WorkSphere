<?php

namespace App\Filament\Resources\LoginLogs;

use App\Filament\Resources\LoginLogs\Pages\ListLoginLogs;
use App\Filament\Resources\LoginLogs\Pages\ViewLoginLog;
use App\Filament\Resources\ReadOnlyLogResource;
use App\Models\LoginLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LoginLogResource extends ReadOnlyLogResource
{
    protected static ?string $model = LoginLog::class;

    protected static ?string $modelLabel = 'login log';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowRightEndOnRectangle;

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user:id,name,email');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('attempted_at')->dateTime(),
            TextEntry::make('event')->badge()->state(fn (LoginLog $record): string => $record->eventLabel())->color(fn (LoginLog $record): string => $record->eventVariant() === 'secondary' ? 'gray' : $record->eventVariant()),
            TextEntry::make('email')->placeholder('-'),
            TextEntry::make('user.name')->label('User')->placeholder('-'),
            TextEntry::make('ip_address')->label('IP address')->placeholder('-'),
            TextEntry::make('device')->placeholder('-'),
            TextEntry::make('failure_reason')->placeholder('-'),
            TextEntry::make('user_agent')->placeholder('-')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('attempted_at')->dateTime()->sortable(),
                TextColumn::make('event')->badge()->state(fn (LoginLog $record): string => $record->eventLabel())->color(fn (LoginLog $record): string => $record->eventVariant() === 'secondary' ? 'gray' : $record->eventVariant()),
                TextColumn::make('email')->searchable()->placeholder('-'),
                TextColumn::make('user.name')->label('User')->placeholder('-'),
                TextColumn::make('ip_address')->label('IP')->searchable(),
                TextColumn::make('failure_reason')->placeholder('-')->toggleable(),
            ])
            ->defaultSort('attempted_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->options(fn (): array => LoginLog::query()->distinct()->orderBy('event')->pluck('event', 'event')->all()),
                SelectFilter::make('user_id')->label('User')->relationship('user', 'name')->searchable()->preload(),
                static::dateRangeFilter('attempted_at'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoginLogs::route('/'),
            'view' => ViewLoginLog::route('/{record}'),
        ];
    }
}
