<?php

namespace Modules\Todos\Filament\Resources\Todos\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoCommentService;

/**
 * A To-Do's discussion thread.
 *
 * Posting goes through {@see TodoCommentService}, which records the activity and
 * dispatches the comment and mention notifications. Mentions are parsed from the
 * body on the server. Comments are append-only here, as they were in Blade.
 */
class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Comments';

    public function isReadOnly(): bool
    {
        return ! Gate::allows('comment', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('body')->required()->maxLength(5000)->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('author:id,name'))
            ->columns([
                TextColumn::make('author.name')->label('From'),
                TextColumn::make('body')->wrap()->description(fn (Model $record): ?string => $record->parent_id ? 'Reply' : null),
                TextColumn::make('created_at')->since(),
            ])
            ->defaultSort('created_at')
            ->headerActions([
                CreateAction::make()->label('Add comment')->using(fn (array $data): Model => $this->post($data['body'], null)),
            ])
            ->recordActions([
                Action::make('reply')
                    ->schema([Textarea::make('body')->required()->maxLength(5000)->rows(3)])
                    ->visible(fn (): bool => ! $this->isReadOnly())
                    ->action(fn (array $data, Model $record) => $this->post($data['body'], $record->parent_id ?? $record->getKey())),
            ]);
    }

    protected function post(string $body, ?int $parentId): Model
    {
        /** @var Todo $todo */
        $todo = $this->getOwnerRecord();

        abort_unless(Gate::allows('comment', $todo), 403);

        $body = trim($body);

        return app(TodoCommentService::class)->store(
            auth()->user(),
            $todo,
            $body,
            $parentId,
            TodoCommentService::mentionedUserIds($body),
        );
    }
}
