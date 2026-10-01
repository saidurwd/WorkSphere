<?php

namespace Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TimeEntry;

/**
 * Time tracking — GAP-025.
 *
 * `actual_minutes` on the task is a cache of the sum of these rows, so every
 * write here resyncs it. Doing it in one place means the cache cannot drift: a
 * second code path adding an entry would otherwise leave the headline figure
 * stale with nothing to correct it.
 */
class TaskTimeEntryController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('logTime', $task);

        $validated = $request->validate([
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'logged_on' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $entry = $task->timeEntries()->create([
            'user_id' => $request->user()->id,
            'minutes' => $validated['minutes'],
            'logged_on' => $validated['logged_on'],
            'note' => $validated['note'] ?? null,
        ]);

        $task->syncActualMinutes();

        $this->activity->record(
            Task::class,
            $task,
            'time_logged',
            null,
            ['entry_id' => $entry->id, 'minutes' => $entry->minutes],
            $request->user()->id,
        );

        return back()->with('success', "{$entry->minutes} minutes logged.");
    }

    public function destroy(Request $request, Task $task, TimeEntry $entry): RedirectResponse
    {
        $this->authorize('logTime', $task);

        abort_unless($entry->task_id === $task->id, 404);

        $entry->delete();

        $task->syncActualMinutes();

        $this->activity->record(
            Task::class,
            $task,
            'time_removed',
            ['entry_id' => $entry->id],
            null,
            $request->user()->id,
        );

        return back()->with('success', 'Time entry removed.');
    }
}
