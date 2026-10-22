<?php

namespace Modules\Meetings\Filament\Resources\Meetings\Pages;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Meetings\Events\MeetingCancelled;
use Modules\Meetings\Events\MeetingCompleted;
use Modules\Meetings\Events\MeetingStarted;
use Modules\Meetings\Filament\Resources\Meetings\MeetingResource;
use Modules\Meetings\Models\Meeting;
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
