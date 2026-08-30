<?php

namespace App\Http\Controllers;

use App\Models\Obligation;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ObligationCalendarController extends Controller
{
    public function events(): JsonResponse
    {
        $user = Auth::user();
        $query = Obligation::query()->select('id', 'title', 'expiry_date', 'start_date', 'status', 'risk_level');

        if (! $this->canManageAll($user)) {
            $query->where(function ($q) use ($user) {
                $q->where('owner_user_id', $user->id)
                    ->orWhereHas('responsibilities', function ($q2) use ($user) {
                        $q2->where('user_id', $user->id)->where('active', true);
                    });
            });
        }

        $obligations = $query->get()->map(fn ($o) => [
            'id' => 'obligation-'.$o->id,
            'title' => $o->title,
            'start' => $o->start_date->format('Y-m-d'),
            'end' => $o->expiry_date->copy()->addDay()->format('Y-m-d'),
            'url' => route('obligations.show', $o),
            'color' => match ($o->risk_level) {
                'critical' => '#dc2626',
                'high' => '#f59e0b',
                'medium' => '#3b82f6',
                'low' => '#10b981',
            },
            'textColor' => '#ffffff',
            'extendedProps' => [
                'status' => $o->status,
                'type' => 'obligation',
            ],
        ]);

        $tasks = Task::query()
            ->whereNotNull('obligation_id')
            ->select('id', 'title', 'due_date', 'status')
            ->get()
            ->map(fn ($t) => [
                'id' => 'task-'.$t->id,
                'title' => $t->title,
                'start' => $t->due_date->format('Y-m-d'),
                'url' => route('tasks.show', $t),
                'color' => match ($t->status) {
                    'completed' => '#10b981',
                    'in_progress' => '#3b82f6',
                    'pending' => '#f59e0b',
                    default => '#6b7280',
                },
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'status' => $t->status,
                    'type' => 'task',
                ],
            ]);

        return response()->json($obligations->merge($tasks));
    }

    public function index(): View
    {
        return view('obligations.calendar');
    }

    private function canManageAll(mixed $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('super-admin');
    }
}
