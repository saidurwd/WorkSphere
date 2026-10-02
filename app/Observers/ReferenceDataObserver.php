<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use App\Models\Vendor;
use App\Support\ReferenceData;
use Illuminate\Database\Eloquent\Model;

/**
 * Invalidates the cached reference lists when the underlying table changes.
 *
 * Registered for exactly the models `ReferenceData` serves. Anything that adds a
 * list must register here too, or that list will be rebuilt only when the TTL
 * expires — which is why the TTL exists, but it is a safety net and not a plan.
 *
 * `updated` fires for every save, not only for changed columns, because the cached
 * value is a WHOLE list: any write to a row can change what that row contributes.
 * Narrowing it to dirty attributes would be cheaper and would be wrong — a rename
 * that touches only `name` still changes the list.
 */
class ReferenceDataObserver
{
    public function __construct(private readonly ReferenceData $referenceData) {}

    public function created(Model $model): void
    {
        $this->refresh($model);
    }

    public function updated(Model $model): void
    {
        $this->refresh($model);
    }

    public function deleted(Model $model): void
    {
        $this->refresh($model);
    }

    public function restored(Model $model): void
    {
        $this->refresh($model);
    }

    private function refresh(Model $model): void
    {
        $this->referenceData->invalidate(self::resourceFor($model::class));
    }

    /**
     * The `ReferenceData` resource a model backs.
     *
     * Null for a model with no cached list, and `invalidate(null)` bumps the
     * wildcard — so registering this observer too widely is safe, just less
     * targeted.
     */
    public static function resourceFor(string $modelClass): ?string
    {
        return match ($modelClass) {
            User::class => 'users',
            Department::class => 'departments',
            Location::class => 'locations',
            Vendor::class => 'vendors',
            Company::class => 'companies',
            default => null,
        };
    }
}
