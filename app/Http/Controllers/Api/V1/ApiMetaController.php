<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CalendarView;
use App\Enums\LinkType;
use App\Enums\NotificationType;
use App\Enums\Priority;
use App\Enums\RecurrenceFrequency;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Http\ApiErrorCode;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * `GET /api/v1/meta` — the vocabularies, published as data.
 *
 * A client that has to hard-code `in_progress` or `waiting_on` has to be updated
 * every time the vocabulary changes, and the failure mode is silent: the filter
 * returns nothing and the integration reports "no results" instead of "your enum
 * is stale". Publishing the cases makes that failure loud at integration time
 * rather than quiet in production.
 *
 * This is also where the token's own abilities are reported, because a client
 * needs to know what it may attempt before it attempts it.
 */
class ApiMetaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => [
                'version' => 'v1',
                'user' => [
                    'id' => (int) $request->user()->id,
                    'name' => (string) $request->user()->name,
                    'abilities' => $this->abilities($request),
                ],
                'enums' => [
                    'work_item_status' => $this->cases(WorkItemStatus::class),
                    'priority' => $this->cases(Priority::class),
                    'visibility' => $this->cases(Visibility::class),
                    'recurrence_frequency' => $this->cases(RecurrenceFrequency::class),
                    'link_type' => $this->cases(LinkType::class),
                    'calendar_view' => $this->cases(CalendarView::class),
                    'notification_type' => $this->cases(NotificationType::class),
                ],
                'error_codes' => ApiErrorCode::all(),
            ],
        ]);
    }

    /**
     * A token's abilities, or `['*']` for a full-access token.
     *
     * `TransientToken` — the marker a session-authenticated request carries — has
     * no ability list, and reporting `[]` for it would tell a client it can do
     * nothing when in fact it can do everything its session allows.
     *
     * @return list<string>
     */
    protected function abilities(Request $request): array
    {
        $token = $request->user()->currentAccessToken();

        if ($token === null) {
            return ['*'];
        }

        return method_exists($token, 'abilities') && $token->abilities !== null
            ? array_values($token->abilities)
            : ['*'];
    }

    /**
     * Every case of a backed enum as `{value, label}`.
     *
     * @param  class-string<\BackedEnum>  $enum
     * @return list<array{value: string, label: string}>
     */
    protected function cases(string $enum): array
    {
        return array_map(
            fn (\BackedEnum $case): array => [
                'value' => (string) $case->value,
                'label' => method_exists($case, 'label') ? (string) $case->label() : (string) $case->name,
            ],
            $enum::cases(),
        );
    }
}
