<?php

namespace App\Providers;

use App\Events\ActionItemCompleted;
use App\Events\ActionItemCreated;
use App\Events\MeetingCancelled;
use App\Events\MeetingCompleted;
use App\Events\MeetingCreated;
use App\Events\MeetingPostponed;
use App\Events\MeetingStarted;
use App\Events\MinutesApproved;
use App\Events\MinutesPublished;
use App\Events\MinutesReturned;
use App\Events\MinutesSubmitted;
use App\Events\TaskAssigned;
use App\Events\TaskCompleted;
use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use App\Listeners\SendActionAssignmentNotification;
use App\Listeners\SendActionCompletedNotification;
use App\Listeners\SendMeetingCancellationNotifications;
use App\Listeners\SendMeetingInvitations;
use App\Listeners\SendMeetingPostponedNotifications;
use App\Listeners\SendMeetingUpdateNotifications;
use App\Listeners\SendMinutesApprovedNotification;
use App\Listeners\SendMinutesPublishedNotification;
use App\Listeners\SendMinutesReturnedNotification;
use App\Listeners\SendMinutesSubmittedNotification;
use App\Listeners\SendTaskAssignedNotification;
use App\Listeners\SendTaskCompletedNotification;
use App\Listeners\SendTaskUpdateNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        MeetingCreated::class => [
            SendMeetingInvitations::class,
        ],
        MeetingUpdated::class => [
            SendMeetingUpdateNotifications::class,
        ],
        MeetingCancelled::class => [
            SendMeetingCancellationNotifications::class,
        ],
        MeetingPostponed::class => [
            SendMeetingPostponedNotifications::class,
        ],
        MeetingStarted::class => [],
        MeetingCompleted::class => [],
        ActionItemCreated::class => [
            SendActionAssignmentNotification::class,
        ],
        ActionItemCompleted::class => [
            SendActionCompletedNotification::class,
        ],
        MinutesSubmitted::class => [
            SendMinutesSubmittedNotification::class,
        ],
        MinutesApproved::class => [
            SendMinutesApprovedNotification::class,
        ],
        MinutesReturned::class => [
            SendMinutesReturnedNotification::class,
        ],
        MinutesPublished::class => [
            SendMinutesPublishedNotification::class,
        ],
        TaskCreated::class => [
            SendTaskAssignedNotification::class,
        ],
        TaskUpdated::class => [
            SendTaskUpdateNotification::class,
        ],
        TaskCompleted::class => [
            SendTaskCompletedNotification::class,
        ],
        TaskAssigned::class => [
            SendTaskAssignedNotification::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
