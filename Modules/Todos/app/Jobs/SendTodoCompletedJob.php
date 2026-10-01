<?php

namespace Modules\Todos\Jobs;

use App\Enums\NotificationType;

/**
 * A To-Do was completed; the creator and watchers are told.
 */
class SendTodoCompletedJob extends SendTodoNotificationJob
{
    public function type(): NotificationType
    {
        return NotificationType::TodoCompleted;
    }
}
