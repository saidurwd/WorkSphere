<?php

namespace Modules\Tasks\Filament\Resources\Tasks\RelationManagers;

use App\Models\Tag;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * A task's tags. Tagging by name creates the tag if it is new, and changing tags
 * is an edit of the task, so it needs `update`.
 */
class TagsRelationManager extends RelationManager
{
    protected static string $relationship = 'tags';

    protected static ?string $title = 'Tags';

    public function isReadOnly(): bool
    {
        return ! Gate::allows('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('tag')->required()->maxLength(100),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->badge(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add tag')
                    ->using(function (array $data): Model {
                        $task = $this->getOwnerRecord();
                        abort_unless(Gate::allows('update', $task), 403);

                        $tag = Tag::findOrCreateByName(trim($data['tag']));

                        $task->tags()->syncWithoutDetaching([$tag->id]);

                        return $tag;
                    }),
            ])
            ->recordActions([
                Action::make('detach')
                    ->label('Remove')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function (Tag $record): void {
                        $task = $this->getOwnerRecord();
                        abort_unless(Gate::allows('update', $task), 403);

                        $task->tags()->detach($record->id);
                    }),
            ]);
    }
}
