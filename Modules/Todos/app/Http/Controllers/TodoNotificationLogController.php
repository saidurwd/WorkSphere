<?php

namespace Modules\Todos\Http\Controllers;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Modules\Todos\Models\Todo;

/**
 * To-Do delivery history, mirroring the Tasks and Meetings log screens.
 *
 * Reads `notification_logs` — the consolidated table Phase 3 made polymorphic —
 * rather than a To-Do-specific one. An index is a cheap read; a duplicate log
 * table is not, and §4.9 exists precisely to prevent one.
 */
class TodoNotificationLogController extends Controller
{
    public function index(Request $request): View
    {
        // `view_all` is the right gate: the log names every recipient and every
        // subject, so it is an administrative view rather than a personal one.
        $this->authorize('todo.view_all');

        $logs = DB::table('notification_logs')
            ->where('subject_type', Todo::class)
            ->when($request->filled('type'), fn ($query) => $query->where('notification_type', $request->input('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->input('status')))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('todos.notification_logs', [
            'logs' => $logs,
            'filters' => $request->only(['type', 'status']),
            'types' => NotificationType::cases(),
        ]);
    }

    public function destroy(Request $request, int $log): RedirectResponse
    {
        // Deleting delivery evidence is super-admin only, matching the equivalent
        // endpoints in Tasks and Obligations.
        $this->authorize('todo.delete_notification_logs');

        DB::table('notification_logs')
            ->where('subject_type', Todo::class)
            ->where('id', $log)
            ->delete();

        return back()->with('success', 'Notification log removed.');
    }
}
