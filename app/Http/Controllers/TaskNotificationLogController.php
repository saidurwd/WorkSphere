<?php

namespace App\Http\Controllers;

use App\Models\TaskNotificationLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TaskNotificationLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = TaskNotificationLog::query()
            ->with(['task', 'user'])
            ->orderByDesc('created_at');

        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->string('status')->toString(),
            'channel' => $request->string('channel')->toString(),
            'notification_type' => $request->string('notification_type')->toString(),
            'task_id' => $request->integer('task_id', 0),
        ];

        $query->when($filters['search'] !== '', function ($q) use ($filters) {
            $search = "%{$filters['search']}%";
            $q->where('subject', 'like', $search)
                ->orWhere('message', 'like', $search)
                ->orWhereHas('task', function ($q2) use ($search) {
                    $q2->where('title', 'like', $search)
                        ->orWhere('task_no', 'like', $search);
                });
        })->when($filters['status'] !== '', function ($q) use ($filters) {
            $q->where('status', $filters['status']);
        })->when($filters['channel'] !== '', function ($q) use ($filters) {
            $q->where('channel', $filters['channel']);
        })->when($filters['notification_type'] !== '', function ($q) use ($filters) {
            $q->where('notification_type', $filters['notification_type']);
        })->when($filters['task_id'] > 0, function ($q) use ($filters) {
            $q->where('task_id', $filters['task_id']);
        });

        $logs = $query->paginate(20)->withQueryString();

        return view('tasks.notification_logs', [
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }

    public function destroy(TaskNotificationLog $log): RedirectResponse
    {
        if (! $this->canManageAllLogs()) {
            abort(403);
        }

        $log->delete();

        return back()->with('success', 'Notification log deleted successfully.');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        if (! $this->canManageAllLogs()) {
            abort(403);
        }

        $query = TaskNotificationLog::query();

        if ($search = $request->string('search')->trim()->toString()) {
            $search = "%{$search}%";
            $query->where('subject', 'like', $search)
                ->orWhere('message', 'like', $search)
                ->orWhereHas('task', function ($q) use ($search) {
                    $q->where('title', 'like', $search)
                        ->orWhere('task_no', 'like', $search);
                });
        }

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($channel = $request->string('channel')->toString()) {
            $query->where('channel', $channel);
        }

        if ($notificationType = $request->string('notification_type')->toString()) {
            $query->where('notification_type', $notificationType);
        }

        if ($taskId = $request->integer('task_id', 0)) {
            $query->where('task_id', $taskId);
        }

        $count = $query->delete();

        return redirect()->route('tasks.notification-logs.index')->with('success', "Deleted {$count} notification log(s).");
    }

    private function canManageAllLogs(): bool
    {
        $user = Auth::user();

        return $user !== null && method_exists($user, 'hasRole') && $user->hasRole('super-admin');
    }
}
