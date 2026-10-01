<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Writes user-facing activity rows to `activity_logs` so a record's timeline can
 * be rendered. This is the "what happened to my item" feed, not the security
 * audit trail — see {@see AuditLogger} for that.
 *
 * `$module` accepts either an FQCN or an already-shortened label; both are
 * normalised to the class basename so a row written by an observer and a row
 * written by a service are grouped identically.
 */
class ActivityLogger
{
    /**
     * `$actor` is the person responsible, which is NOT always the authenticated
     * user: a queued job or a console command acts on somebody's behalf, and
     * `Auth::id()` would be null there, leaving the trail unattributed. Callers
     * that know the actor pass it; anything else falls back to the session.
     *
     * @param  class-string<Model>|string  $module
     * @param  array<string, mixed>|null  $oldValue
     * @param  array<string, mixed>|null  $newValue
     */
    public function record(
        string $module,
        ?Model $subject,
        string $action,
        ?array $oldValue = null,
        ?array $newValue = null,
        ?int $actorId = null,
    ): ?ActivityLog {
        return ActivityLog::query()->create([
            'user_id' => $actorId ?? Auth::id(),
            'module_name' => class_basename($module),
            'record_id' => $subject?->getKey(),
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'action' => $action,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 255, ''),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  class-string<Model>  $model
     * @param  array<string, mixed>  $attributes
     */
    public function created(Model $model, string $module = ''): ?ActivityLog
    {
        return $this->record($module ?: $model::class, $model, 'created', null, $model->attributesToArray());
    }

    /**
     * @param  class-string<Model>  $model
     */
    public function updated(Model $model, string $module = ''): ?ActivityLog
    {
        return $this->record(
            $module ?: $model::class,
            $model,
            'updated',
            $model->getOriginal(),
            $model->attributesToArray(),
        );
    }

    /**
     * @param  class-string<Model>  $model
     */
    public function deleted(Model $model, string $module = ''): ?ActivityLog
    {
        return $this->record($module ?: $model::class, $model, 'deleted', $model->attributesToArray(), null);
    }
}
