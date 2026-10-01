<?php

namespace Modules\Tasks\Services;

use App\Models\Comment;
use App\Services\ActivityLogger;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskRemark;

/**
 * Dual-writes task remarks into the shared `comments` table — GAP-048.
 *
 * `task_remarks` stays the read path for every existing screen, mail and job;
 * `comments` is the platform table that lets one comment stream span modules. The
 * backfill and the cutover are Phase 8's later steps; the drop is Phase 15.
 *
 * Every method returns the shared comment so a caller can link the two without
 * a second lookup — the remark and its mirror always share an id.
 */
class TaskRemarkSynchroniser
{
    public function __construct(private readonly ActivityLogger $activity) {}

    /**
     * Mirror a newly created remark.
     */
    public function mirror(Task $task, TaskRemark $remark): Comment
    {
        // Timestamps are not mass-assignable on Comment (Eloquent owns them), so
        // the remark's original time is applied afterwards rather than passed in.
        // Preserving it matters during a backfill: a comment dated 2019 must not
        // appear at the top of a timeline as though it were written today.
        $comment = $task->sharedComments()->create([
            'user_id' => $remark->user_id,
            'parent_id' => null,
            'body' => $remark->remark,
        ]);

        if ($remark->created_at !== null) {
            $comment->forceFill([
                'created_at' => $remark->created_at,
                'updated_at' => $remark->updated_at ?? $remark->created_at,
            ])->saveQuietly();
        }

        $this->activity->record(
            Task::class,
            $task,
            'remark_added',
            null,
            ['remark_id' => $remark->id, 'comment_id' => $comment->id],
            $remark->user_id,
        );

        return $comment;
    }

    /**
     * Backfill remarks that predate this dual-write.
     *
     * Idempotent by content: a remark is only mirrored when the task has no
     * shared comment with that body and author, so re-running is safe and a
     * remark is never duplicated.
     *
     * @return int The number of remarks mirrored.
     */
    public function backfill(int $chunk = 200): int
    {
        $mirrored = 0;
        $cursor = 0;

        // Paginate by id rather than offset: the loop writes to `comments`, not to
        // the table being scanned, but an id cursor is stable regardless.
        do {
            $remarks = TaskRemark::query()
                ->with('task')
                ->where('id', '>', $cursor)
                ->orderBy('id')
                ->limit($chunk)
                ->get();

            foreach ($remarks as $remark) {
                $task = $remark->task;

                if ($task === null) {
                    $cursor = $remark->id;

                    continue;
                }

                $alreadyMirrored = $task->sharedComments()
                    ->where('user_id', $remark->user_id)
                    ->where('body', $remark->remark)
                    ->exists();

                if (! $alreadyMirrored) {
                    $this->mirror($task, $remark);
                    $mirrored++;
                }

                $cursor = $remark->id;
            }
        } while ($remarks->count() === $chunk);

        return $mirrored;
    }

    /**
     * The Phase 8 read-cutover plan, stated rather than executed.
     *
     * @return array<string, string>
     */
    public static function cutoverPlan(): array
    {
        return [
            'step_1' => 'Dual-write every new remark (this class). Both tables populated.',
            'step_2' => 'Backfill historical remarks with backfill(), which is idempotent by content.',
            'step_3' => 'Reconcile: every task_remarks row must have a matching comments row.',
            'step_4' => 'Point Task::remarks() at the shared table and leave the mail templates reading the same relation.',
            'step_5' => 'Phase 15 drops task_remarks.',
        ];
    }
}
