<?php

namespace App\Exceptions;

use App\Http\ApiErrorCode;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

/**
 * Renders every exception on an API route into the documented error envelope —
 * TODO-MODULE-SPECIFICATION.md §9.2.
 *
 * The reason this exists as a single renderer rather than per-controller
 * `try/catch` is that a client must be able to rely on the shape. Every failure
 * on `api/*` comes out as:
 *
 *     { "error": { "code": "<stable string>", "message": "...", "errors": {...} } }
 *
 * Three decisions worth stating:
 *
 * - **A 403 is not a 404 and vice versa, except where the record itself is the
 *   secret.** An authorization failure on a *known* route returns 403. Route
 *   model binding failures — an id that does not exist, or one the global scope
 *   hides — are 404, because confirming "that exists but is not yours" is itself
 *   a disclosure. A wrong verb is a 405, because "try GET" and "this does not
 *   exist" are different corrections for a client.
 * - **Validation and state-machine rejections share `validation_failed`.** The
 *   service throws `ValidationException` for an illegal status transition, and a
 *   client handles that the same way it handles a bad field: read `errors`.
 * - **A 500 body says nothing about the failure.** Stack traces, SQL and model
 *   internals are logged, not returned. `APP_DEBUG` does not change this, because
 *   an API client is not a developer of this application.
 */
class ApiExceptionRenderer
{
    public function render(Request $request, Throwable $e): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        return $this->toResponse($e);
    }

    public function toResponse(Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException => $this->validation($e),
            $e instanceof AuthenticationException => $this->error(ApiErrorCode::Unauthenticated),
            $e instanceof AuthorizationException => $this->error(ApiErrorCode::Forbidden),
            $e instanceof ModelNotFoundException => $this->error(ApiErrorCode::NotFound),
            $e instanceof NotFoundHttpException => $this->error(ApiErrorCode::NotFound),
            $e instanceof MethodNotAllowedHttpException => $this->error(ApiErrorCode::MethodNotAllowed),
            $e instanceof TooManyRequestsHttpException => $this->error(ApiErrorCode::RateLimited),
            $e instanceof HttpExceptionInterface => $this->httpException($e),
            default => $this->error(ApiErrorCode::ServerError),
        };
    }

    protected function validation(ValidationException $e): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => ApiErrorCode::ValidationFailed,
                'message' => ApiErrorCode::defaultMessage(ApiErrorCode::ValidationFailed),
                // The per-field map is the whole point of a 422: the client needs
                // to know WHICH field, not merely that something was wrong.
                'errors' => $e->errors(),
            ],
        ], ApiErrorCode::status(ApiErrorCode::ValidationFailed));
    }

    /**
     * A deliberate HTTP exception (a `abort(403)`, an unsupported method) maps to
     * the closest documented code rather than to a bare 500 — a 405 is a client
     * mistake worth reporting accurately.
     */
    protected function httpException(HttpExceptionInterface $e): JsonResponse
    {
        $code = match ($e->getStatusCode()) {
            401 => ApiErrorCode::Unauthenticated,
            403 => ApiErrorCode::Forbidden,
            404 => ApiErrorCode::NotFound,
            405 => ApiErrorCode::MethodNotAllowed,
            422 => ApiErrorCode::ValidationFailed,
            429 => ApiErrorCode::RateLimited,
            default => ApiErrorCode::ServerError,
        };

        $message = $code === ApiErrorCode::ServerError
            ? ApiErrorCode::defaultMessage(ApiErrorCode::ServerError)
            : ($e->getMessage() !== '' ? $e->getMessage() : ApiErrorCode::defaultMessage($code));

        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], $e->getStatusCode());
    }

    protected function error(string $code): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => ApiErrorCode::defaultMessage($code),
            ],
        ], ApiErrorCode::status($code));
    }
}
