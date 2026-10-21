<?php

namespace Modules\Todos\Filament\Resources\Todos\RelationManagers;

use App\Services\ActivityLogger;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoChecklistItem;

/**
 * A To-Do's checklist.
 *
 * Reading follows the To-Do's own visibility; every change needs `update` on the
 * parent To-Do and is recorded on its activity trail, as the Blade controller did.
 */
class ChecklistItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'checklistItems';

    protected static ?string $title = 'Checklist';

    public function isReadOnly(): bool
    {
        return ! Gate::allows('update', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                IconColumn::make('is_completed')->label('Done')->boolean(),
                TextColumn::make('title'),
                TextColumn::make('completed_at')->since()->placeholder('-'),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                CreateAction::make()
                    ->using(function (array $data): Model {
                        /** @var Todo $todo */
                        $todo = $this->getOwnerRecord();

                        $item = $todo->checklistItems()->create([
                            'title' => $data['title'],
                            'sort_order' => ($todo->checklistItems()->max('sort_order') ?? -1) + 1,
                        ]);

                        $this->record('checklist_item_added', null, ['item_id' => $item->id, 'title' => $item->title]);

                        return $item;
                    }),
            ])
            ->recordActions([
                Action::make('toggle')
                    ->label(fn (TodoChecklistItem $record): string => $record->is_completed ? 'Reopen' : 'Done')
                    ->icon(fn (TodoChecklistItem $record): string => $record->is_completed ? 'heroicon-o-arrow-uturn-left' : 'heroicon-o-check')
                    ->visible(fn (): bool => ! $this->isReadOnly())
                    ->action(function (TodoChecklistItem $record): void {
                        $completed = ! $record->is_completed;

                        $record->update([
                            'is_completed' => $completed,
                            'completed_at' => $completed ? now() : null,
                            'completed_by' => $completed ? auth()->id() : null,
                        ]);

                        $this->record(
                            'checklist_item_updated',
                            ['title' => $record->title, 'is_completed' => ! $completed],
                            ['title' => $record->title, 'is_completed' => $completed],
                        );
                    }),
                EditAction::make()
                    ->after(fn (TodoChecklistItem $record) => $this->record('checklist_item_updated', null, ['title' => $record->title])),
                DeleteAction::make()
                    ->before(fn (TodoChecklistItem $record) => $this->record('checklist_item_removed', ['item_id' => $record->id, 'title' => $record->title], null)),
            ]);
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    protected function record(string $action, ?array $old, ?array $new): void
    {
        app(ActivityLogger::class)->record(Todo::class, $this->getOwnerRecord(), $action, $old, $new, auth()->id());
    }
}
