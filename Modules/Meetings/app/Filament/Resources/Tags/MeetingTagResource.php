<?php

namespace Modules\Meetings\Filament\Resources\Tags;

use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
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
use Modules\Meetings\Filament\Resources\Tags\Pages\CreateMeetingTag;
use Modules\Meetings\Filament\Resources\Tags\Pages\EditMeetingTag;
use Modules\Meetings\Filament\Resources\Tags\Pages\ListMeetingTags;
use Modules\Meetings\Models\MeetingTag;

/**
 * Meeting tags. Changing them needs `meeting.manage_tags`; reading the list needs
 * that or `meeting.view`.
 */
class MeetingTagResource extends Resource
{
    protected static ?string $model = MeetingTag::class;

    protected static ?string $slug = 'meeting-tags';

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHashtag;

    protected static ?int $navigationSort = 42;

    public static function canViewAny(): bool
    {
        return Gate::allows('meeting.manage_tags') || auth()->user()?->hasPermission('meeting.view') === true;
    }

    public static function canCreate(): bool
    {
        return Gate::allows('meeting.manage_tags');
    }

    public static function canEdit(Model $record): bool
    {
        return Gate::allows('meeting.manage_tags');
    }

    public static function canDelete(Model $record): bool
    {
        return Gate::allows('meeting.manage_tags');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255)->unique(ignoreRecord: true),
            TextInput::make('color')->maxLength(50),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('color'),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMeetingTags::route('/'),
            'create' => CreateMeetingTag::route('/create'),
            'edit' => EditMeetingTag::route('/{record}/edit'),
        ];
    }
}
