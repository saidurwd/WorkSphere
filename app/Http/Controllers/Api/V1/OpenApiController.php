<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\OpenApiGenerator;
use Illuminate\Http\JsonResponse;

/**
 * `GET /api/v1/openapi` — the specification, generated.
 *
 * Served from the generator rather than read from a committed file, which is the
 * whole point: a checked-in document drifts, a generated one cannot. It sits
 * behind `auth:sanctum` like every other API route, because it enumerates every
 * route and every filter the API accepts — a map of the application handed to
 * anonymous callers is not something to publish for convenience.
 *
 * JSON only. `php artisan api:openapi` writes the same document to disk for
 * clients who want a file, which keeps a YAML dependency out of the request path
 * for something almost nobody asks for over HTTP.
 */
class OpenApiController extends Controller
{
    public function __construct(private readonly OpenApiGenerator $generator) {}

    public function show(): JsonResponse
    {
        return response()->json($this->generator->generate());
    }
}
