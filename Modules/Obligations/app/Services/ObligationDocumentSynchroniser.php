<?php

namespace Modules\Obligations\Services;

use App\Models\Attachment;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationDocument;

/**
 * Dual-writes obligation documents into the shared `attachments` table — GAP-048.
 *
 * `obligation_documents` stays the read path (it carries `document_type` and
 * `document_date`, which the platform table has no column for); the shared table
 * is what makes an upload findable across modules.
 *
 * No file is copied: both rows point at the same object on the same disk, so a
 * cleanup cannot leave two divergent copies, and a record move keeps one file.
 *
 * The disk is recorded as the one actually used rather than the platform default,
 * so a reader is never told a private path for a file on another disk.
 */
class ObligationDocumentSynchroniser
{
    public function mirror(Obligation $obligation, ObligationDocument $document, string $disk = 'public'): ?Attachment
    {
        return Attachment::query()->updateOrCreate(
            [
                'attachable_type' => Obligation::class,
                'attachable_id' => $obligation->id,
                'path' => $document->file_path,
            ],
            [
                'disk' => $disk,
                'original_name' => $document->file_name,
                'mime_type' => $document->mime_type,
                'size' => $document->file_size,
                'uploaded_by' => $document->uploaded_by,
            ],
        );
    }

    /**
     * Backfill documents created before this dual-write.
     *
     * Idempotent by path: `updateOrCreate` on (subject, path) means a re-run
     * updates rather than duplicating.
     */
    public function backfill(int $chunk = 200): int
    {
        $mirrored = 0;
        $cursor = 0;

        do {
            $documents = ObligationDocument::query()
                ->where('id', '>', $cursor)
                ->orderBy('id')
                ->limit($chunk)
                ->get();

            foreach ($documents as $document) {
                $obligation = Obligation::query()->find($document->obligation_id);

                if ($obligation !== null) {
                    $this->mirror($obligation, $document);
                    $mirrored++;
                }

                $cursor = $document->id;
            }
        } while ($documents->count() === $chunk);

        return $mirrored;
    }

    /**
     * @return array<string, string>
     */
    public static function cutoverPlan(): array
    {
        return [
            'step_1' => 'Dual-write every new upload (this class). Both tables populated.',
            'step_2' => 'Backfill historical documents with backfill(), idempotent by path.',
            'step_3' => 'Point the download controller at the shared table and keep the platform download service.',
            'step_4' => 'Phase 11 moves uploads to the private disk; this class records the disk actually used.',
            'step_5' => 'Phase 15 drops obligation_documents.',
        ];
    }
}
