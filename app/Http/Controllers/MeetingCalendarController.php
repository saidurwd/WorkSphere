<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingActionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MeetingCalendarController extends Controller
{
    public function index(): View
    {
        return view('meetings.calendar');
    }

    public function events(): JsonResponse
    {
        $user = Auth::user();
        $query = Meeting::query()->select('id', 'title', 'meeting_date', 'start_time', 'end_time', 'status', 'priority');

        if (! $this->canManageAll($user)) {
            $query->where(function ($q) use ($user) {
                $q->where('organizer_id', $user->id)
                    ->orWhereHas('participants', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id);
                    });
            });
        }

        $meetings = $query->get()->map(fn ($m) => [
            'id' => 'meeting-'.$m->id,
            'title' => $m->title,
            'start' => $m->meeting_date->format('Y-m-d').'T'.$m->start_time->format('H:i:s'),
            'end' => $m->meeting_date->format('Y-m-d').'T'.$m->end_time->format('H:i:s'),
            'url' => route('meetings.show', $m),
            'color' => match ($m->priority) {
                'urgent' => '#dc2626',
                'important' => '#f59e0b',
                default => '#3b82f6',
            },
            'textColor' => '#ffffff',
            'extendedProps' => [
                'status' => $m->status,
                'type' => 'meeting',
            ],
        ]);

        $actions = MeetingActionItem::query()
            ->whereNotNull('due_date')
            ->select('id', 'title', 'due_date', 'status', 'meeting_id')
            ->get()
            ->map(fn ($a) => [
                'id' => 'action-'.$a->id,
                'title' => $a->title,
                'start' => $a->due_date->format('Y-m-d'),
                'url' => route('meetings.show', $a->meeting_id),
                'color' => match ($a->status) {
                    'completed' => '#10b981',
                    'in_progress' => '#3b82f6',
                    'open' => '#f59e0b',
                    default => '#6b7280',
                },
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'status' => $a->status,
                    'type' => 'action',
                ],
            ]);

        return response()->json($meetings->merge($actions));
    }

    private function canManageAll(mixed $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('super-admin');
    }
}
