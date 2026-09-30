<?php

namespace Modules\Meetings\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Meetings\Events\ActionItemCompleted;
use Modules\Meetings\Events\ActionItemCreated;
use Modules\Meetings\Events\MeetingCancelled;
use Modules\Meetings\Events\MeetingCompleted;
use Modules\Meetings\Events\MeetingCreated;
use Modules\Meetings\Events\MeetingPostponed;
use Modules\Meetings\Events\MeetingStarted;
use Modules\Meetings\Events\MeetingUpdated;
use Modules\Meetings\Events\MinutesApproved;
use Modules\Meetings\Events\MinutesPublished;
use Modules\Meetings\Events\MinutesReturned;
use Modules\Meetings\Events\MinutesSubmitted;
use Modules\Meetings\Listeners\SendActionAssignmentNotification;
use Modules\Meetings\Listeners\SendActionCompletedNotification;
use Modules\Meetings\Listeners\SendMeetingCancellationNotifications;
use Modules\Meetings\Listeners\SendMeetingInvitations;
use Modules\Meetings\Listeners\SendMeetingPostponedNotifications;
use Modules\Meetings\Listeners\SendMeetingUpdateNotifications;
use Modules\Meetings\Listeners\SendMinutesApprovedNotification;
use Modules\Meetings\Listeners\SendMinutesPublishedNotification;
use Modules\Meetings\Listeners\SendMinutesReturnedNotification;
use Modules\Meetings\Listeners\SendMinutesSubmittedNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the Meetings module.
     *
     * @var array<class-string, array<int, class-string>>
     */
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
    ];

    /**
     * Mappings are explicit above, so auto-discovery stays off.
     *
     * @var bool
     */
    protected static $shouldDiscoverEvents = false;

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    public function configureEmailVerification(): void {}
}
