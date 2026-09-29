<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = ActivityLog::query()->with('user:id,name,email');

        if ($request->filled('module')) {
            $query->where('module_name', $request->string('module'));
        }

        if ($request->filled('action')) {
            $query->where('action', $request->string('action'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->string('date_to'));
        }

        $modules = ActivityLog::query()->select('module_name')->distinct()->whereNotNull('module_name')->orderBy('module_name')->pluck('module_name');
        $actions = ActivityLog::query()->select('action')->distinct()->whereNotNull('action')->orderBy('action')->pluck('action');

        $logs = $query->latest('created_at')->paginate(30)->withQueryString();

        return view('admin.activity-logs.index', compact('logs', 'modules', 'actions'));
    }

    public function show(ActivityLog $activityLog): View
    {
        $activityLog->load('user');

        return view('admin.activity-logs.show', compact('activityLog'));
    }
}
