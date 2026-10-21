<?php

namespace Modules\Tasks\Filament\Resources\Tasks\Pages;

use App\Services\ActivityLogger;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Tasks\Filament\Resources\Tasks\TaskResource;
use Modules\Tasks\Models\Task;

class ViewTask extends ViewRecord
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            $this->reparentAction(),
        ];
    }

    /**
     * Move the task under another parent, or detach it (leave the parent empty).
     *
     * `Task::wouldCreateCycle()` is the single enforcement point for the "own
     * ancestor" rule, because it sees the whole ancestry, not just the parent.
     */
    protected function reparentAction(): Action
    {
        return Action::make('reparent')
            ->label('Move')
            ->icon('heroicon-o-arrows-right-left')
            ->color('gray')
            ->authorize('reparent')
            ->fillForm(fn (Task $record): array => ['parent_id' => $record->parent_id])
            ->schema([
                Select::make('parent_id')
                    ->label('Parent task')
                    ->helperText('Leave empty to detach.')
                    ->options(fn (Task $record): array => Task::query()
                        ->whereKeyNot($record->getKey())
                        ->orderBy('title')
                        ->limit(200)
                        ->pluck('title', 'id')
                        ->all())
                    ->searchable()
                    ->exists('tasks', 'id'),
            ])
            ->action(function (array $data, Task $record): void {
                $parentId = filled($data['parent_id'] ?? null) ? (int) $data['parent_id'] : null;

                if ($record->wouldCreateCycle($parentId)) {
                    Notification::make()->title('That move would make the task its own ancestor.')->danger()->send();

                    return;
                }

                $before = $record->parent_id;

                $record->forceFill(['parent_id' => $parentId])->save();

                app(ActivityLogger::class)->record(
                    Task::class,
                    $record,
                    'reparented',
                    ['parent_id' => $before],
                    ['parent_id' => $parentId],
                    auth()->id(),
                );

                Notification::make()->title($parentId === null ? 'Sub-task detached' : 'Task moved')->success()->send();
            });
    }
}
