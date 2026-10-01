<?php

namespace Tests\Feature;

use App\Http\ApiErrorCode;
use App\Http\Resources\ApiResource;
use App\Http\Resources\CommentResource;
use App\Http\Resources\MeetingResource;
use App\Http\Resources\ObligationResource;
use App\Http\Resources\PersonResource;
use App\Http\Resources\TagResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\TodoResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Meetings\Models\Meeting;
use Modules\Obligations\Models\Obligation;
use Modules\Tasks\Models\Task;
use Modules\Todos\Models\Todo;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The published contract has to be true.
 *
 * A generated OpenAPI document is only worth generating if it cannot quietly
 * disagree with the code. Three ways it could, each pinned here:
 *
 * - a resource returns a field its `schema()` never declared;
 * - a resource declares a field it never returns;
 * - the document describes an error code the application cannot produce, or omits
 *   one it can.
 *
 * The first two are asserted against real records rather than by reading the
 * source, because a resource that returns a field conditionally would otherwise
 * pass on an instance chosen to hide it.
 */
class ApiSchemaTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /**
     * @return list<class-string<ApiResource>>
     */
    private function resources(): array
    {
        return [
            TodoResource::class,
            TaskResource::class,
            MeetingResource::class,
            ObligationResource::class,
            CommentResource::class,
            PersonResource::class,
            TagResource::class,
        ];
    }

    public function test_a_resource_returns_only_fields_its_schema_declares(): void
    {
        $viewer = $this->userWithPermissions([
            'todos.view_all', 'task.view', 'meeting.view', 'obligation.view',
        ]);

        Sanctum::actingAs($viewer);

        $todo = Todo::factory()->create();
        $task = Task::factory()->create();
        $meeting = Meeting::factory()->create();
        $obligation = Obligation::factory()->create();
        $comment = $todo->comments()->create([
            'user_id' => $viewer->id,
            'body' => 'A comment',
        ]);

        $responses = [
            TodoResource::class => $this->getJson("/api/v1/todos/{$todo->id}")->json('data'),
            TaskResource::class => $this->getJson("/api/v1/tasks/{$task->id}")->json('data'),
            MeetingResource::class => $this->getJson("/api/v1/meetings/{$meeting->id}")->json('data'),
            ObligationResource::class => $this->getJson("/api/v1/obligations/{$obligation->id}")->json('data'),
            CommentResource::class => $this->getJson("/api/v1/todos/{$todo->id}/comments")->json('data.0'),
        ];

        foreach ($responses as $class => $payload) {
            $declared = array_keys($class::schema());

            $this->assertSame(
                [],
                array_diff(array_keys((array) $payload), $declared),
                "{$class} returns fields its schema() does not declare.",
            );
        }

        // CommentResource is nested rather than top-level, so it is asserted
        // directly rather than through an endpoint.
        $this->assertSame([], array_diff(
            array_keys((new CommentResource($comment))->resolve(request())),
            array_keys(CommentResource::schema()),
        ));
    }

    public function test_a_resource_declares_no_field_it_never_returns(): void
    {
        $viewer = $this->userWithPermissions([
            'todos.view_all', 'task.view', 'meeting.view', 'obligation.view',
        ]);

        Sanctum::actingAs($viewer);

        $todo = Todo::factory()->create();
        $task = Task::factory()->create();
        $meeting = Meeting::factory()->create();
        $obligation = Obligation::factory()->create();

        $responses = [
            TodoResource::class => $this->getJson("/api/v1/todos/{$todo->id}")->json('data'),
            TaskResource::class => $this->getJson("/api/v1/tasks/{$task->id}")->json('data'),
            MeetingResource::class => $this->getJson("/api/v1/meetings/{$meeting->id}")->json('data'),
            ObligationResource::class => $this->getJson("/api/v1/obligations/{$obligation->id}")->json('data'),
        ];

        foreach ($responses as $class => $payload) {
            $this->assertSame(
                [],
                array_diff(array_keys($class::schema()), array_keys((array) $payload)),
                "{$class} declares fields it never returns.",
            );
        }
    }

    public function test_every_resource_declares_a_schema(): void
    {
        foreach ($this->resources() as $class) {
            $this->assertNotEmpty(
                $class::schema(),
                "{$class} declares no schema, so the OpenAPI document cannot describe it.",
            );
        }
    }

    public function test_the_document_publishes_a_component_per_resource(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $schemas = $this->getJson('/api/v1/openapi')->assertOk()->json('components.schemas');

        foreach ($this->resources() as $class) {
            $this->assertArrayHasKey(class_basename($class), $schemas);
        }
    }

    public function test_every_error_code_the_api_can_produce_is_documented(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $schemas = $this->getJson('/api/v1/openapi')->assertOk()->json('components.schemas');

        $documented = $schemas['Error']['properties']['error']['properties']['code']['enum'];

        // If the application can emit a code the document does not list, a client
        // written against the document has no branch for it.
        $this->assertSame(ApiErrorCode::all(), $documented);
    }

    public function test_a_validation_failure_returns_the_documented_error_shape(): void
    {
        Sanctum::actingAs($this->userWithPermissions(['todos.create']));

        $this->postJson('/api/v1/todos', [])
            ->assertStatus(422)
            ->assertJsonStructure(['error' => ['code', 'message', 'errors']]);
    }

    public function test_a_server_error_body_leaks_nothing(): void
    {
        Sanctum::actingAs($this->userWithPermissions(['task.view']));

        // A deliberately broken request: the point is what the BODY contains, not
        // the status. Stack traces, SQL and model names must not reach a client.
        $response = $this->getJson('/api/v1/tasks?per_page=abc')->assertStatus(422);

        $body = $response->getContent();

        foreach (['vendor/laravel', 'vendor/', 'SQLSTATE', '.env', 'Illuminate\\'] as $leak) {
            $this->assertStringNotContainsString($leak, $body);
        }
    }

    public function test_the_generated_document_file_is_current(): void
    {
        // `php artisan api:openapi --check` in CI is only meaningful if the
        // committed copy is what the code produces today.
        $this->artisan('api:openapi --check')->assertExitCode(0);
    }
}
