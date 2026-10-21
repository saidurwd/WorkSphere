<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Filament\Resources\ReadOnlyLogResource;
use App\Models\AuditLog;
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

class AuditLogResource extends ReadOnlyLogResource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $modelLabel = 'audit log';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user:id,name,email');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('created_at')->dateTime(),
            TextEntry::make('user.name')->label('User')->placeholder('System'),
            TextEntry::make('event')->badge(),
            TextEntry::make('auditable_type')->label('Subject')->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : 'System'),
            TextEntry::make('auditable_id')->label('Subject ID')->placeholder('-'),
            TextEntry::make('ip_address')->label('IP address')->placeholder('-'),
            TextEntry::make('user_agent')->placeholder('-')->columnSpanFull(),
            KeyValueEntry::make('old_values')->label('Old values')->placeholder('-'),
            KeyValueEntry::make('new_values')->label('New values')->placeholder('-'),
            KeyValueEntry::make('metadata')->placeholder('-')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->dateTime()->sortable(),
                TextColumn::make('user.name')->label('User')->placeholder('-'),
                TextColumn::make('event')->searchable()->badge(),
                TextColumn::make('auditable_type')->label('Subject')->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : 'System')->searchable(),
                TextColumn::make('auditable_id')->label('ID'),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('event')
                    ->options(fn (): array => AuditLog::query()->distinct()->orderBy('event')->pluck('event', 'event')->all()),
                SelectFilter::make('auditable_type')->label('Subject type')
                    ->options(fn (): array => AuditLog::query()->whereNotNull('auditable_type')->distinct()->orderBy('auditable_type')->pluck('auditable_type', 'auditable_type')->map(fn (string $type): string => class_basename($type))->all()),
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
            'index' => ListAuditLogs::route('/'),
            'view' => ViewAuditLog::route('/{record}'),
        ];
    }
}
