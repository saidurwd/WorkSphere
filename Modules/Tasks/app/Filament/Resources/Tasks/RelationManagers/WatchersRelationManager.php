<?php

namespace Modules\Tasks\Filament\Resources\Tasks\RelationManagers;

use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Who else can see a task. A watcher gains visibility, never edit rights; managing
 * the list is its own ability (`manageWatchers`).
 */
class WatchersRelationManager extends RelationManager
{
    protected static string $relationship = 'watchers';

    protected static ?string $title = 'Watchers';

    public function isReadOnly(): bool
    {
        return ! Gate::allows('manageWatchers', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('User')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user:id,name,email'))
            ->columns([
                TextColumn::make('user.name')->label('Name'),
                TextColumn::make('user.email')->label('Email'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add watcher')
                    ->using(function (array $data): Model {
                        $task = $this->getOwnerRecord();
                        abort_unless(Gate::allows('manageWatchers', $task), 403);

                        return $task->watchers()->firstOrCreate(['user_id' => $data['user_id']]);
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->before(fn () => abort_unless(Gate::allows('manageWatchers', $this->getOwnerRecord()), 403)),
            ]);
    }
}
