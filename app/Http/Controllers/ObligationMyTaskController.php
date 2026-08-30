<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ObligationMyTaskController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $query = Task::query()
            ->whereNotNull('obligation_id')
            ->with(['obligation', 'obligation.type', 'obligation.department'])
            ->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                    ->orWhere('responsible_user_id', $user->id);
            });

        $filters = [
            'status' => request()->string('status')->toString(),
            'priority' => request()->string('priority')->toString(),
            'due_date' => request()->string('due_date')->toString(),
        ];

        $query->when($filters['status'] !== '', function ($q) use ($filters) {
            $q->where('status', $filters['status']);
        })->when($filters['priority'] !== '', function ($q) use ($filters) {
            $q->where('priority', $filters['priority']);
        })->when($filters['due_date'] !== '', function ($q) use ($filters) {
            $today = now()->startOfDay();
            $q->when($filters['due_date'] === 'today', function ($q2) use ($today) {
                $q2->whereDate('due_date', $today);
            })->when($filters['due_date'] === 'overdue', function ($q2) use ($today) {
                $q2->where('status', '!=', 'completed')->where('due_date', '<', $today);
            })->when($filters['due_date'] === 'upcoming', function ($q2) use ($today) {
                $q2->where('status', '!=', 'completed')->whereDate('due_date', '>', $today);
            });
        });

        $tasks = $query->orderByDesc('due_date')->paginate(15)->withQueryString();

        return view('obligations.my-tasks', [
            'tasks' => $tasks,
            'filters' => $filters,
        ]);
    }
}
