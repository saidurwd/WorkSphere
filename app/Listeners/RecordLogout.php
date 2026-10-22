<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\LoginLogService;
use Illuminate\Auth\Events\Logout;

/**
 * Write a logout to the login log, whichever screen the person signed out from.
 */
class RecordLogout
{
    public function __construct(private readonly LoginLogService $logs) {}

    public function handle(Logout $event): void
    {
        if (! $event->user instanceof User || app()->runningInConsole() && ! request()->hasSession()) {
            return;
        }

        $this->logs->recordLogout(request(), $event->user, (string) $event->user->email);
    }
}
