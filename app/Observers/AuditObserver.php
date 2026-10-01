<?php

namespace App\Observers;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

/**
 * Writes an audit row for every create / update / delete on a sensitive model.
 *
 * Attached in AppServiceProvider::$observe. Related-record pivot changes are not
 * observed here: a pivot row has no model lifecycle, and the owning model's own
 * updates are already captured.
 */
class AuditObserver
{
    public function __construct(protected AuditLogger $audit) {}

    public function created(Model $model): void
    {
        $this->audit->created($model, $model->attributesToArray());
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

        $this->audit->updated($model, $original, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->audit->deleted($model, $model->attributesToArray());
    }

    /**
     * A soft-deleted model is not gone; record the restore explicitly so the
     * trail shows the row was hidden and re-shown rather than silently changing.
     */
    public function restored(Model $model): void
    {
        $this->audit->record('restored', $model, null, $model->attributesToArray());
    }
}
