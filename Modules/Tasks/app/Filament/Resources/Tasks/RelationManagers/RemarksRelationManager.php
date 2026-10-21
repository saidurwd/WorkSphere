<?php

namespace Modules\Tasks\Filament\Resources\Tasks\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Services\TaskRemarkSynchroniser;

/**
 * A task's remarks. Append-only; each one is mirrored into the shared comments
 * thread by {@see TaskRemarkSynchroniser}, exactly as the Blade form did.
 */
class RemarksRelationManager extends RelationManager
{
    protected static string $relationship = 'remarks';

    protected static ?string $title = 'Remarks';

    public function isReadOnly(): bool
    {
        return ! Gate::allows('addRemark', $this->getOwnerRecord());
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Textarea::make('remark')->required()->maxLength(2000)->rows(3),
            FileUpload::make('attachment')->disk('public')->directory('task_remarks')->maxSize(10240),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('user:id,name'))
            ->columns([
                TextColumn::make('user.name')->label('From'),
                TextColumn::make('remark')->wrap(),
                TextColumn::make('attachment')->formatStateUsing(fn (?string $state): ?string => $state ? basename($state) : null)
                    ->url(fn (Model $record): ?string => $record->attachment ? asset('storage/'.$record->attachment) : null, true)
                    ->placeholder('-'),
                TextColumn::make('created_at')->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Add remark')
                    ->using(function (array $data): Model {
                        /** @var Task $task */
                        $task = $this->getOwnerRecord();
                        abort_unless(Gate::allows('addRemark', $task), 403);

                        $remark = $task->remarks()->create([...$data, 'user_id' => auth()->id()]);

                        app(TaskRemarkSynchroniser::class)->mirror($task, $remark);

                        return $remark;
                    }),
            ]);
    }
}
