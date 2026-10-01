<?php

namespace Modules\Todos\Jobs;

use App\Enums\NotificationType;

/**
 * The next occurrence of a series was materialised.
 */
class SendTodoRecurringJob extends SendTodoNotificationJob
{
    public function type(): NotificationType
    {
        return NotificationType::TodoRecurringGenerated;
    }
}
