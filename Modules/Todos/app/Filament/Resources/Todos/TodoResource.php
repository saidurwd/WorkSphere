<?php

namespace Modules\Todos\Filament\Resources\Todos;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Models\Department;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Modules\Todos\Filament\Resources\Todos\Pages\CreateTodo;
use Modules\Todos\Filament\Resources\Todos\Pages\EditTodo;
use Modules\Todos\Filament\Resources\Todos\Pages\ListTodos;
use Modules\Todos\Filament\Resources\Todos\Pages\ViewTodo;
use Modules\Todos\Models\Todo;
use Modules\Todos\Policies\TodoPolicy;
use Modules\Todos\Services\TodoService;

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

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['assignee:id,name', 'department:id,department_name']);
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
            ])
            ->defaultSort('due_date')
            ->filters([
                SelectFilter::make('status')->options(collect(WorkItemStatus::cases())->mapWithKeys(fn (WorkItemStatus $s): array => [$s->value => $s->label()])->all()),
                SelectFilter::make('priority')->options(collect(Priority::cases())->mapWithKeys(fn (Priority $p): array => [$p->value => $p->label()])->all()),
                SelectFilter::make('assignee_id')->label('Assignee')->relationship('assignee', 'name')->searchable()->preload(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                static::lifecycleAction('complete', 'Complete', 'heroicon-o-check', 'success'),
                static::lifecycleAction('reopen', 'Reopen', 'heroicon-o-arrow-path', 'gray'),
            ]);
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
