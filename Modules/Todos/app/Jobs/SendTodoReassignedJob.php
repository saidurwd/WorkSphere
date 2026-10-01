<?php

namespace Modules\Todos\Jobs;

use App\Enums\NotificationType;

/**
 * A To-Do changed hands; the new holder is told.
 */
class SendTodoReassignedJob extends SendTodoNotificationJob
{
    public function type(): NotificationType
    {
        return NotificationType::TodoReassigned;
    }
}
