<?php

namespace App\Http\Controllers;

use App\Models\NotificationLog;
use App\Models\Obligation;
use Illuminate\View\View;

class ObligationNotificationController extends Controller
{
    public function index(): View
    {
        $query = NotificationLog::query()
            ->with(['obligation', 'obligation.type', 'obligation.department', 'user', 'rule']);

        $filters = [
            'search' => request()->string('search')->trim()->toString(),
            'status' => request()->string('status')->toString(),
            'channel' => request()->string('channel')->toString(),
            'obligation_id' => request()->integer('obligation_id', 0),
        ];

        $query->when($filters['search'] !== '', function ($q) use ($filters) {
            $search = "%{$filters['search']}%";
            $q->where('subject', 'like', $search)
                ->orWhereHas('obligation', function ($q2) use ($search) {
                    $q2->where('obligation_no', 'like', $search)
                        ->orWhere('title', 'like', $search);
                });
        })->when($filters['status'] !== '', function ($q) use ($filters) {
            $q->where('status', $filters['status']);
        })->when($filters['channel'] !== '', function ($q) use ($filters) {
            $q->where('channel', $filters['channel']);
        })->when($filters['obligation_id'] > 0, function ($q) use ($filters) {
            $q->where('obligation_id', $filters['obligation_id']);
        });

        $notifications = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return view('obligations.notifications', [
            'notifications' => $notifications,
            'filters' => $filters,
        ]);
    }
}
