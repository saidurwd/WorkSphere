<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoginLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = LoginLog::query()->with('user:id,name,email');

        if ($request->filled('event')) {
            $query->where('event', $request->string('event'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->integer('user_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate('attempted_at', '>=', $request->string('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('attempted_at', '<=', $request->string('date_to'));
        }

        $events = LoginLog::query()->select('event')->distinct()->orderBy('event')->pluck('event');

        $logs = $query->latest('attempted_at')->paginate(30)->withQueryString();

        return view('admin.login-logs.index', compact('logs', 'events'));
    }

    public function show(LoginLog $loginLog): View
    {
        $loginLog->load('user');

        return view('admin.login-logs.show', compact('loginLog'));
    }
}
