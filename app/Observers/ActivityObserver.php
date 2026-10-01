<?php

namespace App\Observers;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes a user-facing timeline row to `activity_logs` for every work-item
 * mutation, so a record's detail page can show "what happened to this".
 *
 * Distinct from {@see AuditObserver}, which produces the security trail.
 * Attached in AppServiceProvider::$observers alongside it.
 */
class ActivityObserver
{
    public function __construct(protected ActivityLogger $activity) {}

    public function created(Model $model): void
    {
        $this->activity->record($this->module($model), $model, 'created', null, $model->attributesToArray());
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();

        if ($changes === []) {
            return;
        }

        $original = [];

        foreach (array_keys($changes) as $key) {
            $original[$key] = $model->getRawOriginal($key);
        }

        $this->activity->record($this->module($model), $model, 'updated', $original, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->activity->record($this->module($model), $model, 'deleted', $model->attributesToArray(), null);
    }

    public function restored(Model $model): void
    {
        $this->activity->record($this->module($model), $model, 'restored', null, $model->attributesToArray());
    }

    /**
     * `activity_logs.module_name` is the human label used by the timeline views,
     * so it is derived from the model class rather than hard-coded per observer.
     */
    protected function module(Model $model): string
    {
        return class_basename($model);
    }
}
