<?php

namespace App\Http\Controllers\Api;

use App\Http\ApiErrorCode;
use App\Http\Requests\ApiIndexRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Shared behaviour for every `/api/v1` controller.
 *
 * Three things every endpoint needs and none of them should be reimplemented per
 * module:
 *
 * - **Permission-filtered pagination.** {@see filtered()} applies a predicate and
 *   paginates in one call, so "filter in the query" is the default rather than
 *   something a controller has to remember. Filtering in the query rather than in
 *   the view is a security requirement, not a performance choice: a row hidden
 *   in Blade but present in the JSON is still a disclosure.
 * - **A 403 the caller can distinguish from a 404.** Route model binding 404s an
 *   id that does not exist; a policy denial on a record that does exist is a 403.
 * - **A single JSON envelope.** Resources handle the success shape; the exception
 *   renderer handles every failure shape.
 */
abstract class ApiController
{
    /**
     * Paginate an already-scoped query and wrap it in a resource collection.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>  $query
     * @param  class-string<JsonResource>  $resource
     */
    protected function paginated(Builder $query, string $resource, ApiIndexRequest $request, int $perPage = 25): AnonymousResourceCollection
    {
        $request->applySorting($query);

        return $resource::collection(
            $query->paginate($request->perPage() ?: $perPage)->withQueryString(),
        );
    }

    /**
     * A 403 when the policy denies, for a record the caller demonstrably knows
     * exists.
     *
     * Used where a controller fetches a record by a query the scope has already
     * narrowed, so an absent record and a forbidden one would otherwise be
     * indistinguishable — and the client would be told 404 for a row it can see
     * in a list.
     */
    protected function authorizeAction(User $user, string $ability, mixed $target): void
    {
        Gate::forUser($user)->authorize($ability, $target);
    }

    /**
     * @throws NotFoundHttpException
     */
    protected function notFound(): never
    {
        throw new NotFoundHttpException(ApiErrorCode::defaultMessage(ApiErrorCode::NotFound));
    }

    /**
     * Whether this caller holds a permission. Named clearly because it reads as a
     * gate check at every call site and is used to decide whether a filter is
     * needed at all, not to authorise a single record.
     */
    protected function allows(User $user, string $permission): bool
    {
        return $user->hasPermission($permission);
    }

    /**
     * The authenticated user, or a 401.
     *
     * Every route here is behind `auth:sanctum`, so in practice this cannot be
     * null — but the type is `?User` and a null would otherwise surface much later
     * as a permission check against nothing.
     */
    protected function actor(Request $request): User
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(401, ApiErrorCode::defaultMessage(ApiErrorCode::Unauthenticated));
        }

        return $user;
    }
}
