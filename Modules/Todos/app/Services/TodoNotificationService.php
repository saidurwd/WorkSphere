<?php

namespace Modules\Todos\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Todos\Models\Todo;
use Modules\Todos\Notifications\TodoNotification;

/**
 * Notification delivery for the To-Do module — TODO-MODULE-SPECIFICATION.md §6.
 *
 * Two jobs live here and nowhere else:
 *
 * 1. `shouldNotify()` consults `notification_preferences` instead of returning
 *    `true` (GAP-024). A user with no row for a pair is opted IN, so the table
 *    stays small and only opt-outs and channel-specific choices need a row.
 * 2. Every attempt writes a `notification_logs` row with a `dedupe_key`. The
 *    unique index on that column is what makes a re-fired cron safe (GAP-023):
 *    a duplicate insert is swallowed rather than raising, so a second run simply
 *    does not send — which is the entire point.
 *
 * `notification_logs.status` is uppercase ('PENDING'/'SENT'/'FAILED'); that is the
 * pre-existing column default in the table, not a choice made here.
 */
class TodoNotificationService
{
    public function __construct(private readonly RecipientResolver $recipients) {}

    /**
     * Whether this user wants this notification type on this channel.
     *
     * An explicit `enabled = false` row opts out. Absence of a row means opt-in:
     * a new user should receive work notifications until they say otherwise.
     */
    public function shouldNotify(User $user, NotificationType $type, NotificationChannel $channel): bool
    {
        $preference = NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('notification_type', $type->value)
            ->where('channel', $channel->value)
            ->first();

        return $preference === null || $preference->enabled;
    }

    /**
     * The channels a user actually receives for this type. A user may opt out of
     * email while keeping in-app, so this cannot be a constant.
     *
     * Only *deliverable* channels are considered. NotificationChannel also
     * declares Sms and InApp as reserved-but-unwired cases; including them here
     * would report a user as still having a channel they had not opted out of
     * when no notification is actually sent down it, which makes the preference
     * check meaningless.
     *
     * @return list<NotificationChannel>
     */
    public function channelsFor(User $user, NotificationType $type): array
    {
        return array_values(array_filter(
            NotificationChannel::deliverableCases(),
            fn (NotificationChannel $channel): bool => $this->shouldNotify($user, $type, $channel),
        ));
    }

    /**
     * Deliver to one recipient and record the attempt.
     *
     * Returns true when the recipient was actually notified. A duplicate dedupe
     * key returns false without sending, which is how re-running a scheduler
     * stays safe.
     */
    public function deliver(
        User $recipient,
        Todo $todo,
        NotificationType $type,
        string $discriminator = '',
        string $actorName = '',
    ): bool {
        $channels = $this->channelsFor($recipient, $type);

        if ($channels === []) {
            // Deliberately no log row. Nothing was attempted, and writing one
            // would consume the dedupe key — so a user who opts back in later
            // would find their first real notification already "claimed" and
            // silently never delivered.
            return false;
        }

        $dedupeKey = $type->dedupeKey($todo->getKey(), $discriminator);

        if (! $this->claim($todo, $recipient, $type, $channels, $dedupeKey)) {
            return false;
        }

        $notification = new TodoNotification(
            $todo->getKey(),
            $todo->title,
            $type,
            $actorName,
        );

        try {
            $recipient->notify($notification);
        } catch (\Throwable $e) {
            // Log the To-Do id and the exception class only. Never the recipient's
            // address or the notification body.
            Log::warning('To-Do notification failed', [
                'todo_id' => $todo->getKey(),
                'type' => $type->value,
                'error' => $e::class,
            ]);

            $this->markFailed($dedupeKey, $e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Reserve the dedupe key by writing the log row.
     *
     * Returns false when the key is already taken, which means a previous run
     * already delivered this notification and nothing should be sent now.
     *
     * @param  list<NotificationChannel>  $channels
     */
    protected function claim(
        Todo $todo,
        User $recipient,
        NotificationType $type,
        array $channels,
        string $dedupeKey,
    ): bool {
        try {
            return $this->log($todo, $recipient, $type, $channels, 'PENDING', $dedupeKey) !== null;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    /**
     * Update the claimed row rather than inserting a second one. The dedupe key
     * is UNIQUE, so a follow-up insert would raise and mask the failure it was
     * meant to record.
     */
    protected function markFailed(string $dedupeKey, string $error): void
    {
        DB::table('notification_logs')
            ->where('dedupe_key', $dedupeKey)
            ->update([
                'status' => 'FAILED',
                'error_message' => Str::limit($error, 500),
                'updated_at' => now(),
            ]);
    }

    /**
     * @param  list<NotificationChannel>  $channels
     */
    protected function log(
        Todo $todo,
        User $recipient,
        NotificationType $type,
        array $channels,
        string $status,
        string $dedupeKey,
        ?string $error = null,
    ): bool {
        return DB::table('notification_logs')->insert([
            'subject_type' => Todo::class,
            'subject_id' => $todo->getKey(),
            'user_id' => $recipient->id,
            'channel' => implode(',', array_map(
                fn (NotificationChannel $channel): string => $channel->value,
                $channels,
            )),
            'notification_type' => $type->value,
            'scheduled_at' => now(),
            'sent_at' => $status === 'SENT' ? now() : null,
            'status' => $status,
            // Subject and message are intentionally not copied from the
            // notification body: this table is read by admin screens, and the
            // body may carry text the reader is not cleared to see.
            'dedupe_key' => $dedupeKey,
            'retry_count' => 0,
            'error_message' => $error === null ? null : Str::limit($error, 500),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * The recipients for an event, minus the actor. Used by every listener so
     * the rule for "who hears about this" lives in one place.
     *
     * @return list<User>
     */
    public function recipientsFor(Todo $todo, NotificationType $type, ?int $actorId = null): array
    {
        $recipients = $this->recipients->for($todo, $type, $actorId);

        return array_values(array_filter(
            $recipients,
            fn (User $user): bool => $this->channelsFor($user, $type) !== [],
        ));
    }
}
