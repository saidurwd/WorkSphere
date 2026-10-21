<?php

namespace Modules\Tasks\Filament\Resources\Tasks\RelationManagers;

use App\Services\ActivityLogger;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TimeEntry;

/**
 * Time logged against a task. The person who did the work logs it (`logTime`);
 * every change re-syncs the task's actual minutes and is activity-logged.
 */
class TimeEntriesRelationManager extends RelationManager
{
    protected static string $relationship = 'timeEntries';

    protected static ?string $title = 'Time';

    public function isReadOnly(): bool
    {
        return ! Gate::allows('logTime', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('minutes')->numeric()->integer()->minValue(1)->maxValue(1440)->required(),
            DatePicker::make('logged_on')->default(now())->required(),
            TextInput::make('note')->maxLength(255),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user:id,name'))
            ->columns([
                TextColumn::make('logged_on')->date(),
                TextColumn::make('user.name')->label('By'),
                TextColumn::make('minutes')->suffix(' min'),
                TextColumn::make('note')->placeholder('-'),
            ])
            ->defaultSort('logged_on', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Log time')
                    ->using(function (array $data): Model {
                        /** @var Task $task */
                        $task = $this->getOwnerRecord();
                        abort_unless(Gate::allows('logTime', $task), 403);

                        $entry = $task->timeEntries()->create([
                            'user_id' => auth()->id(),
                            'minutes' => $data['minutes'],
                            'logged_on' => $data['logged_on'],
                            'note' => $data['note'] ?? null,
                        ]);

                        $task->syncActualMinutes();

                        $this->record('time_logged', null, ['entry_id' => $entry->id, 'minutes' => $entry->minutes]);

                        return $entry;
                    }),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->before(fn () => abort_unless(Gate::allows('logTime', $this->getOwnerRecord()), 403))
                    ->after(function (TimeEntry $record): void {
                        $this->getOwnerRecord()->syncActualMinutes();

                        $this->record('time_removed', ['entry_id' => $record->id], null);
                    }),
            ]);
    }

    /**
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    protected function record(string $action, ?array $old, ?array $new): void
    {
        app(ActivityLogger::class)->record(Task::class, $this->getOwnerRecord(), $action, $old, $new, auth()->id());
    }
}
