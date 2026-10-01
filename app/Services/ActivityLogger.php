<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Writes user-facing activity rows to `activity_logs` so a record's timeline can
 * be rendered. This is the "what happened to my item" feed, not the security
 * audit trail — see {@see AuditLogger} for that.
 */
class ActivityLogger
{
    public function record(string $module, ?Model $subject, string $action, ?array $oldValue = null, ?array $newValue = null): ?ActivityLog
    {
        return ActivityLog::query()->create([
            'user_id' => Auth::id(),
            'module_name' => $module,
            'record_id' => $subject?->getKey(),
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    public function created(Model $subject, string $module): ?ActivityLog
    {
        return $this->record($module, $subject, 'created', null, $subject->attributesToArray());
    }

    public function updated(Model $subject, string $module): ?ActivityLog
    {
        return $this->record(
            $module,
            $subject,
            'updated',
            $subject->getOriginal(),
            $subject->attributesToArray(),
        );
    }

    public function deleted(Model $subject, string $module): ?ActivityLog
    {
        return $this->record($module, $subject, 'deleted', $subject->attributesToArray(), null);
    }
}
