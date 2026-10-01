<?php

namespace Modules\Todos\Console\Commands;

use App\Enums\NotificationType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Todos\Jobs\SendTodoNotificationJob;
use Throwable;

/**
 * Shared machinery for the To-Do notification commands.
 *
 * The three commands differ only in which rows they select and which notification
 * type they send. Chunking, idempotency, error isolation and output discipline
 * are identical, so they live here once — a per-command copy is how one of them
 * eventually stops being safe to re-run.
 */
abstract class SendsTodoNotifications extends Command
{
    /**
     * Rows per pass. Bounds memory and keeps one bad row from stalling the rest.
     */
    protected int $chunkSize = 200;

    /**
     * The notification type this command sends.
     */
    abstract protected function notificationType(): NotificationType;

    /**
     * The concrete job this command dispatches.
     *
     * A specific class per command rather than the shared abstract base: the
     * base cannot be instantiated, and a named job lets a queue worker retry or
     * throttle overdue warnings independently of due-soon ones.
     *
     * @return class-string<SendTodoNotificationJob>
     */
    abstract protected function jobClass(): string;

    /**
     * The discriminator that makes the dedupe key distinct per subject. Overdue
     * is per-day, so a To-Do that slips past three days warns three times.
     */
    abstract protected function discriminator(): string;

    /**
     * Which To-Dos this command applies to.
     */
    abstract protected function query();

    /**
     * Extra text for the confirmation line, e.g. the window being scanned.
     */
    protected function describeWindow(): string
    {
        return 'now';
    }

    public function handle(): int
    {
        $dispatched = 0;
        $skipped = 0;
        $failed = 0;

        $type = $this->notificationType();

        $this->query()
            ->with('assignee:id')
            ->orderBy('id')
            ->chunkById($this->chunkSize, function ($todos) use ($type, &$dispatched, &$skipped, &$failed): void {
                foreach ($todos as $todo) {
                    try {
                        // No assignee, no one to notify. Skipped rather than
                        // failed: it is a state, not a fault.
                        if ($todo->assignee_id === null) {
                            $skipped++;

                            continue;
                        }

                        ($this->jobClass())::dispatch(
                            $todo,
                            null,
                            $this->discriminator(),
                        );

                        $dispatched++;
                    } catch (Throwable $e) {
                        $failed++;

                        // Subject id and exception class only. A reminder or
                        // notification log must never carry an address, a name or
                        // a message body.
                        Log::error('To-Do notification dispatch failed', [
                            'todo_id' => $todo->getKey(),
                            'type' => $type->value,
                            'error' => $e::class,
                        ]);
                    }
                }
            });

        $this->info(sprintf(
            '%s: %d queued, %d skipped, %d failed (window: %s).',
            $type->label(),
            $dispatched,
            $skipped,
            $failed,
            $this->describeWindow(),
        ));

        // Advisory by design: a partial pass is not a scheduler failure, and a
        // non-zero exit would page an operator for one poison row.
        return self::SUCCESS;
    }
}
