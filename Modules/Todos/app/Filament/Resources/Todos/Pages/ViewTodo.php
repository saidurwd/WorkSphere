<?php

namespace Modules\Todos\Filament\Resources\Todos\Pages;

use App\Enums\RecurrenceFrequency;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Validation\ValidationException;
use Modules\Todos\Filament\Resources\Todos\TodoResource;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoService;

class ViewTodo extends ViewRecord
{
    protected static string $resource = TodoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            TodoResource::lifecycleAction('complete', 'Complete', 'heroicon-o-check', 'success'),
            TodoResource::lifecycleAction('reopen', 'Reopen', 'heroicon-o-arrow-path', 'gray'),
            TodoResource::lifecycleAction('archive', 'Archive', 'heroicon-o-archive-box', 'gray'),
            TodoResource::lifecycleAction('restore', 'Restore', 'heroicon-o-arrow-uturn-left', 'gray'),
            $this->recurrenceAction(),
        ];
    }

    /**
     * Set or clear the repeat rule (§5.2). Gated by `manageRecurrence`, applied
     * through the service so the activity trail records it.
     */
    protected function recurrenceAction(): Action
    {
        return Action::make('recurrence')
            ->label('Repeat')
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('gray')
            ->authorize('manageRecurrence')
            ->fillForm(fn (Todo $record): array => $record->recurrence_rule ?? ['interval' => 1])
            ->schema([
                Select::make('frequency')
                    ->options(collect(RecurrenceFrequency::cases())->mapWithKeys(fn (RecurrenceFrequency $f): array => [$f->value => $f->label()])->all())
                    ->required(),
                TextInput::make('interval')->numeric()->integer()->minValue(1)->maxValue(365)->default(1),
                CheckboxList::make('by_weekday')
                    ->label('Weekdays')
                    ->options([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'])
                    ->columns(7),
                TextInput::make('by_month_day')->label('Day of month')->numeric()->integer()->minValue(1)->maxValue(31),
                DatePicker::make('start_date')->required(),
                DatePicker::make('end_date')->afterOrEqual('start_date'),
                TextInput::make('max_occurrences')->numeric()->integer()->minValue(1)->maxValue(1000),
            ])
            ->extraModalFooterActions(fn (Action $action): array => [
                $action->makeModalSubmitAction('clear', arguments: ['clear' => true])->label('Stop repeating')->color('danger'),
            ])
            ->action(function (array $data, array $arguments, Todo $record): void {
                $rule = null;

                if (! ($arguments['clear'] ?? false)) {
                    // A repeating To-Do must end somehow, or it generates forever.
                    if (blank($data['end_date'] ?? null) && blank($data['max_occurrences'] ?? null)) {
                        Notification::make()
                            ->title('A repeating To-Do needs either an end date or a maximum number of occurrences.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $rule = array_filter([
                        'frequency' => $data['frequency'],
                        'interval' => (int) ($data['interval'] ?? 1),
                        'by_weekday' => ! empty($data['by_weekday']) ? array_map('intval', $data['by_weekday']) : null,
                        'by_month_day' => $data['by_month_day'] ?? null,
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'] ?? null,
                        'max_occurrences' => $data['max_occurrences'] ?? null,
                        'skip_dates' => $record->recurrence_rule['skip_dates'] ?? null,
                    ], fn (mixed $value): bool => $value !== null);
                }

                try {
                    app(TodoService::class)->update(auth()->user(), $record, ['recurrence_rule' => $rule]);
                } catch (ValidationException $e) {
                    Notification::make()->title(collect($e->errors())->flatten()->first())->danger()->send();

                    return;
                }

                Notification::make()->title($rule === null ? 'Repeat rule cleared' : 'Repeat rule saved')->success()->send();
            });
    }
}
