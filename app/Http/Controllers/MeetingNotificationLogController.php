<?php

namespace App\Http\Controllers;

use App\Models\MeetingNotificationLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingNotificationLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = MeetingNotificationLog::query()
            ->with(['meeting', 'actionItem', 'user'])
            ->orderByDesc('created_at');

        $filters = [
            'search' => $request->string('search')->trim()->toString(),
            'status' => $request->string('status')->toString(),
            'channel' => $request->string('channel')->toString(),
            'notification_type' => $request->string('notification_type')->toString(),
            'meeting_id' => $request->integer('meeting_id', 0),
        ];

        $query->when($filters['search'] !== '', function ($q) use ($filters) {
            $search = "%{$filters['search']}%";
            $q->where('subject', 'like', $search)
                ->orWhere('message', 'like', $search)
                ->orWhereHas('meeting', function ($q2) use ($search) {
                    $q2->where('title', 'like', $search)
                        ->orWhere('meeting_no', 'like', $search);
                })
                ->orWhereHas('actionItem', function ($q3) use ($search) {
                    $q3->where('title', 'like', $search);
                });
        })->when($filters['status'] !== '', function ($q) use ($filters) {
            $q->where('status', $filters['status']);
        })->when($filters['channel'] !== '', function ($q) use ($filters) {
            $q->where('channel', $filters['channel']);
        })->when($filters['notification_type'] !== '', function ($q) use ($filters) {
            $q->where('notification_type', $filters['notification_type']);
        })->when($filters['meeting_id'] > 0, function ($q) use ($filters) {
            $q->where('meeting_id', $filters['meeting_id']);
        });

        $logs = $query->paginate(20)->withQueryString();

        return view('meetings.notification_logs', [
            'logs' => $logs,
            'filters' => $filters,
        ]);
    }
}
