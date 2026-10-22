<?php

namespace Modules\Tasks\Filament\Resources\Tasks;

use App\Enums\WorkItemStatus;
use App\Models\User;
use App\Policies\TaskPolicy;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Modules\Projects\Models\Project;
use Modules\Tasks\Filament\Resources\Tasks\Pages\CreateTask;
use Modules\Tasks\Filament\Resources\Tasks\Pages\EditTask;
use Modules\Tasks\Filament\Resources\Tasks\Pages\ListTasks;
use Modules\Tasks\Filament\Resources\Tasks\Pages\ViewTask;
use Modules\Tasks\Filament\Resources\Tasks\RelationManagers\RemarksRelationManager;
use Modules\Tasks\Filament\Resources\Tasks\RelationManagers\SubtasksRelationManager;
use Modules\Tasks\Filament\Resources\Tasks\RelationManagers\TagsRelationManager;
use Modules\Tasks\Filament\Resources\Tasks\RelationManagers\TimeEntriesRelationManager;
use Modules\Tasks\Filament\Resources\Tasks\RelationManagers\WatchersRelationManager;
use Modules\Tasks\Models\Task;
use UnitEnum;

/**
 * Tasks in the Filament workspace panel.
 *
 * Every ability is decided by {@see TaskPolicy}. The list is
 * narrowed the way the Blade list was: a user without `task.view` sees only tasks
 * they own, are responsible for, or watch.
 */
class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Tasks';

    protected static ?int $navigationSort = 10;

    public const PRIORITIES = ['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'];

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['responsibleUser:id,name', 'project:id,name']);

        $user = auth()->user();

        // A super-admin can act on any task, so the lookup must not hide one from them.
        if ($user instanceof User && ! $user->hasPermission('task.view') && ! $user->hasRole('super-admin')) {
            $query->forUser($user);
        }

        return $query;
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return WorkItemStatus::taskOptions();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('title')->required()->maxLength(255)->columnSpanFull(),
            Textarea::make('description')->columnSpanFull(),
            Select::make('priority')->options(self::PRIORITIES)->default('medium')->required(),
            Select::make('status')->options(self::statusOptions())->default(WorkItemStatus::Pending->value)->required(),
            DatePicker::make('due_date')->required(),
            Select::make('responsible_user_id')
                ->label('Responsible')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->required(),
            Select::make('project_id')
                ->label('Project')
                ->options(fn (): array => Project::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
            FileUpload::make('attachment')->disk('public')->directory('task-attachments')->maxSize(10240),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            TextEntry::make('task_no')->label('No.')->placeholder('-'),
            TextEntry::make('title')->columnSpanFull(),
            TextEntry::make('status')->badge()->formatStateUsing(fn (WorkItemStatus $state): string => $state->label()),
            TextEntry::make('priority')->badge()->formatStateUsing(fn (string $state): string => ucfirst($state)),
            TextEntry::make('due_date')->date(),
            TextEntry::make('responsibleUser.name')->label('Responsible')->placeholder('-'),
            TextEntry::make('user.name')->label('Created by')->placeholder('-'),
            TextEntry::make('project.name')->label('Project')->placeholder('-'),
            TextEntry::make('parent.title')->label('Parent task')->placeholder('-'),
            TextEntry::make('completed_at')->dateTime()->placeholder('-'),
            TextEntry::make('description')->placeholder('-')->columnSpanFull(),
            TextEntry::make('attachment')
                ->placeholder('-')
                ->formatStateUsing(fn (?string $state): ?string => $state ? basename($state) : null)
                ->url(fn (Task $record): ?string => $record->attachment ? asset('storage/'.$record->attachment) : null, true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('task_no')->label('No.')->searchable()->toggleable(),
                TextColumn::make('title')->searchable()->limit(60)->sortable(),
                TextColumn::make('status')->badge()->formatStateUsing(fn (WorkItemStatus $state): string => $state->label()),
                TextColumn::make('priority')->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'high' => 'danger',
                        'medium' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('responsibleUser.name')->label('Responsible')->toggleable(),
                TextColumn::make('project.name')->label('Project')->toggleable(),
                TextColumn::make('due_date')->date()->sortable(),
            ])
            ->defaultSort('due_date', 'desc')
            ->filters([
                SelectFilter::make('status')->options(self::statusOptions()),
                SelectFilter::make('priority')->options(self::PRIORITIES),
                SelectFilter::make('responsible_user_id')->label('Responsible')->relationship('responsibleUser', 'name')->searchable()->preload(),
                SelectFilter::make('project_id')->label('Project')->relationship('project', 'name')->searchable()->preload(),
                Filter::make('due')
                    ->schema([
                        Select::make('when')->options([
                            'today' => 'Today',
                            'this_week' => 'This week',
                            'this_month' => 'This month',
                            'future' => 'Future',
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $today = now()->startOfDay();

                        return match ($data['when'] ?? null) {
                            'today' => $query->whereDate('due_date', $today),
                            'this_week' => $query->whereBetween('due_date', [$today->copy()->startOfWeek(), $today->copy()->endOfWeek()]),
                            'this_month' => $query->whereMonth('due_date', $today->month)->whereYear('due_date', $today->year),
                            'future' => $query->whereDate('due_date', '>', $today),
                            default => $query,
                        };
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SubtasksRelationManager::class,
            RemarksRelationManager::class,
            TimeEntriesRelationManager::class,
            WatchersRelationManager::class,
            TagsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTasks::route('/'),
            'create' => CreateTask::route('/create'),
            'view' => ViewTask::route('/{record}'),
            'edit' => EditTask::route('/{record}/edit'),
        ];
    }
}
