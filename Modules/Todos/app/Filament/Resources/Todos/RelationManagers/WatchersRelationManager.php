<?php

namespace Modules\Todos\Filament\Resources\Todos\RelationManagers;

use App\Models\User;
use App\Services\ActivityLogger;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoWatcher;

/**
 * Who else can see a To-Do. Adding or removing a watcher widens or narrows the
 * audience, so it needs `update` on the To-Do and is recorded on its trail.
 */
class WatchersRelationManager extends RelationManager
{
    protected static string $relationship = 'watchers';

    protected static ?string $title = 'Watchers';

    public function isReadOnly(): bool
    {
        return ! Gate::allows('update', $this->getOwnerRecord());
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
                        $todo = $this->getOwnerRecord();
                        abort_unless(Gate::allows('update', $todo), 403);

                        $watcher = $todo->watchers()->firstOrCreate(['user_id' => $data['user_id']]);

                        $this->record('watcher_added', null, ['user_id' => (int) $data['user_id']]);

                        return $watcher;
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->before(fn (TodoWatcher $record) => abort_unless(Gate::allows('update', $this->getOwnerRecord()), 403))
                    ->after(fn (TodoWatcher $record) => $this->record('watcher_removed', ['user_id' => $record->user_id], null)),
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
