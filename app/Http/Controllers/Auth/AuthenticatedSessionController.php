<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\LoginLog;
use App\Services\LoginLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(private readonly LoginLogService $loginLogs) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();
        } catch (\Illuminate\Validation\ValidationException) {
            $request->recordFailure($this->loginLogs);
            throw;
        }

        $this->loginLogs->recordLogin($request, $request->user());

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard.index'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->loginLogs->recordLogout($request, $user, (string) $request->session()->get('auth.email', $user?->email ?? 'unknown'));

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}