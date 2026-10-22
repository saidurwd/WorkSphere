<?php

namespace Modules\Tasks\Filament\Resources\Transfers;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Support\Facades\Storage;
use Modules\Tasks\Filament\Resources\Transfers\Pages\CreateTaskTransfer;
use Modules\Tasks\Filament\Resources\Transfers\Pages\ListTaskTransfers;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskTransfer;
use UnitEnum;

/**
 * Hand-overs of a task from one responsible user to another.
 *
 * Creating one needs `create` on tasks AND `transfer` on the chosen task, and it
 * reassigns the task. The list is limited to transfers of tasks the viewer can
 * see: the Blade screen listed every transfer to every signed-in user, which
 * exposed the reasons and remarks of tasks they could not otherwise open.
 */
class TaskTransferResource extends Resource
{
    protected static ?string $model = TaskTransfer::class;

    protected static ?string $modelLabel = 'task transfer';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static string|UnitEnum|null $navigationGroup = 'Tasks';

    protected static ?string $navigationLabel = 'Transfers';

    protected static ?int $navigationSort = 11;

    protected static ?string $slug = 'task-transfers';

    public static function canCreate(): bool
    {
        return Gate::allows('create', Task::class);
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return Gate::allows('transfer', $record->task);
    }

    public static function getEloquentQuery(): Builder
    {
        // The whole task is loaded, not a column subset: the `transfer` policy reads
        // its owner and responsible user.
        $query = parent::getEloquentQuery()->with(['task', 'fromUser:id,name', 'toUser:id,name', 'transferredBy:id,name']);

        $user = auth()->user();

        if ($user instanceof User && ! $user->hasPermission('task.view')) {
            $query->whereHas('task', fn (Builder $tasks): Builder => $tasks->forUser($user));
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('task_id')
                ->label('Task')
                ->options(fn (): array => Task::query()->orderBy('title')->limit(300)->pluck('title', 'id')->all())
                ->searchable()
                ->required(),
            Select::make('from_user_id')
                ->label('From')
                ->helperText('Defaults to the current responsible user.')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable(),
            Select::make('to_user_id')
                ->label('To')
                ->options(fn (): array => User::query()->orderBy('name')->pluck('name', 'id')->all())
                ->searchable()
                ->required(),
            DatePicker::make('transfer_date')->default(now()),
            Textarea::make('reason')->required()->maxLength(1000)->columnSpanFull(),
            Textarea::make('remarks')->maxLength(1000)->columnSpanFull(),
            TextInput::make('file_title')->maxLength(255),
            FileUpload::make('file_attache')
                ->label('Attachment')
                ->disk(config('uploads.disk'))
                ->directory(config('uploads.directory'))
                ->acceptedFileTypes([
                    'application/pdf', 'text/plain', 'application/zip', 'image/jpeg', 'image/png', 'image/gif',
                    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                ])
                ->maxSize(10240),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transfer_date')->date()->sortable(),
                TextColumn::make('task.title')->label('Task')->limit(50),
                TextColumn::make('fromUser.name')->label('From')->placeholder('-'),
                TextColumn::make('toUser.name')->label('To'),
                TextColumn::make('transferredBy.name')->label('By')->toggleable(),
                TextColumn::make('reason')->limit(60)->toggleable(),
            ])
            ->defaultSort('transfer_date', 'desc')
            ->filters([
                SelectFilter::make('task_id')
                    ->label('Task')
                    ->options(fn (): array => Task::query()->orderBy('title')->limit(300)->pluck('title', 'id')->all())
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (TaskTransfer $record): bool => Gate::allows('transfer', $record->task))
                    ->action(function (TaskTransfer $record): void {
                        abort_unless(Gate::allows('transfer', $record->task), 403);

                        $disk = config('uploads.disk');

                        if ($record->file_attache && Storage::disk($disk)->exists($record->file_attache)) {
                            Storage::disk($disk)->delete($record->file_attache);
                        }

                        $record->delete();

                        Notification::make()->title('Transfer deleted')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaskTransfers::route('/'),
            'create' => CreateTaskTransfer::route('/create'),
        ];
    }
}
