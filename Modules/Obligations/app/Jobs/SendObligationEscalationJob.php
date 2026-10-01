<?php

namespace Modules\Obligations\Jobs;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Obligations\Mail\ObligationEscalation;
use Modules\Obligations\Models\Obligation;
use Throwable;

/**
 * Delivers one obligation escalation.
 *
 * GAP-022: this module sent every escalation inline. An escalation fires from the
 * `obligations:process` scheduler tick, so a slow SMTP handshake stalled the
 * scheduler, and one unreachable mailbox aborted the remaining escalations for
 * that run.
 *
 * The job owns the send *and* the log update, so the log reflects what actually
 * happened rather than what the scheduler hoped would happen. Idempotent on the
 * log row's id: a retried job updates the same row instead of duplicating it.
 */
class SendObligationEscalationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(
        public Obligation $obligation,
        public User $recipient,
        public string $channel,
        public string $escalationLevel,
        public int $logId,
    ) {}

    public function handle(): void
    {
        $log = NotificationLog::query()->find($this->logId);

        if ($log === null || $log->status === 'SENT') {
            return;
        }

        try {
            if (Str::upper($this->channel) === 'EMAIL') {
                Mail::to($this->recipient->email)->send(new ObligationEscalation($this->obligation, $this->recipient));
            }

            $log->update(['status' => 'SENT', 'sent_at' => now()]);
        } catch (Throwable $e) {
            $log->update([
                'status' => 'FAILED',
                'error_message' => Str::limit($e->getMessage(), 500),
                'retry_count' => $log->retry_count + 1,
            ]);

            // A failed delivery must not abort the run: the remaining recipients
            // still need their escalation.
            Log::warning('Obligation escalation failed', [
                'obligation_id' => $this->obligation->id,
                'log_id' => $this->logId,
                'error' => $e::class,
            ]);

            throw $e;
        }
    }
}
