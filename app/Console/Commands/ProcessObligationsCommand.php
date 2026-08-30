<?php

namespace App\Console\Commands;

use App\Models\Obligation;
use App\Services\ObligationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessObligationsCommand extends Command
{
    protected $signature = 'obligations:process';

    protected $description = 'Process obligations for notifications, escalations, and status updates';

    public function handle(ObligationService $obligationService): int
    {
        $this->info('Processing obligations...');

        $processed = 0;
        $errors = 0;

        Obligation::query()
            ->whereNotIn('status', ['renewed', 'cancelled', 'not_required', 'archived'])
            ->chunk(100, function ($obligations) use ($obligationService, &$processed, &$errors) {
                foreach ($obligations as $obligation) {
                    try {
                        $obligationService->processObligation($obligation);
                        $processed++;
                    } catch (\Throwable $e) {
                        $errors++;
                        Log::error('Failed to process obligation: '.$obligation->id, [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            });

        $this->info("Processed {$processed} obligations successfully.");
        if ($errors > 0) {
            $this->warn("Encountered {$errors} errors. Check logs for details.");
        }

        return $errors > 0 ? 1 : 0;
    }
}
