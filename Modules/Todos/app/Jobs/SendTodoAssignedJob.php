<?php

namespace Modules\Todos\Jobs;

use App\Enums\NotificationType;

/**
 * A To-Do was assigned.
 */
class SendTodoAssignedJob extends SendTodoNotificationJob
{
    public function type(): NotificationType
    {
        return NotificationType::TodoAssigned;
    }
}
