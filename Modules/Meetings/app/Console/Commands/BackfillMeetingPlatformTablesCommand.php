<?php

namespace Modules\Meetings\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingTag;
use Modules\Meetings\Services\MeetingAttachmentSynchroniser;
use Modules\Meetings\Services\MeetingTagSynchroniser;
use Throwable;

/**
 * Backfills the Meetings legacy tables onto the shared platform tables — GAP-048.
 *
 * Dual-write is already live (Phase 7 tags, Phase 8 attachments); this covers the
 * rows that predate it. Both steps are idempotent — tags by slug, attachments by
 * path — so a re-run is safe and a partial run is resumable.
 *
 * Phase 8's read cutover depends on this having been run and reconciled; the
 * drops are Phase 15.
 */
class BackfillMeetingPlatformTablesCommand extends Command
{
    protected $signature = 'meetings:backfill-platform-tables
                            {--chunk=200 : Rows per pass}
                            {--only= : Limit to tags or attachments}';

    protected $description = 'Mirror meeting tags and attachments onto the shared platform tables. Safe to re-run.';

    public function handle(
        MeetingTagSynchroniser $tags,
        MeetingAttachmentSynchroniser $attachments,
    ): int {
        $chunk = max(1, min(2000, (int) $this->option('chunk')));
        $only = $this->option('only');

        $failed = 0;

        if ($only === null || $only === 'tags') {
            try {
                $mirrored = $this->backfillTags($tags, $chunk);
                $this->info("Tags mirrored: {$mirrored}.");
            } catch (Throwable $e) {
                $failed++;
                // Counts and the exception class only: no tag names, no paths.
                Log::error('Meeting tag backfill failed', ['error' => $e::class]);
                $this->error('Tag backfill failed: '.$e::getMessage());
            }
        }

        if ($only === null || $only === 'attachments') {
            try {
                $mirrored = $this->backfillAttachments($attachments, $chunk);
                $this->info("Attachments mirrored: {$mirrored}.");
            } catch (Throwable $e) {
                $failed++;
                Log::error('Meeting attachment backfill failed', ['error' => $e::class]);
                $this->error('Attachment backfill failed: '.$e::getMessage());
            }
        }

        // Advisory, per the phase conventions: a partial backfill is resumable, so
        // a non-zero exit would page an operator for no operational gain.
        return self::SUCCESS;
    }

    protected function backfillTags(MeetingTagSynchroniser $synchroniser, int $chunk): int
    {
        $mirrored = 0;
        $cursor = 0;

        do {
            $rows = DB::table('meeting_tag_map')
                ->where('meeting_id', '>', $cursor)
                ->orderBy('meeting_id')
                ->limit($chunk)
                ->get();

            foreach ($rows as $row) {
                $meeting = Meeting::query()->find($row->meeting_id);
                $legacy = MeetingTag::query()->find($row->tag_id);

                if ($meeting !== null && $legacy !== null) {
                    $synchroniser->sync($meeting->id, collect([$legacy]));
                    $mirrored++;
                }

                $cursor = $row->meeting_id;
            }
        } while ($rows->count() === $chunk);

        return $mirrored;
    }

    protected function backfillAttachments(MeetingAttachmentSynchroniser $synchroniser, int $chunk): int
    {
        return $synchroniser->backfill($chunk);
    }
}
