<?php

namespace Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Tasks\Models\Task;

/**
 * Task watchers — GAP-025.
 *
 * Mirrors the To-Do watcher controller, including the idempotent `firstOrCreate`:
 * the composite unique index makes a duplicate impossible, so adding twice is a
 * no-op rather than a constraint violation.
 */
class TaskWatcherController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('manageWatchers', $task);

        $validated = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')],
        ]);

        $watcher = User::query()->findOrFail($validated['user_id']);

        $task->watchers()->firstOrCreate(['user_id' => $watcher->id]);

        return back()->with('success', "{$watcher->name} can now see this task.");
    }

    public function destroy(Request $request, Task $task, User $user): RedirectResponse
    {
        $this->authorize('manageWatchers', $task);

        $task->watchers()->where('user_id', $user->id)->delete();

        return back()->with('success', 'Watcher removed.');
    }
}
