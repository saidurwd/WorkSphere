<?php

namespace Modules\Todos\Jobs;

use App\Enums\NotificationType;

/**
 * A To-Do passed its due date.
 */
class SendTodoOverdueJob extends SendTodoNotificationJob
{
    public function type(): NotificationType
    {
        return NotificationType::TodoOverdue;
    }
}
