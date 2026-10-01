<?php

namespace Modules\Todos\Jobs;

use App\Enums\NotificationType;

/**
 * A completed To-Do was reopened.
 */
class SendTodoReopenedJob extends SendTodoNotificationJob
{
    public function type(): NotificationType
    {
        return NotificationType::TodoReopened;
    }
}
