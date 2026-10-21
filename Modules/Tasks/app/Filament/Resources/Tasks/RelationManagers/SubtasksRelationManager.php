<?php

namespace Modules\Tasks\Filament\Resources\Tasks\RelationManagers;

use App\Enums\WorkItemStatus;
use App\Models\User;
use App\Services\ActivityLogger;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Modules\Tasks\Filament\Resources\Tasks\TaskResource;
use Modules\Tasks\Models\Task;

/**
 * Sub-tasks of a task. Creating one needs `createSubtask` on the parent.
 */
class SubtasksRelationManager extends RelationManager
{
    protected static string $relationship = 'subtasks';

    protected static ?string $title = 'Sub-tasks';

    public function isReadOnly(): bool
    {
        return ! Gate::allows('createSubtask', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('description')->maxLength(5000)->columnSpanFull(),
            Select::make('priority')->options(TaskResource::PRIORITIES)->default('medium'),
            DatePicker::make('due_date'),
            Select::make('responsible_user_id')
                ->label('Responsible')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('responsibleUser:id,name'))
            ->columns([
                TextColumn::make('title'),
                TextColumn::make('status')->badge()->formatStateUsing(fn (WorkItemStatus $state): string => $state->label()),
                TextColumn::make('responsibleUser.name')->label('Responsible')->placeholder('-'),
                TextColumn::make('due_date')->date()->placeholder('-'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add sub-task')
                    ->using(function (array $data): Model {
                        /** @var Task $task */
                        $task = $this->getOwnerRecord();
                        abort_unless(Gate::allows('createSubtask', $task), 403);

                        $subtask = $task->subtasks()->create([
                            'title' => $data['title'],
                            'description' => $data['description'] ?? null,
                            'priority' => $data['priority'] ?? 'medium',
                            'status' => WorkItemStatus::Pending->value,
                            'due_date' => $data['due_date'] ?? null,
                            'responsible_user_id' => $data['responsible_user_id'] ?? auth()->id(),
                            'user_id' => auth()->id(),
                        ]);

                        app(ActivityLogger::class)->record(
                            Task::class,
                            $task,
                            'subtask_created',
                            null,
                            ['subtask_id' => $subtask->id, 'title' => $subtask->title],
                            auth()->id(),
                        );

                        return $subtask;
                    }),
            ]);
    }
}
