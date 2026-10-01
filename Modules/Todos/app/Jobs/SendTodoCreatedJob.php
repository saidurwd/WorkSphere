<?php

namespace Modules\Todos\Jobs;

use App\Enums\NotificationType;

/**
 * A To-Do was created for the assignee.
 */
class SendTodoCreatedJob extends SendTodoNotificationJob
{
    public function type(): NotificationType
    {
        return NotificationType::TodoCreated;
    }
}
