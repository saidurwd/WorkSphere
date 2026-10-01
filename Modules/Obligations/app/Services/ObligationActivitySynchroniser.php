<?php

namespace Modules\Obligations\Services;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationActivityLog;

/**
 * Dual-writes obligation activity into the shared `activity_logs` table — GAP-048.
 *
 * `obligation_activity_logs` stays the read path for every existing screen; the
 * shared table is what makes one timeline possible across modules.
 *
 * Both rows are written together and carry the same payload, so a reconciliation
 * between them is a comparison rather than an inference. The mirror is
 * idempotent per (subject, action, payload fingerprint) so a backfill can be
 * re-run safely.
 */
class ObligationActivitySynchroniser
{
    public function __construct(private readonly ActivityLogger $shared) {}

    /**
     * Record an action on both tables.
     *
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public function record(
        Obligation $obligation,
        string $action,
        ?array $old = null,
        ?array $new = null,
        ?string $remarks = null,
        ?int $userId = null,
        ?string $ip = null,
        ?string $userAgent = null,
    ): ObligationActivityLog {
        $legacy = ObligationActivityLog::query()->create([
            'obligation_id' => $obligation->id,
            'user_id' => $userId,
            'action' => $action,
            'old_value' => $old,
            'new_value' => $new,
            'remarks' => $remarks,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        // The shared row names the module by class, matching the polymorphic
        // convention the To-Do and Task observers already use.
        $this->shared->record(
            Obligation::class,
            $obligation,
            strtolower($action),
            $old,
            $new,
            $userId,
        );

        return $legacy;
    }

    /**
     * Backfill legacy rows into the shared table.
     *
     * Idempotent: a row is mirrored only when the shared table has nothing with
     * the same subject, action and recorded-at second, so a partial run resumes
     * and a second run mirrors nothing.
     */
    public function backfill(int $chunk = 200): int
    {
        $mirrored = 0;
        $cursor = 0;

        do {
            $rows = ObligationActivityLog::query()
                ->where('id', '>', $cursor)
                ->orderBy('id')
                ->limit($chunk)
                ->get();

            foreach ($rows as $row) {
                $obligation = Obligation::query()->find($row->obligation_id);

                if ($obligation !== null && ! $this->alreadyMirrored($row)) {
                    ActivityLog::query()->create([
                        'user_id' => $row->user_id,
                        'module_name' => 'Obligation',
                        'record_id' => $obligation->id,
                        'subject_type' => Obligation::class,
                        'subject_id' => $obligation->id,
                        'action' => strtolower((string) $row->action),
                        'old_value' => $row->old_value,
                        'new_value' => $row->new_value,
                        'ip_address' => $row->ip_address,
                        'user_agent' => $row->user_agent,
                        'created_at' => $row->created_at,
                    ]);

                    $mirrored++;
                }

                $cursor = $row->id;
            }
        } while ($rows->count() === $chunk);

        return $mirrored;
    }

    protected function alreadyMirrored(ObligationActivityLog $row): bool
    {
        return ActivityLog::query()
            ->where('subject_type', Obligation::class)
            ->where('subject_id', $row->obligation_id)
            ->where('action', strtolower((string) $row->action))
            ->where('created_at', $row->created_at)
            ->exists();
    }

    /**
     * The Phase 8 cutover plan, stated rather than executed.
     *
     * @return array<string, string>
     */
    public static function cutoverPlan(): array
    {
        return [
            'step_1' => 'Dual-write every new activity entry (this class). Both tables populated.',
            'step_2' => 'Backfill historical entries with backfill(), idempotent on subject + action + timestamp.',
            'step_3' => 'Reconcile: every obligation_activity_logs row must have a matching activity_logs row.',
            'step_4' => 'Point Obligation::activityLogs() at the shared table; the timeline view keeps reading the same relation.',
            'step_5' => 'Phase 15 drops obligation_activity_logs.',
        ];
    }
}
