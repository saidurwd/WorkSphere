<?php

namespace Modules\Projects\Filament\Resources\Projects\RelationManagers;

use App\Enums\WorkItemStatus;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\Tasks\Filament\Resources\Tasks\TaskResource;
use Modules\Tasks\Models\Task;

/**
 * The tasks filed under a project, read-only. Tasks are created and edited from
 * their own screens; this list links to them.
 */
class TasksRelationManager extends RelationManager
{
    protected static string $relationship = 'tasks';

    protected static ?string $title = 'Tasks';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('responsibleUser:id,name'))
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('responsibleUser.name')->label('Responsible')->placeholder('Unassigned'),
                TextColumn::make('priority')->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'medium' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('status')->badge()->formatStateUsing(fn (WorkItemStatus $state): string => $state->label()),
                TextColumn::make('due_date')->date()->placeholder('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Task $record): string => TaskResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
