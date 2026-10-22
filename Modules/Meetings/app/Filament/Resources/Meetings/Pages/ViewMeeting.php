<?php

namespace Modules\Meetings\Filament\Resources\Meetings\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Modules\Meetings\Events\MeetingCancelled;
use Modules\Meetings\Events\MeetingCompleted;
use Modules\Meetings\Events\MeetingCreated;
use Modules\Meetings\Events\MeetingStarted;
use Modules\Meetings\Events\MinutesApproved;
use Modules\Meetings\Events\MinutesPublished;
use Modules\Meetings\Events\MinutesReturned;
use Modules\Meetings\Events\MinutesSubmitted;
use Modules\Meetings\Filament\Resources\Meetings\MeetingResource;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Services\MeetingApprovalService;
use Modules\Meetings\Services\MeetingMinutesService;
use Modules\Meetings\Services\MeetingRecurrenceService;
use Modules\Meetings\Services\MeetingService;

class ViewMeeting extends ViewRecord
{
    protected static string $resource = MeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('print')
                ->label('Print')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->authorize('print')
                ->url(fn (Meeting $record): string => route('meetings.print', $record), true),
            $this->lifecycleAction('start', 'Start', 'heroicon-o-play', 'success', ['scheduled', 'postponed'], MeetingStarted::class),
            $this->lifecycleAction('complete', 'Complete', 'heroicon-o-check', 'success', ['in_progress'], MeetingCompleted::class),
            $this->lifecycleAction('cancel', 'Cancel meeting', 'heroicon-o-x-mark', 'danger', ['scheduled', 'in_progress', 'postponed'], MeetingCancelled::class),
            ...$this->recurrenceActions(),
            ...$this->minutesActions(),
        ];
    }

    /**
     * Make the meeting repeat, stop it repeating, and create the next one in the
     * series. Creating the next meeting is an explicit step rather than something
     * that happens on its own, so nothing appears in anyone's calendar unasked.
     *
     * @return list<Action>
     */
    protected function recurrenceActions(): array
    {
        $recurrence = app(MeetingRecurrenceService::class);

        return [
            Action::make('repeat')
                ->label('Repeat')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->authorize('update')
                ->visible(fn (Meeting $record): bool => ! in_array($record->status, ['completed', 'cancelled'], true))
                ->fillForm(['recurrence_type' => 'weekly', 'recurrence_interval' => 1])
                ->schema([
                    Select::make('recurrence_type')
                        ->label('Repeats')
                        ->options(['daily' => 'Daily', 'weekly' => 'Weekly', 'biweekly' => 'Every two weeks', 'monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'])
                        ->required(),
                    TextInput::make('recurrence_interval')->label('Every')->numeric()->minValue(1)->maxValue(52)->required(),
                    DatePicker::make('end_date')->label('Until')->after('today'),
                    TextInput::make('occurrences')->label('Or after this many meetings')->numeric()->minValue(2)->maxValue(500),
                ])
                ->action(function (array $data, Meeting $record) use ($recurrence): void {
                    $recurrence->setRecurrence($record, $data);

                    Notification::make()->title('Meeting set to repeat')->success()->send();
                }),

            Action::make('stop_repeating')
                ->label('Stop repeating')
                ->icon('heroicon-o-stop')
                ->color('gray')
                ->requiresConfirmation()
                ->authorize('update')
                ->visible(fn (Meeting $record): bool => $record->recurrence()->where('is_active', true)->exists())
                ->action(function (Meeting $record) use ($recurrence): void {
                    $recurrence->stopRecurrence($record);

                    Notification::make()->title('The meeting no longer repeats')->success()->send();
                }),

            Action::make('create_next')
                ->label('Create next meeting')
                ->icon('heroicon-o-calendar-days')
                ->color('gray')
                ->requiresConfirmation()
                ->authorize('create', Meeting::class)
                ->visible(fn (Meeting $record): bool => $recurrence->nextDateAfter($record) !== null)
                ->action(function (Meeting $record) use ($recurrence): void {
                    $next = $recurrence->createNextMeeting($record);

                    if ($next === null) {
                        Notification::make()->title('The next meeting already exists')->warning()->send();

                        return;
                    }

                    event(new MeetingCreated($next));

                    Notification::make()->title("Created {$next->meeting_no}")->success()
                        ->actions([Action::make('open')->url(MeetingResource::getUrl('view', ['record' => $next]))])
                        ->send();
                }),
        ];
    }

    /**
     * The minutes workflow: prepare, submit (optionally naming approvers), approve or
     * return, publish. Each step is its own policy ability, so approving can be held
     * by someone who cannot prepare, and each button shows only in the state where it
     * applies. Every step announces itself with its event so the notifications go out.
     *
     * @return list<Action>
     */
    protected function minutesActions(): array
    {
        $approvals = app(MeetingApprovalService::class);
        $minutes = app(MeetingMinutesService::class);

        $refresh = function (): void {
            $this->refreshFormData(['minutes_status']);

            Notification::make()->title('Minutes updated')->success()->send();
        };

        return [
            Action::make('minutes_prepare')
                ->label('Prepare minutes')
                ->color('gray')
                ->requiresConfirmation()
                ->authorize('manageMinutes')
                ->visible(fn (Meeting $record): bool => $record->minutes_status === 'draft')
                ->action(function (Meeting $record) use ($minutes, $refresh): void {
                    $minutes->prepare($record);
                    $refresh();
                }),

            Action::make('minutes_submit')
                ->label('Submit minutes')
                ->color('gray')
                ->authorize('submitMinutes')
                ->visible(fn (Meeting $record): bool => $record->minutes_status === 'prepared')
                ->schema([
                    Select::make('approvers')
                        ->label('Approvers')
                        ->helperText('Everyone named must approve. Leave empty to let anyone with approval rights do it.')
                        ->multiple()
                        ->options(fn (): array => User::query()
                            ->whereHas('roles.permissions', fn ($permissions) => $permissions->where('permission_name', 'meeting.approve_minutes'))
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->all()),
                ])
                ->action(function (array $data, Meeting $record) use ($approvals, $minutes, $refresh): void {
                    $approverIds = array_map('intval', $data['approvers'] ?? []);

                    $approverIds === []
                        ? $minutes->submit($record)
                        : $approvals->submitForApproval($record, $approverIds);

                    event(new MinutesSubmitted($record->fresh()));
                    $refresh();
                }),

            Action::make('minutes_approve')
                ->label('Approve minutes')
                ->color('success')
                ->authorize('approveMinutes')
                ->visible(fn (Meeting $record): bool => in_array($record->minutes_status, ['submitted', 'under_review'], true) && $this->mayDecide($record))
                ->schema([Textarea::make('comments')->maxLength(2000)])
                ->action(function (array $data, Meeting $record) use ($approvals, $minutes, $refresh): void {
                    $approvals->isWaitingOn($record, auth()->user())
                        ? $approvals->approve($record, $data['comments'] ?? null)
                        : $minutes->approve($record);

                    // Announced once the minutes are actually approved, not on each
                    // partial sign-off.
                    if ($record->fresh()->minutes_status === 'approved') {
                        event(new MinutesApproved($record->fresh()));
                    }

                    $refresh();
                }),

            Action::make('minutes_return')
                ->label('Return minutes')
                ->color('warning')
                ->authorize('approveMinutes')
                ->visible(fn (Meeting $record): bool => in_array($record->minutes_status, ['submitted', 'under_review'], true) && $this->mayDecide($record))
                ->schema([Textarea::make('comments')->required()->maxLength(2000)])
                ->action(function (array $data, Meeting $record) use ($approvals, $minutes, $refresh): void {
                    $approvals->isWaitingOn($record, auth()->user())
                        ? $approvals->reject($record, $data['comments'])
                        : $minutes->returnMinutes($record, $data['comments']);

                    event(new MinutesReturned($record->fresh(), $data['comments']));
                    $refresh();
                }),

            Action::make('minutes_publish')
                ->label('Publish minutes')
                ->color('success')
                ->requiresConfirmation()
                ->authorize('publishMinutes')
                ->visible(fn (Meeting $record): bool => $record->minutes_status === 'approved')
                ->action(function (Meeting $record) use ($minutes, $refresh): void {
                    $minutes->publish($record);

                    event(new MinutesPublished($record->fresh()));
                    $refresh();
                }),
        ];
    }

    /**
     * Who may decide on submitted minutes. When approvers were named, only they (or
     * a super-admin) may; otherwise anyone with approval rights.
     */
    protected function mayDecide(Meeting $meeting): bool
    {
        $approvals = app(MeetingApprovalService::class);

        if (! $approvals->hasSteps($meeting)) {
            return true;
        }

        return $approvals->isWaitingOn($meeting, auth()->user()) || auth()->user()->hasRole('super-admin');
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
