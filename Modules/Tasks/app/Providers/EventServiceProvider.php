<?php

namespace Modules\Tasks\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Tasks\Events\TaskAssigned;
use Modules\Tasks\Events\TaskCompleted;
use Modules\Tasks\Events\TaskCreated;
use Modules\Tasks\Events\TaskUpdated;
use Modules\Tasks\Listeners\SendTaskAssignedNotification;
use Modules\Tasks\Listeners\SendTaskCompletedNotification;
use Modules\Tasks\Listeners\SendTaskUpdateNotification;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event handler mappings for the Tasks module.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
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
