<?php

namespace Modules\Todos\Jobs;

use App\Enums\NotificationType;

/**
 * A To-Do is due shortly.
 */
class SendTodoDueSoonJob extends SendTodoNotificationJob
{
    public function type(): NotificationType
    {
        return NotificationType::TodoDueSoon;
    }
}
