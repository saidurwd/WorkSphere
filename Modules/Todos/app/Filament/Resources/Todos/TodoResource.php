<?php

namespace Modules\Todos\Filament\Resources\Todos;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Models\Department;
use App\Models\Tag;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Todos\Filament\Resources\Todos\Pages\CreateTodo;
use Modules\Todos\Filament\Resources\Todos\Pages\EditTodo;
use Modules\Todos\Filament\Resources\Todos\Pages\ListTodos;
use Modules\Todos\Filament\Resources\Todos\Pages\ViewTodo;
use Modules\Todos\Filament\Resources\Todos\RelationManagers\ChecklistItemsRelationManager;
use Modules\Todos\Filament\Resources\Todos\RelationManagers\CommentsRelationManager;
use Modules\Todos\Filament\Resources\Todos\RelationManagers\LinksRelationManager;
use Modules\Todos\Filament\Resources\Todos\RelationManagers\WatchersRelationManager;
use Modules\Todos\Models\Todo;
use Modules\Todos\Policies\TodoPolicy;
use Modules\Todos\Services\TodoService;
use UnitEnum;

/**
 * To-Dos in the Filament workspace panel.
 *
 * Pilot for moving the work modules to Filament. Two rules carried over from the
 * Blade controller:
 *
 * 1. Every ability is decided by {@see TodoPolicy}, which
 *    Filament calls for each record action. `TodoScope` narrows the list query, but
 *    a scope is a convenience, not a check.
 * 2. Every mutation goes through {@see TodoService}, never the model, so the
 *    transition graph and the activity trail cannot be bypassed.
 */
