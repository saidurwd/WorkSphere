<?php

namespace Modules\Meetings\Filament\Resources\Meetings\RelationManagers;

use App\Models\Department;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;
use Modules\Meetings\Events\ActionItemAssigned;
use Modules\Meetings\Events\ActionItemCompleted;
use Modules\Meetings\Events\ActionItemCreated;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Meetings\Services\MeetingActionTaskService;
use Modules\Tasks\Filament\Resources\Tasks\TaskResource;
use Modules\Tasks\Models\Task;

/**
 * Follow-ups agreed at the meeting. Creating one needs `manageActionItems`; the
 * assignee may update their own; deleting needs `manageActionItems`; linking a task
 * needs `assignActionItems` (the `linkTask` ability). Completion and assignment fire
 * the same events the Blade screens did.
 */
class ActionItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'actionItems';

    protected static ?string $title = 'Action items';

    /**
     * Not read-only as a whole: the assignee may update their own item without
     * being able to add or delete any. Each action carries its own policy check.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('action_no')->label('No.')->numeric()->integer()->minValue(1)->required()
                ->default(fn (): int => ((int) $this->getOwnerRecord()->actionItems()->max('action_no')) + 1),
            TextInput::make('title')->required()->maxLength(255),
            Textarea::make('description')->columnSpanFull(),
            Select::make('assigned_to')->label('Assigned to')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
            Select::make('assigned_department_id')->label('Department')
                ->options(fn (): array => Department::query()->orderBy('department_name')->pluck('department_name', 'id')->all())
                ->searchable(),
            Select::make('priority')->options(['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'])->default('medium')->required(),
            Select::make('status')->options(['open' => 'Open', 'in_progress' => 'In progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'])->default('open')->required(),
            DatePicker::make('start_date'),
            DatePicker::make('due_date'),
            TextInput::make('completion_percentage')->numeric()->integer()->minValue(0)->maxValue(100)->default(0)->required(),
            Textarea::make('remarks')->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['assignedTo:id,name', 'task:id,task_no,title']))
            ->columns([
                TextColumn::make('action_no')->label('No.'),
                TextColumn::make('title')->limit(50),
                TextColumn::make('assignedTo.name')->label('Assigned to')->placeholder('-'),
                TextColumn::make('priority')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('due_date')->date()->placeholder('-'),
                TextColumn::make('task.task_no')->label('Task')->placeholder('-')->url(fn (MeetingActionItem $record): ?string => $record->task_id ? TaskResource::getUrl('view', ['record' => $record->task_id]) : null),
            ])
            ->defaultSort('action_no')
            ->headerActions([
                // The related-model policy's `create` needs an item to inspect, which
                // does not exist yet; adding one is a question about the meeting.
                CreateAction::make()
                    ->label('Add action item')
                    ->authorize(fn (): bool => Gate::allows('manageActionItems', $this->getOwnerRecord()))
                    ->mutateDataUsing(function (array $data): array {
                        abort_unless(Gate::allows('manageActionItems', $this->getOwnerRecord()), 403);

                        return $data + ['created_by' => auth()->id(), 'updated_by' => auth()->id()];
                    })
                    ->after(fn (MeetingActionItem $record) => event(new ActionItemCreated($record))),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (MeetingActionItem $record): bool => Gate::allows('update', $record))
                    ->using(function (MeetingActionItem $record, array $data): MeetingActionItem {
                        abort_unless(Gate::allows('update', $record), 403);

                        $oldStatus = $record->status;
                        $oldAssignedTo = $record->assigned_to;

                        $record->update($data + ['updated_by' => auth()->id()]);

                        if ($oldStatus !== 'completed' && $record->status === 'completed') {
                            event(new ActionItemCompleted($record->fresh()));
                        }

                        if ($oldAssignedTo != $record->assigned_to && ! empty($record->assigned_to)) {
                            event(new ActionItemAssigned($record->fresh()));
                        }

                        return $record;
                    }),
                Action::make('createTask')
                    ->label('Create task')
                    ->icon('heroicon-o-plus')
                    ->visible(fn (MeetingActionItem $record): bool => $record->task_id === null && Gate::allows('linkTask', $record))
                    ->fillForm(fn (MeetingActionItem $record): array => [
                        'title' => $record->title,
                        'description' => $record->description,
                        'priority' => in_array($record->priority, ['low', 'medium', 'high'], true) ? $record->priority : 'high',
                        'due_date' => $record->due_date?->toDateString(),
                        'responsible_user_id' => $record->assigned_to,
                    ])
                    ->schema([
                        TextInput::make('title')->required()->maxLength(255),
                        Textarea::make('description'),
                        Select::make('priority')->options(TaskResource::PRIORITIES)->required(),
                        Select::make('responsible_user_id')->label('Responsible')
                            ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())->searchable(),
                        DatePicker::make('due_date'),
                    ])
                    ->action(function (array $data, MeetingActionItem $record): void {
                        abort_unless(Gate::allows('linkTask', $record), 403);

                        // The service builds the task from the action item and links the
                        // two in one transaction; what the person edited overrides it.
                        app(MeetingActionTaskService::class)->createTask($record, $data + [
                            'user_id' => auth()->id(),
                            'task_no' => 'TASK-'.str_pad((string) (Task::count() + 1), 4, '0', STR_PAD_LEFT),
                        ]);

                        Notification::make()->title('Task created and linked')->success()->send();
                    }),
                Action::make('linkTask')
                    ->label('Link task')
                    ->icon('heroicon-o-link')
                    ->visible(fn (MeetingActionItem $record): bool => $record->task_id === null && Gate::allows('linkTask', $record))
                    ->schema([
                        Select::make('task_id')->label('Task')
                            ->options(fn (): array => TaskResource::getEloquentQuery()->orderByDesc('created_at')->limit(200)->pluck('title', 'id')->all())
                            ->searchable()->required(),
                    ])
                    ->action(function (array $data, MeetingActionItem $record): void {
                        abort_unless(Gate::allows('linkTask', $record), 403);

                        // Only a task the person can see may be linked, so the link
                        // cannot be used to attach (and so reveal) someone else's.
                        abort_unless(TaskResource::getEloquentQuery()->whereKey($data['task_id'])->exists(), 403);

                        $service = app(MeetingActionTaskService::class);

                        try {
                            $service->linkTask($record, Task::query()->findOrFail($data['task_id']));
                        } catch (InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();

                            return;
                        }

                        // Take on the task's state straight away, so a task that is
                        // already done does not leave an open action item behind.
                        $service->syncStatus($record->refresh());

                        Notification::make()->title('Task linked')->success()->send();
                    }),
                Action::make('unlinkTask')
                    ->label('Unlink task')
                    ->icon('heroicon-o-link-slash')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (MeetingActionItem $record): bool => $record->task_id !== null && Gate::allows('linkTask', $record))
                    ->action(function (MeetingActionItem $record): void {
                        abort_unless(Gate::allows('linkTask', $record), 403);

                        $record->update(['task_id' => null]);

                        Notification::make()->title('Task unlinked')->success()->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (MeetingActionItem $record): bool => Gate::allows('delete', $record))
                    ->before(fn (MeetingActionItem $record) => abort_unless(Gate::allows('delete', $record), 403)),
            ]);
    }
}
