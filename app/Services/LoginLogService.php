<?php

namespace App\Services;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginLogService
{
    public function record(Request $request, string $event, string $email, ?User $user = null, ?string $reason = null): LoginLog
    {
        return DB::transaction(fn (): LoginLog => LoginLog::query()->create([
            'user_id' => $user?->id,
            'email' => $email,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device' => $this->describeDevice($request),
            'failure_reason' => $reason,
            'attempted_at' => now(),
        ]));
    }

    /**
     * Record a successful sign in.
     */
    public function recordLogin(Request $request, User $user): LoginLog
    {
        return $this->record($request, LoginLog::LOGIN, $user->email, $user);
    }

    /**
     * Record a sign out, falling back to the session email when the guard is gone.
     */
    public function recordLogout(Request $request, ?User $user, string $email): LoginLog
    {
        return $this->record($request, LoginLog::LOGOUT, $user?->email ?? $email, $user);
    }

    /**
     * Record a rejected sign in attempt.
     */
    public function recordFailure(Request $request, string $email, string $event = LoginLog::FAILED, ?string $reason = null): LoginLog
    {
        return $this->record($request, $event, $email, reason: $reason);
    }

    /**
     * Best effort device description derived from the user agent string.
     */
    protected function describeDevice(Request $request): string
    {
        return (string) $request->userAgent();
    }
}
