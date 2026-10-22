<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Listeners\RecordLogout;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sign-out for the pages that still use the application layout. The logout is
 * written to the login log by {@see RecordLogout}, so it is recorded
 * the same way whichever screen the person signed out from.
 */
class AuthenticatedSessionController extends Controller
{
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
