<?php

namespace Modules\Meetings\Filament\Resources\Meetings\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Meetings\Events\MeetingCancelled;
use Modules\Meetings\Events\MeetingCompleted;
use Modules\Meetings\Events\MeetingStarted;
use Modules\Meetings\Filament\Resources\Meetings\MeetingResource;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Services\MeetingMinutesService;
use Modules\Meetings\Services\MeetingService;

class ViewMeeting extends ViewRecord
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            $this->lifecycleAction('start', 'Start', 'heroicon-o-play', 'success', ['scheduled', 'postponed'], MeetingStarted::class),
            $this->lifecycleAction('complete', 'Complete', 'heroicon-o-check', 'success', ['in_progress'], MeetingCompleted::class),
            $this->lifecycleAction('cancel', 'Cancel meeting', 'heroicon-o-x-mark', 'danger', ['scheduled', 'in_progress', 'postponed'], MeetingCancelled::class),
            ...$this->minutesActions(),
        ];
    }

    /**
     * The minutes workflow: prepare, submit, approve (or return), publish. Each step
     * is its own policy ability, so approving can be held by someone who cannot
     * prepare, and each button shows only in the state where it applies.
     *
     * @return list<Action>
     */
    protected function minutesActions(): array
    {
        $step = function (string $name, string $label, string $ability, array $from, string $color = 'gray') {
            return Action::make('minutes_'.$name)
                ->label($label)
                ->color($color)
                ->requiresConfirmation()
                ->authorize($ability)
                ->visible(fn (Meeting $record): bool => in_array($record->minutes_status, $from, true))
                ->action(function (Meeting $record) use ($name): void {
                    $method = $name;
                    app(MeetingMinutesService::class)->{$method}($record);

                    $this->refreshFormData(['minutes_status']);

                    Notification::make()->title('Minutes updated')->success()->send();
                });
        };

        return [
            $step('prepare', 'Prepare minutes', 'manageMinutes', ['draft']),
            $step('submit', 'Submit minutes', 'submitMinutes', ['prepared']),
            $step('approve', 'Approve minutes', 'approveMinutes', ['submitted', 'under_review'], 'success'),
            Action::make('minutes_return')
                ->label('Return minutes')
                ->color('warning')
                ->authorize('approveMinutes')
                ->visible(fn (Meeting $record): bool => in_array($record->minutes_status, ['submitted', 'under_review'], true))
                ->schema([Textarea::make('comments')->required()->maxLength(2000)])
                ->action(function (array $data, Meeting $record): void {
                    app(MeetingMinutesService::class)->returnMinutes($record, $data['comments']);

                    $this->refreshFormData(['minutes_status']);

                    Notification::make()->title('Minutes returned for revision')->success()->send();
                }),
            $step('publish', 'Publish minutes', 'publishMinutes', ['approved'], 'success'),
        ];
    }

    /**
     * A lifecycle transition: the policy decides who, the service does it, and the
     * event is dispatched so notifications go out. The button only appears in the
     * states where the move means something.
     *
     * @param  list<string>  $from
     * @param  class-string  $event
     */
    protected function lifecycleAction(string $name, string $label, string $icon, string $color, array $from, string $event): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->requiresConfirmation()
            ->authorize('transition')
            ->visible(fn (Meeting $record): bool => in_array($record->status, $from, true))
            ->action(function (Meeting $record) use ($name, $event): void {
                $method = $name;
                app(MeetingService::class)->{$method}($record);

                event(new $event($record->fresh()));

                $this->refreshFormData(['status']);

                Notification::make()->title('Meeting updated')->success()->send();
            });
    }
}
