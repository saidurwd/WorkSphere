<?php

namespace Modules\Meetings\Services;

use App\Models\Attachment;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingAttachment;

/**
 * Dual-writes meeting attachments into the shared `attachments` table — GAP-048.
 *
 * `meeting_attachments` stays the read path; `attachments` is the platform table
 * that lets one upload query span modules. The row records the disk the file was
 * **actually** written to, so a reader is never told a private path for a file
 * living on the public disk.
 *
 * No file is copied: two rows point at one object, so a migration or cleanup
 * cannot leave two divergent copies.
 */
class MeetingAttachmentSynchroniser
{
    /**
     * Mirror a newly stored attachment.
     */
    public function mirror(Meeting $meeting, MeetingAttachment $attachment, string $disk): ?Attachment
    {
        $shared = Attachment::query()->updateOrCreate(
            [
                'attachable_type' => Meeting::class,
                'attachable_id' => $meeting->id,
                'path' => $attachment->file_path,
            ],
            [
                'disk' => $disk,
                'original_name' => $attachment->file_name,
                'mime_type' => $attachment->file_type,
                'size' => $attachment->file_size,
                'uploaded_by' => $attachment->uploaded_by,
            ],
        );

        return $shared->refresh();
    }

    /**
     * Backfill attachments created before this dual-write.
     *
     * Idempotent by path: `updateOrCreate` on (subject, path) means a re-run
     * updates rather than duplicating.
     *
     * @return int The number of attachments mirrored.
     */
    public function backfill(int $chunk = 200): int
    {
        $mirrored = 0;
        $cursor = 0;

        do {
            $attachments = MeetingAttachment::query()
                ->where('id', '>', $cursor)
                ->whereNotNull('meeting_id')
                ->orderBy('id')
                ->limit($chunk)
                ->get();

            foreach ($attachments as $attachment) {
                $meeting = Meeting::query()->find($attachment->meeting_id);

                if ($meeting !== null) {
                    // Historic uploads went to the public disk; the shared row must
                    // say so rather than claim the private default.
                    //
                    // `wasRecentlyCreated` is what makes the count meaningful: a
                    // second run updates the existing rows and reports zero, which is
                    // how an operator can tell a complete backfill from a partial one.
                    $shared = $this->mirror($meeting, $attachment, 'public');

                    if ($shared !== null && $shared->wasRecentlyCreated) {
                        $mirrored++;
                    }
                }

                $cursor = $attachment->id;
            }
        } while ($attachments->count() === $chunk);

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
            'step_1' => 'Dual-write every new upload (this class). Both tables populated.',
            'step_2' => 'Backfill historical attachments with backfill(), idempotent by path.',
            'step_3' => 'Point Meeting::attachments() at the shared table.',
            'step_4' => 'Phase 11 moves uploads to the shared upload service and the private disk;',
            'step_5' => 'Phase 15 drops meeting_attachments.',
        ];
    }
}
