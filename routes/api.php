<?php

use App\Http\Controllers\Api\V1\ApiMetaController;
use App\Http\Controllers\Api\V1\OpenApiController;
use App\Http\Controllers\Api\V1\PersonalAccessTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes
|--------------------------------------------------------------------------
|
| Token-authenticated. Sanctum is the guard; Phase 11 gave tokens an expiry and
| emptied `sanctum.guard`, so a leaked credential dies on its own and a session
| cookie cannot authenticate an API route.
|
| The version lives in the URI, not in a header. `/api/v1/...` can gain `/api/v2`
| alongside it without breaking a single existing integration, and a client that
| hard-codes the prefix keeps working through the upgrade. Module endpoints are
| declared in each module's `routes/api.php` and loaded by that module's
| RouteServiceProvider under the same `v1` prefix — this file owns only what
| belongs to no single module.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request) => $request->user())->name('api.user');

    // Let a token owner see and revoke their own tokens. Revoking the current
    // token is the "sign this device out" action, and without it a token that
    // should have been dropped lives forever.
    Route::get('/tokens', [PersonalAccessTokenController::class, 'index'])
        ->middleware('throttle:api')
        ->name('api.tokens.index');

    Route::delete('/tokens/{tokenId}', [PersonalAccessTokenController::class, 'destroy'])
        ->middleware('throttle:api')
        ->name('api.tokens.destroy');
});

/*
|--------------------------------------------------------------------------
| v1
|--------------------------------------------------------------------------
|
| Every route below is `auth:sanctum` plus one of the two named limiters. Reads
| get `throttle:api`; anything that mutates also gets `throttle:api-writes`,
| because a write is both the expensive and the abusable one.
|
*/

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['auth:sanctum', 'throttle:api'])
    ->group(function (): void {
        Route::get('meta', [ApiMetaController::class, 'index'])
            ->name('meta');

        Route::get('openapi', [OpenApiController::class, 'show'])
            ->name('openapi');

        Route::post('tokens', [PersonalAccessTokenController::class, 'store'])
            ->middleware('throttle:api-writes')
            ->name('tokens.store');

        // Listing and revoking already existed at the unversioned `/api/tokens`
        // from Phase 11 and are unchanged. These are aliases onto the same action,
        // so a client living entirely inside `/api/v1` does not have to know that
        // half the token surface predates the version prefix.
        Route::get('tokens', [PersonalAccessTokenController::class, 'index'])
            ->name('tokens.index');

        Route::delete('tokens/{tokenId}', [PersonalAccessTokenController::class, 'destroy'])
            ->middleware('throttle:api-writes')
            ->name('tokens.destroy');
    });