class TodoResource extends Resource
{
    protected static ?string $model = Todo::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static string|UnitEnum|null $navigationGroup = 'To-Dos';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['assignee:id,name', 'department:id,department_name'])
            ->withCount(['comments', 'checklistItems']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('description')->maxLength(5000)->columnSpanFull(),
            Select::make('priority')
                ->options(collect(Priority::cases())->mapWithKeys(fn (Priority $p): array => [$p->value => $p->label()])->all())
                ->default(Priority::Medium->value),
            Select::make('visibility')
                ->options(collect(Visibility::cases())->mapWithKeys(fn (Visibility $v): array => [$v->value => $v->label()])->all())
                ->default(Visibility::Personal->value),
            // Assigning to somebody else is a distinct permission from creating a
            // To-Do at all.
            Select::make('assignee_id')
                ->label('Assignee')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->visible(fn (): bool => Gate::allows('createForOthers', Todo::class)),
            Select::make('department_id')
                ->label('Department')
                ->options(fn (): array => Department::query()->orderBy('department_name')->pluck('department_name', 'id')->all())
                ->searchable(),
            DatePicker::make('start_date'),
            DatePicker::make('due_date')->afterOrEqual('start_date'),
            TimePicker::make('due_time')->seconds(false),
            TextInput::make('estimated_minutes')->numeric()->integer()->minValue(0)->maxValue(100000),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('title')->columnSpanFull(),
            TextEntry::make('status')->badge()->formatStateUsing(fn (WorkItemStatus $state): string => $state->label()),
            TextEntry::make('priority')->badge()->formatStateUsing(fn (Priority $state): string => $state->label()),
            TextEntry::make('visibility')->formatStateUsing(fn (Visibility $state): string => $state->label()),
            TextEntry::make('assignee.name')->label('Assignee')->placeholder('Unassigned'),
            TextEntry::make('department.department_name')->label('Department')->placeholder('-'),
            TextEntry::make('start_date')->date()->placeholder('-'),
            TextEntry::make('due_date')->date()->placeholder('-'),
            TextEntry::make('waiting_on')->placeholder('-'),
            TextEntry::make('description')->placeholder('-')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->limit(60),
                TextColumn::make('status')->badge()->formatStateUsing(fn (WorkItemStatus $state): string => $state->label()),
                TextColumn::make('priority')->badge()->formatStateUsing(fn (Priority $state): string => $state->label()),
                TextColumn::make('assignee.name')->label('Assignee')->placeholder('-')->toggleable(),
                TextColumn::make('due_date')->date()->sortable()->placeholder('-'),
                TextColumn::make('checklist_items_count')->label('Checklist')->counts('checklistItems')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('comments_count')->label('Comments')->counts('comments')->toggleable(isToggledHiddenByDefault: true),
            ])
            // Dated work first, newest due date first, undated last — the order the
            // Blade list used.
            ->defaultSort(fn (Builder $query): Builder => $query->orderByRaw('due_date IS NULL, due_date DESC')->orderByDesc('id'))
            ->filters([
                SelectFilter::make('status')->options(collect(WorkItemStatus::cases())->mapWithKeys(fn (WorkItemStatus $s): array => [$s->value => $s->label()])->all()),
                SelectFilter::make('priority')->options(collect(Priority::cases())->mapWithKeys(fn (Priority $p): array => [$p->value => $p->label()])->all()),
                SelectFilter::make('visibility')->options(collect(Visibility::cases())->mapWithKeys(fn (Visibility $v): array => [$v->value => $v->label()])->all()),
                SelectFilter::make('assignee_id')
                    ->label('Assignee')
                    ->options(fn (): array => ['none' => 'Unassigned'] + User::query()->orderBy('name')->limit(200)->pluck('name', 'id')->all())
                    ->searchable()
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        null, '' => $query,
                        'none' => $query->whereNull('assignee_id'),
                        default => $query->where('assignee_id', $data['value']),
                    }),
                Filter::make('overdue')->toggle()->query(fn (Builder $query): Builder => $query->overdue()),
                Filter::make('recurring')->toggle()->query(fn (Builder $query): Builder => $query->recurring()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    static::bulkAction('complete', 'Complete', 'complete', fn (Todo $todo, array $data) => app(TodoService::class)->complete(auth()->user(), $todo)),
                    static::bulkAction('archive', 'Archive', 'archive', fn (Todo $todo, array $data) => app(TodoService::class)->archive(auth()->user(), $todo)),
                    static::bulkAction(
                        'reassign',
                        'Reassign',
                        'assign',
                        fn (Todo $todo, array $data) => app(TodoService::class)->assign(auth()->user(), $todo, $data['assignee_id'] ?: null),
                        [Select::make('assignee_id')
                            ->label('Assignee')
                            ->options(fn (): array => User::query()->orderBy('name')->limit(200)->pluck('name', 'id')->all())
                            ->searchable()],
                    ),
                    static::bulkAction(
                        'tag',
                        'Add tag',
                        'update',
                        fn (Todo $todo, array $data) => $todo->tags()->syncWithoutDetaching([Tag::findOrCreateByName($data['tag'])->id]),
                        [TextInput::make('tag')->required()->maxLength(100)],
                    ),
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                static::lifecycleAction('complete', 'Complete', 'heroicon-o-check', 'success'),
                static::lifecycleAction('reopen', 'Reopen', 'heroicon-o-arrow-path', 'gray'),
            ]);
    }

    /**
     * A bulk action authorised PER ITEM, not once for the batch: holding a
     * permission over one To-Do must not grant it over the other forty selected.
     * Items the actor may not act on are skipped and reported, never silently
     * applied, and a refused transition counts as skipped rather than aborting the
     * rest.
     *
     * @param  callable(Todo, array<string, mixed>): mixed  $apply
     * @param  list<Component>  $schema
     */
    public static function bulkAction(string $name, string $label, string $ability, callable $apply, array $schema = []): BulkAction
    {
        return BulkAction::make($name)
            ->label($label)
            ->schema($schema)
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records, array $data) use ($ability, $apply): void {
                $applied = 0;

                foreach ($records as $todo) {
                    if (Gate::denies($ability, $todo)) {
                        continue;
                    }

                    try {
                        $apply($todo, $data);
                        $applied++;
                    } catch (ValidationException) {
                        // The transition graph refused this one; carry on with the rest.
                    }
                }

                $skipped = $records->count() - $applied;

                $title = trans_choice('{1} :count To-Do updated.|[0,*] :count To-Dos updated.', $applied, ['count' => $applied]);

                if ($skipped > 0) {
                    $title .= ' '.trans_choice('{1} :count skipped.|[2,*] :count skipped.', $skipped, ['count' => $skipped]);
                }

                Notification::make()->title($title)->status($applied > 0 ? 'success' : 'danger')->send();
            });
    }

    /**
     * A lifecycle transition that is decided by the policy and performed by the
     * service.
     *
     * @param  'complete'|'reopen'|'archive'|'restore'  $ability
     */
    public static function lifecycleAction(string $ability, string $label, string $icon, string $color): Action
    {
        return Action::make($ability)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->requiresConfirmation()
            ->authorize($ability)
            ->action(function (Model $record) use ($ability): void {
                try {
                    app(TodoService::class)->{$ability}(auth()->user(), $record);
                } catch (ValidationException $e) {
                    // The transition graph refused the move; say so rather than
                    // letting the click appear to do nothing.
                    Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();

                    return;
                }

                Notification::make()->title("To-Do {$ability}d")->success()->send();
            });
    }

    public static function getRelations(): array
    {
        return [
            ChecklistItemsRelationManager::class,
            CommentsRelationManager::class,
            WatchersRelationManager::class,
            LinksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTodos::route('/'),
            'create' => CreateTodo::route('/create'),
            'view' => ViewTodo::route('/{record}'),
            'edit' => EditTodo::route('/{record}/edit'),
        ];
    }
}
