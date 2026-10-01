<?php

namespace Modules\Todos\Console\Commands;

use App\Enums\WorkItemStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoRecurrenceService;
use Throwable;

/**
 * Materialises the next occurrence of a recurring series.
 *
 * Phase 4 generates an occurrence on completion (§5.1). This command exists for
 * the cases completion does not cover: a To-Do whose next occurrence fell past
 * its date while nobody opened it, and a series whose To-Do was completed outside
 * the application — by a script, an import, or another operator.
 *
 * Rule-on-row, materialise-on-completion, so this does not pre-create future rows:
 * it only fills a gap where the series is open and due.
 */
class GenerateTodoOccurrencesCommand extends Command
{
    protected $signature = 'todos:generate
                            {--limit=500 : Maximum To-Dos to inspect in one pass}';

    protected $description = 'Generate any missing next occurrence for a recurring To-Do. Safe to run repeatedly.';

    public function __construct(private readonly TodoRecurrenceService $recurrence)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $generated = 0;
        $examined = 0;
        $failed = 0;

        Todo::query()
            ->recurring()
            ->whereIn('status', [WorkItemStatus::InProgress->value, WorkItemStatus::Planned->value])
            ->whereDate('due_date', '<=', now()->toDateString())
            ->with('assignee:id')
            ->orderBy('id')
            ->chunkById(max(1, (int) $this->option('limit')), function ($todos) use (&$generated, &$examined, &$failed): void {
                foreach ($todos as $todo) {
                    $examined++;

                    try {
                        $occurrence = $this->recurrence->occurrenceCount($todo);

                        $next = $this->recurrence->advance($todo, $occurrence);

                        if ($next !== null) {
                            $generated++;
                        }
                    } catch (Throwable $e) {
                        $failed++;

                        // Subject id and exception class only — never the rule
                        // payload, which may carry dates and recipient context.
                        Log::error('Recurrence generation failed', [
                            'todo_id' => $todo->getKey(),
                            'error' => $e::class,
                        ]);

                        $this->warn("To-Do #{$todo->getKey()} could not generate its next occurrence.");
                    }
                }
            });

        $this->info(sprintf(
            'Examined %d recurring To-Do(s); generated %d occurrence(s); %d failed.',
            $examined,
            $generated,
            $failed,
        ));

        return self::SUCCESS;
    }
}
