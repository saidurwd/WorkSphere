<?php

namespace App\Filament\Resources\ActivityLogs;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog;
use App\Filament\Resources\ReadOnlyLogResource;
use App\Models\ActivityLog;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends ReadOnlyLogResource
{
    protected static ?string $model = ActivityLog::class;

    protected static ?string $modelLabel = 'activity log';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user:id,name,email');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('user.name')->label('User')->placeholder('System'),
            TextEntry::make('module_name')->label('Module')->badge(),
            TextEntry::make('action')->badge(),
            TextEntry::make('record_id')->label('Record')->placeholder('-'),
            TextEntry::make('ip_address')->label('IP address')->placeholder('-'),
            TextEntry::make('user_agent')->placeholder('-')->columnSpanFull(),
            KeyValueEntry::make('old_value')->label('Old values')->placeholder('-'),
            KeyValueEntry::make('new_value')->label('New values')->placeholder('-'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('user.name')->label('User')->placeholder('-'),
                TextColumn::make('module_name')->label('Module')->searchable()->badge(),
                TextColumn::make('action')->searchable()->badge(),
                TextColumn::make('record_id')->label('Record')->toggleable(),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('module_name')->label('Module')
                    ->options(fn (): array => ActivityLog::query()->whereNotNull('module_name')->distinct()->orderBy('module_name')->pluck('module_name', 'module_name')->all()),
                SelectFilter::make('action')
                    ->options(fn (): array => ActivityLog::query()->whereNotNull('action')->distinct()->orderBy('action')->pluck('action', 'action')->all()),
                SelectFilter::make('user_id')->label('User')->relationship('user', 'name')->searchable()->preload(),
                static::dateRangeFilter('created_at'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
            'view' => ViewActivityLog::route('/{record}'),
        ];
    }
}
