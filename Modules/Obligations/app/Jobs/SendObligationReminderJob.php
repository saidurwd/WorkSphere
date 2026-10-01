<?php

namespace Modules\Obligations\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Obligations\Mail\ObligationReminder;
use Modules\Obligations\Models\NotificationLog;
use Modules\Obligations\Models\NotificationRule;
use Modules\Obligations\Models\Obligation;
use Throwable;

/**
 * Delivers one obligation reminder — GAP-022.
 *
 * The last inline send in this module. It previously ran inside
 * `obligations:process`, so a slow SMTP handshake stalled the scheduler and one
 * unreachable mailbox swallowed the rest of the run. It also marked the log SENT
 * optimistically, which meant a failed send was recorded as delivered.
 *
 * The job owns the send and the status update, so the log records what happened.
 * A retry updates the same row rather than duplicating it.
 */
class SendObligationReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Obligation $obligation,
        public NotificationRule $rule,
        public ?User $recipient,
        public int $logId,
    ) {}

    public function handle(): void
    {
        $log = NotificationLog::query()->find($this->logId);

        if ($log === null || $log->status === 'SENT') {
            return;
        }

        try {
            if (Str::upper((string) $this->rule->channel) === 'EMAIL' && $this->recipient?->email) {
                Mail::to($this->recipient->email)->send(
                    new ObligationReminder($this->obligation, $this->rule, $this->recipient),
                );
            }

            $log->update(['status' => 'SENT', 'sent_at' => now()]);
        } catch (Throwable $e) {
            $log->update([
                'status' => 'FAILED',
                'error_message' => Str::limit($e->getMessage(), 500),
                'retry_count' => $log->retry_count + 1,
            ]);

            // Obligation id and exception class only — never the message body.
            Log::warning('Obligation reminder failed', [
                'obligation_id' => $this->obligation->id,
                'log_id' => $this->logId,
                'error' => $e::class,
            ]);

            throw $e;
        }
    }
}
