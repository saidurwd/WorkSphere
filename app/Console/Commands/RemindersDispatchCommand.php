<?php

namespace App\Console\Commands;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Todos\Events\TodoReminderFired;
use Throwable;

/**
 * Fires every due reminder — TODO-MODULE-SPECIFICATION.md §6.1.
 *
 * Replaces the five module-specific daily commands (GAP-046). Runs every minute
 * and selects on `reminder_dispatch_idx`, the one index built for it.
 *
 * Re-runnability is a hard requirement, not a nicety: cron fires this on every
 * scheduler tick and a deployment will inevitably run it twice. Two independent
 * guards cover that —
 *
 * 1. `claim()` flips the row to `sent` inside a conditional UPDATE. Two
 *    schedulers racing on the same row means exactly one UPDATE affects a row;
 *    the loser dispatches nothing.
 * 2. The notification's own dedupe key (`notification_logs.dedupe_key`, UNIQUE)
 *    makes the downstream send a no-op even if a job is somehow queued twice.
 *
 * Chunked because `limit 200` on an unbounded scan would hold one long
 * transaction over an ever-growing table.
 */
class RemindersDispatchCommand extends Command
{
    /**
     * How many reminders are claimed per pass. Bounds the transaction and the
     * memory held while dispatching.
     */
    private const CHUNK = 200;

    protected $signature = 'reminders:dispatch
                            {--limit= : Maximum reminders to fire in one pass (default 200)}';

    protected $description = 'Fire every reminder that has come due. Safe to run repeatedly.';

    public function handle(): int
    {
        $limit = min(10_000, max(1, (int) $this->option('limit') ?: self::CHUNK));

        $claimed = 0;
        $skipped = 0;
        $failed = 0;

        // pluck() rather than get(): the claim only needs the id, and these
        // rows can number in the millions.
        Reminder::query()
            ->due()
            ->orderBy('remind_at')
            ->limit($limit)
            ->pluck('id')
            ->each(function (int $id) use (&$claimed, &$skipped, &$failed): void {
                try {
                    $reminder = $this->claim($id);

                    if ($reminder === null) {
                        $skipped++;

                        return;
                    }

                    TodoReminderFired::dispatch($reminder, (int) $reminder->subject_id);

                    $claimed++;
                } catch (Throwable $e) {
                    // One bad reminder must not abandon the rest of the pass: a
                    // single malformed row would otherwise block every reminder
                    // behind it until someone noticed.
                    $failed++;

                    // The id and the exception class only. A reminder's payload
                    // and the recipient's identity are PII and must not reach a
                    // log line (see the security requirements in the prompt).
                    Log::error('Reminder dispatch failed', [
                        'reminder_id' => $id,
                        'error' => $e::class,
                    ]);

                    $this->warn("Reminder #{$id} could not be dispatched.");
                }
            });

        if ($claimed > 0 || $skipped > 0 || $failed > 0) {
            // Counts only. No recipient addresses, no subjects — this is stdout,
            // which is captured by process managers and shipped to log storage.
            $this->info(sprintf(
                'Fired %d reminder(s); %d already claimed; %d failed.',
                $claimed,
                $skipped,
                $failed,
            ));
        }

        // Advisory, per the prompt: a partial success is still a success for the
        // scheduler's purposes, and a non-zero exit would page an operator for a
        // single poison row.
        return self::SUCCESS;
    }

    /**
     * Atomically move a reminder out of the pending state.
     *
     * The `where('status', pending)` in the UPDATE is the whole mechanism: two
     * schedulers reading the same due row will both attempt the claim, and only
     * one can match the predicate.
     */
    protected function claim(int $id): ?Reminder
    {
        return DB::transaction(function () use ($id): ?Reminder {
            $updated = Reminder::query()
                ->whereKey($id)
                ->where('status', ReminderStatus::Pending->value)
                ->update([
                    'status' => ReminderStatus::Sent->value,
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                return null;
            }

            return Reminder::query()->find($id);
        });
    }
}
