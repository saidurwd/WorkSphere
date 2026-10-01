<?php

namespace Modules\Todos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoChecklistItem;

/**
 * Checklist items hang off a To-Do and are governed by that To-Do's policy.
 *
 * Progress is recomputed on every read and stored nowhere, so there is no
 * aggregate to keep in step with the items.
 */
class TodoChecklistController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function store(Request $request, Todo $todo): RedirectResponse
    {
        $this->authorize('update', $todo);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $item = $todo->checklistItems()->create([
            'title' => $validated['title'],
            'sort_order' => $validated['sort_order'] ?? ($todo->checklistItems()->max('sort_order') ?? -1) + 1,
        ]);

        $this->activity->record(
            Todo::class,
            $todo,
            'checklist_item_added',
            null,
            ['item_id' => $item->id, 'title' => $item->title],
            $request->user()?->id,
        );

        return back()->with('success', 'Checklist item added.');
    }

    public function update(Request $request, Todo $todo, TodoChecklistItem $item): RedirectResponse
    {
        $this->authorize('update', $todo);

        abort_unless($item->todo_id === $todo->id, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'is_completed' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $completed = $request->boolean('is_completed');

        $item->update([
            'title' => $validated['title'],
            'is_completed' => $completed,
            'sort_order' => $validated['sort_order'] ?? $item->sort_order,
            // Both halves move together: a completed item with no completer, or
            // an open item stamped as completed by somebody, are both wrong.
            'completed_at' => $completed ? ($item->completed_at ?? now()) : null,
            'completed_by' => $completed ? ($item->completed_by ?? $request->user()?->id) : null,
        ]);

        $this->activity->record(
            Todo::class,
            $todo,
            'checklist_item_updated',
            ['title' => $item->getRawOriginal('title'), 'is_completed' => $item->getRawOriginal('is_completed')],
            ['title' => $item->title, 'is_completed' => $item->is_completed],
            $request->user()?->id,
        );

        return back()->with('success', 'Checklist item updated.');
    }

    public function destroy(Request $request, Todo $todo, TodoChecklistItem $item): RedirectResponse
    {
        $this->authorize('update', $todo);

        abort_unless($item->todo_id === $todo->id, 404);

        $item->delete();

        $this->activity->record(
            Todo::class,
            $todo,
            'checklist_item_removed',
            ['item_id' => $item->id, 'title' => $item->title],
            null,
            $request->user()?->id,
        );

        return back()->with('success', 'Checklist item removed.');
    }
}
