<?php

namespace Modules\Todos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Todos\Models\Todo;

/**
 * Watchers are a visibility party, not an ownership right. A watcher can see the
 * To-Do and is notified about it; they cannot edit or delete it.
 */
class TodoWatcherController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function store(Request $request, Todo $todo): RedirectResponse
    {
        $this->authorize('update', $todo);

        $validated = $request->validate([
            'user_id' => ['required', Rule::exists('users', 'id')],
        ]);

        $watcher = User::query()->findOrFail($validated['user_id']);

        // firstOrCreate: the composite unique index makes a duplicate impossible,
        // so this is idempotent rather than a race.
        $todo->watchers()->firstOrCreate(['user_id' => $watcher->id]);

        $this->activity->record(
            Todo::class,
            $todo,
            'watcher_added',
            null,
            ['user_id' => $watcher->id],
            $request->user()?->id,
        );

        return back()->with('success', "{$watcher->name} can now see this To-Do.");
    }

    public function destroy(Request $request, Todo $todo, User $user): RedirectResponse
    {
        $this->authorize('update', $todo);

        $todo->watchers()->where('user_id', $user->id)->delete();

        $this->activity->record(
            Todo::class,
            $todo,
            'watcher_removed',
            ['user_id' => $user->id],
            null,
            $request->user()?->id,
        );

        return back()->with('success', 'Watcher removed.');
    }
}
