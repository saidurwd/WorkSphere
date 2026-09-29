<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()->with('user:id,name,email');

        if ($request->filled('event')) {
            $query->where('event', $request->string('event'));
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->string('auditable_type'));
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

        $events = AuditLog::query()->select('event')->distinct()->orderBy('event')->pluck('event');
        $types = AuditLog::query()->select('auditable_type')->distinct()->whereNotNull('auditable_type')->orderBy('auditable_type')->pluck('auditable_type');

        $logs = $query->latest('created_at')->paginate(30)->withQueryString();

        return view('admin.audit-logs.index', compact('logs', 'events', 'types'));
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user');

        return view('admin.audit-logs.show', compact('auditLog'));
    }
}
