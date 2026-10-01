<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\ReminderStatus;
use App\Models\Reminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Modules\Todos\Models\Todo;

/**
 * Creates and retires reminders on the shared `reminders` table.
 *
 * Deliberately idempotent. UNIQUE(subject_type, subject_id, remind_at) makes a
 * duplicate impossible at the database level, so this is `firstOrCreate` by
 * construction rather than a check-then-insert that races — two scheduler ticks
 * racing here would otherwise both decide the reminder does not exist and both
 * try to create it.
 *
 * A reminder's `remind_at` is stored in UTC (a `timestamp` column) computed from
 * a *local* business instant. That conversion is the whole reason this class
 * exists: §5.3 requires reminders to be computed in the app timezone, and getting
 * it wrong fires a 09:00 reminder at 03:00.
 */
class ReminderScheduler
{
    /**
     * Default lead time before a due date.
     */
    public const DEFAULT_LEAD_MINUTES = 60;

    /**
     * Schedule a reminder for a To-Do, replacing any earlier pending one.
     *
     * A reminder already sent is left alone: re-arming it would produce a second
     * notification for a single due date, because the dedupe key is keyed on the
     * fire date and the row would now carry a new one.
     */
    public function scheduleForTodo(
        Todo $todo,
        ?CarbonImmutable $remindAt = null,
        NotificationChannel|string|null $channel = null,
        ?User $createdBy = null,
    ): ?Reminder {
        $remindAt ??= $this->defaultMomentFor($todo);

        if ($remindAt === null) {
            return null;
        }

        $channelValue = $channel instanceof NotificationChannel ? $channel->value : ($channel ?? NotificationChannel::InApp->value);

        // One pending reminder per subject: a new due date replaces the old plan
        // rather than stacking a second notification on top of it.
        Reminder::query()
            ->where('subject_type', Todo::class)
            ->where('subject_id', $todo->getKey())
            ->where('status', ReminderStatus::Pending->value)
            ->delete();

        return Reminder::query()->firstOrCreate(
            [
                'subject_type' => Todo::class,
                'subject_id' => $todo->getKey(),
                'remind_at' => $remindAt->utc(),
            ],
            [
                'channel' => $channelValue,
                'status' => ReminderStatus::Pending->value,
                'created_by' => $createdBy?->id,
            ],
        );
    }

    /**
     * Cancel every pending reminder for a subject — used when a To-Do is
     * completed, archived or deleted, so nothing fires about work that is done.
     */
    public function cancelForTodo(Todo $todo): int
    {
        return Reminder::query()
            ->where('subject_type', Todo::class)
            ->where('subject_id', $todo->getKey())
            ->where('status', ReminderStatus::Pending->value)
            ->update([
                'status' => ReminderStatus::Cancelled->value,
                'cancelled_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * The moment a To-Do should be reminded, as a local instant.
     *
     * Returns null when there is nothing to remind about: no due date, or a due
     * date already in the past. A reminder for a moment that has passed would
     * fire on the next scheduler tick, which is not "reminding", it is nagging
     * about something already due.
     */
    protected function defaultMomentFor(Todo $todo): ?CarbonImmutable
    {
        if ($todo->due_date === null) {
            return null;
        }

        $dueAt = CarbonImmutable::parse(
            $todo->due_date->format('Y-m-d').' '.($todo->due_time ?: '09:00'),
            config('app.timezone'),
        );

        $remindAt = $dueAt->subMinutes(self::DEFAULT_LEAD_MINUTES);

        return $remindAt->greaterThan(CarbonImmutable::now(config('app.timezone'))) ? $remindAt : null;
    }
}
