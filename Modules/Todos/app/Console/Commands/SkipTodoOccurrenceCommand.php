<?php

namespace Modules\Todos\Console\Commands;

use App\Enums\ReminderStatus;
use App\Models\Reminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoRecurrenceService;
use Modules\Todos\Services\TodoService;
use Throwable;

/**
 * Moves a recurring To-Do's next occurrence forward without completing it.
 *
 * The operation behind the "skip this week" affordance: the current row is left
 * untouched — skipping one occurrence changes *when the next one is due*, not
 * whether the current one exists.
 */
class SkipTodoOccurrenceCommand extends Command
{
    protected $signature = 'todos:skip
                            {todo? : Id of the To-Do to skip. Omit to skip every overdue recurring To-Do.}
                            {--reason= : Recorded on the activity log, e.g. "public holiday"}
                            {--limit= : Maximum To-Dos to process when no id is given}';

    protected $description = 'Skip the next occurrence of a recurring To-Do. Safe to run repeatedly.';

    public function __construct(
        private readonly TodoRecurrenceService $recurrence,
        private readonly TodoService $todos,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $ids = $this->argument('todo')
            ? [$this->argument('todo')]
            : $this->dueIds();

        if ($ids === []) {
            $this->info('Nothing to skip.');

            return self::SUCCESS;
        }

        $skipped = 0;
        $finished = 0;
        $failed = 0;

        foreach ($ids as $id) {
            try {
                $todo = Todo::query()->withoutGlobalScopes()->find($id);

                if ($todo === null || ! $todo->isRecurring()) {
                    continue;
                }

                $next = $this->recurrence->skip(
                    $todo,
                    $todo->recurrence_rule,
                    $this->recurrence->occurrenceCount($todo),
                );

                if ($next === null) {
                    // The series has ended: no further occurrence exists to skip.
                    $finished++;

                    continue;
                }

                $this->todos->update($todo->creator ?? $todo->assignee, $todo, [
                    'start_date' => $next->toDateString(),
                    'due_date' => $next->toDateString(),
                ]);

                // A reminder pinned to the skipped date would fire about work
                // that has moved; cancel rather than let it fire.
                Reminder::query()
                    ->where('subject_type', Todo::class)
                    ->where('subject_id', $todo->id)
                    ->where('status', ReminderStatus::Pending->value)
                    ->whereDate('remind_at', '<', $next)
                    ->update([
                        'status' => ReminderStatus::Cancelled->value,
                        'cancelled_at' => now(),
                        'updated_at' => now(),
                    ]);

                $skipped++;
            } catch (Throwable $e) {
                $failed++;

                Log::error('Occurrence skip failed', [
                    'todo_id' => $id,
                    'error' => $e::class,
                ]);

                $this->warn("To-Do #{$id} could not be skipped.");
            }
        }

        $this->info(sprintf(
            'Skipped %d occurrence(s); %d series finished; %d failed.',
            $skipped,
            $finished,
            $failed,
        ));

        return self::SUCCESS;
    }

    /**
     * Recurring To-Dos still sitting on a date that has passed.
     *
     * @return list<int>
     */
    protected function dueIds(): array
    {
        return Todo::query()
            ->withoutGlobalScopes()
            ->recurring()
            ->active()
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', now()->toDateString())
            ->orderBy('due_date')
            ->limit(max(1, (int) $this->option('limit')))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
