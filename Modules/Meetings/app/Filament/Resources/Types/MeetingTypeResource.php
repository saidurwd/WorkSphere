<?php

namespace Modules\Meetings\Filament\Resources\Types;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Modules\Meetings\Filament\Resources\Types\Pages\CreateMeetingType;
use Modules\Meetings\Filament\Resources\Types\Pages\EditMeetingType;
use Modules\Meetings\Filament\Resources\Types\Pages\ListMeetingTypes;
use Modules\Meetings\Models\MeetingType;

/**
 * Meeting types. Changing them needs `meeting.manage_types`; reading the list needs
 * that or `meeting.view`.
 */
class MeetingTypeResource extends Resource
{
    protected static ?string $model = MeetingType::class;

    protected static ?string $slug = 'meeting-types';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?int $navigationSort = 41;

    public static function canViewAny(): bool
    {
        return Gate::allows('meeting.manage_types') || auth()->user()?->hasPermission('meeting.view') === true;
    }

    public static function canCreate(): bool
    {
        return Gate::allows('meeting.manage_types');
    }

    public static function canEdit(Model $record): bool
    {
        return Gate::allows('meeting.manage_types');
    }

    public static function canDelete(Model $record): bool
    {
        return Gate::allows('meeting.manage_types');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255),
            TextInput::make('code')->required()->maxLength(50)->unique(ignoreRecord: true),
            Textarea::make('description')->columnSpanFull(),
            TextInput::make('color')->maxLength(50),
            TextInput::make('sort_order')->numeric()->integer()->minValue(0)->default(0),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('code')->searchable(),
                TextColumn::make('sort_order')->sortable(),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetingTypes::route('/'),
            'create' => CreateMeetingType::route('/create'),
            'edit' => EditMeetingType::route('/{record}/edit'),
        ];
    }
}
