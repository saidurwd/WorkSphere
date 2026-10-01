<?php

namespace Tests\Feature;

use App\Enums\CalendarView;
use App\Enums\LinkType;
use App\Enums\NotificationType;
use App\Enums\Priority;
use App\Enums\RecurrenceFrequency;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Http\ApiErrorCode;
use App\Http\OpenApiGenerator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * `GET /api/v1/meta` and `GET /api/v1/openapi`.
 *
 * The point of both is anti-drift. `meta` exists so a client reads its vocabulary
 * instead of hard-coding it; `openapi` is generated from the route table so it
 * cannot describe an endpoint that does not exist. Both are tested against the
 * same source of truth the endpoint reads, which is the only way either claim
 * stays true.
 */
class ApiMetaTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_the_vocabulary_endpoint_requires_a_token(): void
    {
        $this->getJson('/api/v1/meta')->assertUnauthorized();
    }

    public function test_it_publishes_every_enum_the_api_accepts(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $response = $this->getJson('/api/v1/meta')->assertOk();

        $enums = $response->json('data.enums');

        // A filter validated against an enum is only as good as the enum the client
        // was told about. If `waiting` exists as a status but not in `meta`, a
        // client filtering on it is working from a guess.
        $this->assertContains('waiting', array_column($enums['work_item_status'], 'value'));
        $this->assertContains('team', array_column($enums['visibility'], 'value'));
        $this->assertContains('high', array_column($enums['priority'], 'value'));
        $this->assertContains('weekly', array_column($enums['recurrence_frequency'], 'value'));
        $this->assertContains('blocks', array_column($enums['link_type'], 'value'));
    }

    public function test_every_published_enum_case_is_a_real_case(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $enums = $this->getJson('/api/v1/meta')->assertOk()->json('data.enums');

        $expected = [
            'work_item_status' => WorkItemStatus::class,
            'priority' => Priority::class,
            'visibility' => Visibility::class,
            'recurrence_frequency' => RecurrenceFrequency::class,
            'link_type' => LinkType::class,
            'calendar_view' => CalendarView::class,
            'notification_type' => NotificationType::class,
        ];

        foreach ($expected as $key => $enum) {
            $this->assertSame(
                array_column($enum::cases(), 'value'),
                array_column($enums[$key], 'value'),
                "{$key} does not match {$enum}.",
            );
        }
    }

    public function test_it_publishes_the_stable_error_codes(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $codes = $this->getJson('/api/v1/meta')->assertOk()->json('data.error_codes');

        // The codes are the API's contract with clients for error handling, so the
        // published list must be the enforced list — not a hand-copied subset.
        $this->assertSame(ApiErrorCode::all(), $codes);
    }

    public function test_a_session_authenticated_caller_is_not_accepted(): void
    {
        // `sanctum.guard` is empty since Phase 11, so a browser session cookie must
        // not authenticate an API route. A session reaching the API would mean
        // revoking a token changes nothing while that session is alive.
        $this->actingAs($this->plainUser())->getJson('/api/v1/meta')->assertUnauthorized();
    }

    public function test_the_openapi_document_is_generated_from_the_live_routes(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $document = $this->getJson('/api/v1/openapi')->assertOk()->json();

        $this->assertSame('3.1.0', $document['openapi']);

        foreach (['todos', 'tasks', 'meetings', 'obligations', 'tokens'] as $resource) {
            $this->assertArrayHasKey(
                "api/v1/{$resource}",
                $document['paths'],
                "The document omits the {$resource} endpoint.",
            );
        }
    }

    public function test_every_v1_route_appears_in_the_document(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $document = $this->getJson('/api/v1/openapi')->assertOk()->json();

        foreach (app('router')->getRoutes() as $route) {
            if (! str_starts_with($route->uri(), 'api/v1/')) {
                continue;
            }

            $this->assertArrayHasKey(
                $route->uri(),
                $document['paths'],
                "{$route->uri()} exists as a route but not in the OpenAPI document.",
            );

            foreach (['GET', 'POST', 'PATCH', 'DELETE'] as $method) {
                if (in_array($method, $route->methods(), true)) {
                    $this->assertArrayHasKey(
                        strtolower($method),
                        $document['paths'][$route->uri()],
                        "{$route->uri()} {$method} is missing from the OpenAPI document.",
                    );
                }
            }
        }
    }

    public function test_the_document_describes_the_validation_the_api_actually_enforces(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $document = $this->getJson('/api/v1/openapi')->assertOk()->json();

        $body = $document['paths']['api/v1/todos']['post']['requestBody'];
        $properties = $body['content']['application/json']['schema']['properties'];

        // These come from StoreTodoRequest, not from a hand-written schema, so the
        // published contract and the enforced contract cannot diverge.
        $this->assertArrayHasKey('title', $properties);
        $this->assertArrayHasKey('priority', $properties);
        $this->assertSame(
            Priority::values(),
            $properties['priority']['enum'],
        );
    }

    public function test_the_document_publishes_a_schema_per_resource(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $schemas = $this->getJson('/api/v1/openapi')->assertOk()->json('components.schemas');

        foreach (['TodoResource', 'TaskResource', 'MeetingResource', 'ObligationResource', 'CommentResource'] as $resource) {
            $this->assertArrayHasKey($resource, $schemas);
        }

        $serialised = json_encode($schemas);

        // No `$ref` may dangle: a resource pointing at a component that was never
        // emitted is a document that documents nothing. The `~` delimiter is
        // deliberate — the pattern itself contains `#/components/…`.
        preg_match_all('~"\$ref"\s*:\s*"#/components/schemas/([A-Za-z]+)"~', $serialised, $matches);

        foreach ($matches[1] as $referenced) {
            $this->assertArrayHasKey($referenced, $schemas, "Dangling \$ref to {$referenced}.");
        }
    }

    public function test_the_openapi_document_requires_authentication(): void
    {
        // It enumerates every route and every filter the API accepts. A map of the
        // application is not something to hand to an anonymous caller.
        $this->getJson('/api/v1/openapi')->assertUnauthorized();
    }

    public function test_the_generated_file_matches_what_the_command_would_write(): void
    {
        // `--check` in CI is only meaningful if the committed copy is identical to
        // the generated one; this asserts the generator is deterministic.
        $first = json_encode(app(OpenApiGenerator::class)->generate());
        $second = json_encode(app(OpenApiGenerator::class)->generate());

        $this->assertSame($first, $second);
    }

    public function test_it_reports_the_caller_identity(): void
    {
        $user = $this->plainUser();

        Sanctum::actingAs($user);

        $data = $this->getJson('/api/v1/meta')->assertOk()->json('data.user');

        $this->assertSame($user->id, $data['id']);
        $this->assertNotEmpty($data['abilities']);
    }

    public function test_a_blocked_account_cannot_reach_the_api(): void
    {
        // A bearer token outlives any account-status change, so without this an
        // account disabled today would keep full programmatic access until its
        // token expired.
        $user = User::factory()->create(['status' => 'suspended']);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/meta')
            ->assertForbidden()
            ->assertJsonPath('error.code', ApiErrorCode::Forbidden);
    }
}
