<?php

namespace Modules\Tasks\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Tasks\Models\Task;

/**
 * Task tags via the shared `taggables` table — GAP-025 / GAP-048.
 *
 * One vocabulary across Tasks, To-Dos and Meetings: `Tag::findOrCreateByName()`
 * is keyed on the slug, so attaching "Budget" here finds the same tag a meeting
 * used rather than creating a near-duplicate.
 */
class TaskTagController extends Controller
{
    public function store(Request $request, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'tag' => ['required', 'string', 'max:100'],
        ]);

        $tag = Tag::findOrCreateByName(trim($validated['tag']));

        // syncWithoutDetaching rather than attach: attaching an existing tag
        // twice must be a no-op, and `attach()` would duplicate the row.
        $task->tags()->syncWithoutDetaching([$tag->id]);

        return back()->with('success', "Tagged “{$tag->name}”.");
    }

    public function destroy(Request $request, Task $task, Tag $tag): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->tags()->detach($tag->id);

        return back()->with('success', 'Tag removed.');
    }
}
