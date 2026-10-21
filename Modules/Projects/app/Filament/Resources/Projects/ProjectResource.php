<?php

namespace Modules\Projects\Filament\Resources\Projects;

use App\Models\User;
use App\Policies\ProjectPolicy;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Projects\Filament\Resources\Projects\Pages\CreateProject;
use Modules\Projects\Filament\Resources\Projects\Pages\EditProject;
use Modules\Projects\Filament\Resources\Projects\Pages\ListProjects;
use Modules\Projects\Filament\Resources\Projects\Pages\ViewProject;
use Modules\Projects\Filament\Resources\Projects\RelationManagers\TasksRelationManager;
use Modules\Projects\Models\Project;

/**
 * Projects in the Filament workspace panel.
 *
 * Abilities come from {@see ProjectPolicy}. The list is narrowed
 * the way the Blade list was: without `project.view` you see only projects you
 * created (a super-admin sees them all).
 */
class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?int $navigationSort = 20;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->withCount('tasks');

        $user = auth()->user();

        if ($user instanceof User && ! $user->hasPermission('project.view') && ! $user->hasRole('super-admin')) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('description')->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('name'),
            TextEntry::make('user.name')->label('Created by')->placeholder('-'),
            TextEntry::make('description')->placeholder('-')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('description')->searchable()->limit(60)->toggleable(),
                TextColumn::make('tasks_count')->label('Tasks')->sortable(),
                TextColumn::make('created_at')->since()->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProjects::route('/'),
            'create' => CreateProject::route('/create'),
            'view' => ViewProject::route('/{record}'),
            'edit' => EditProject::route('/{record}/edit'),
        ];
    }
}
