<?php

namespace Modules\Todos\Jobs;

use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoNotificationService;

/**
 * Base for every To-Do notification job.
 *
 * One class per notification *type* rather than one per event, because the
 * delivery logic is identical and differs only in the type. Splitting it per
 * event would give nine near-identical files and one place — this one — to get
 * right.
 *
 * ShouldQueue, and always dispatched rather than run inline: no mail may be sent
 * inside a request or a scheduler tick (GAP-022).
 *
 * Idempotent by construction. The dedupe key is
 * `{type}:{todo_id}[:{discriminator}]` behind a unique index, so a retried job or
 * a re-fired cron finds the key taken and sends nothing.
 */
abstract class SendTodoNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Todo $todo,
        public ?int $actorId = null,
        public string $discriminator = '',
    ) {}

    abstract public function type(): NotificationType;

    public function handle(TodoNotificationService $notifications): void
    {
        $type = $this->type();

        foreach ($notifications->recipientsFor($this->todo, $type, $this->actorId) as $recipient) {
            $notifications->deliver(
                $recipient,
                $this->todo,
                $type,
                $this->discriminator,
                $this->actorName($notifications),
            );
        }
    }

    /**
     * The actor's display name, for the "From: …" line. Resolved through the
     * notification service so the job does not need its own user lookup.
     */
    protected function actorName(TodoNotificationService $notifications): string
    {
        if ($this->actorId === null) {
            return '';
        }

        return User::query()->whereKey($this->actorId)->value('name') ?? '';
    }
}
