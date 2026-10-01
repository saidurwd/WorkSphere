<?php

namespace Modules\Todos\Jobs;

use App\Enums\NotificationType;

/**
 * A scheduled reminder came due.
 *
 * The discriminator defaults to the fire date rather than being empty: a reminder
 * for the same To-Do on two different days is two notifications, and an empty
 * discriminator would collapse them into one via the dedupe key.
 */
class SendTodoReminderJob extends SendTodoNotificationJob
{
    public function type(): NotificationType
    {
        return NotificationType::TodoReminder;
    }
}
