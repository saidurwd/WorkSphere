<?php

namespace Modules\Obligations\Console\Commands;

use Illuminate\Console\Command;
use Modules\Obligations\Services\ObligationDocumentSynchroniser;

/**
 * Mirrors obligation documents uploaded before the dual-write onto the shared
 * `attachments` table — GAP-048. Idempotent by path, so a re-run is safe and a
 * partial run is resumable.
 */
class BackfillObligationDocumentsCommand extends Command
{
    protected $signature = 'obligations:backfill-documents
                            {--chunk=200 : Rows per pass}';

    protected $description = 'Mirror obligation documents onto the shared attachments table. Safe to re-run.';

    public function handle(ObligationDocumentSynchroniser $documents): int
    {
        $mirrored = $documents->backfill(max(1, min(2000, (int) $this->option('chunk'))));

        $this->info("Mirrored {$mirrored} obligation document(s).");

        return self::SUCCESS;
    }
}
