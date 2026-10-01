<?php

namespace Modules\Obligations\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\ObligationResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Obligations\Http\Requests\IndexObligationRequest;
use Modules\Obligations\Models\Obligation;

/**
 * `/api/v1/obligations` — read only.
 *
 * Visibility is `ObligationController::index`'s predicate: without
 * `obligation.view`, only obligations the caller owns or is an *active*
 * responsible party for. The `active` flag matters — a closed-out responsibility
 * row must not keep granting visibility, which is exactly what the web controller
 * already encodes and what an API that forgot it would silently widen.
 */
class ObligationApiController extends ApiController
{
    public function index(IndexObligationRequest $request): AnonymousResourceCollection
    {
        $user = $this->actor($request);

        $query = Obligation::query()->with(['owner', 'type', 'vendor']);

        if (! $this->allows($user, 'obligation.view')) {
            $query->where(function (Builder $inner) use ($user): void {
                $inner->where('owner_user_id', $user->id)
                    ->orWhereHas('responsibilities', function (Builder $responsibilities) use ($user): void {
                        $responsibilities->where('user_id', $user->id)->where('active', true);
                    });
            });
        }

        return $this->paginated($this->filter($query, $request), ObligationResource::class, $request);
    }

    public function show(Request $request, Obligation $obligation): ObligationResource
    {
        $this->authorizeAction($this->actor($request), 'view', $obligation);

        return new ObligationResource($obligation->load(['owner', 'type', 'vendor']));
    }

    /**
     * @param  Builder<Obligation>  $query
     * @return Builder<Obligation>
     */
    protected function filter(Builder $query, IndexObligationRequest $request): Builder
    {
        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->trim()->toString().'%';

            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('title', 'like', $term)
                    ->orWhere('obligation_no', 'like', $term);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority')->toString());
        }

        if ($request->filled('risk_level')) {
            $query->where('risk_level', $request->string('risk_level')->toString());
        }

        foreach (['obligation_type_id', 'category_id', 'company_id', 'department_id', 'vendor_id', 'owner_user_id'] as $column) {
            if ($request->filled($column)) {
                $query->where($column, $request->integer($column));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('start_date', '<=', $request->date('date_to'));
        }

        return $query;
    }
}
