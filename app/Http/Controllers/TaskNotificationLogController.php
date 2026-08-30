<?php

namespace App\Http\Controllers;

use App\Models\TaskNotificationLog;
use Illuminate\Http\Request;
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
}
