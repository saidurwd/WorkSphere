<?php

namespace Tests\Feature;

use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Http\ApiErrorCode;
use App\Models\User;
use Database\Factories\DepartmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Todos\Models\Todo;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * `/api/v1/todos` — §9.1.
 *
 * The load-bearing cases are the authorization ones. A policy that is enforced on
 * the web and bypassed here is an IDOR with a JSON body, so every web-visible
 * restriction has a matching assertion: a non-assignee is absent from the list AND
 * 403s on the record, and a cross-department Team To-Do is invisible in both.
 */
class ApiTodoTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    // ---- Authentication -----------------------------------------------------

    public function test_every_todo_endpoint_requires_a_token(): void
    {
        $todo = Todo::factory()->create();

        $this->getJson('/api/v1/todos')->assertUnauthorized();
        $this->postJson('/api/v1/todos', ['title' => 'x'])->assertUnauthorized();
        $this->getJson("/api/v1/todos/{$todo->id}")->assertUnauthorized();
        $this->patchJson("/api/v1/todos/{$todo->id}", ['title' => 'x'])->assertUnauthorized();
        $this->deleteJson("/api/v1/todos/{$todo->id}")->assertUnauthorized();
        $this->postJson("/api/v1/todos/{$todo->id}/complete")->assertUnauthorized();
        $this->getJson("/api/v1/todos/{$todo->id}/comments")->assertUnauthorized();
    }

    public function test_an_unauthenticated_failure_carries_the_stable_code(): void
    {
        $this->getJson('/api/v1/todos')
            ->assertUnauthorized()
            ->assertJsonPath('error.code', ApiErrorCode::Unauthenticated);
    }

    // ---- Read ---------------------------------------------------------------

    public function test_it_lists_the_callers_todos(): void
    {
        $user = $this->plainUser();

        Todo::factory()->assignedTo($user)->create(['title' => 'Mine']);
        Todo::factory()->create(['title' => 'Somebody elses']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/todos')->assertOk();

        $this->assertSame(['Mine'], array_column($response->json('data'), 'title'));
    }

    public function test_the_collection_carries_the_documented_meta_block(): void
    {
        Sanctum::actingAs($this->plainUser());

        $meta = $this->getJson('/api/v1/todos')->assertOk()->json('meta');

        // A client paginating needs these four and cannot compute them from a count.
        foreach (['current_page', 'last_page', 'per_page', 'total'] as $key) {
            $this->assertArrayHasKey($key, $meta);
        }
    }

    public function test_a_non_assignee_cannot_open_a_todo(): void
    {
        $todo = Todo::factory()->create();

        Sanctum::actingAs($this->plainUser());

        // `TodoScope` narrows route-model-binding, so a record the caller cannot
        // see does not resolve at all. That is a 404 rather than a 403 on
        // purpose: a 403 would confirm the To-Do exists.
        $this->getJson("/api/v1/todos/{$todo->id}")
            ->assertNotFound()
            ->assertJsonPath('error.code', ApiErrorCode::NotFound);
    }

    public function test_a_record_the_scope_shows_but_the_policy_denies_is_a_403(): void
    {
        // `view_all` lets the caller SEE a To-Do they hold no update rights on. The
        // list shows it, so a 404 on open would be a lie; the policy denial has to
        // surface as a 403 the client can distinguish from "no such record".
        $user = $this->userWithPermissions(['todos.view_all']);

        $todo = Todo::factory()->createdBy($this->plainUser())->create();

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/todos/{$todo->id}")->assertOk();

        $this->patchJson("/api/v1/todos/{$todo->id}", [
            'title' => 'Hijacked',
            'priority' => 'high',
            'visibility' => 'personal',
        ])->assertForbidden()
            ->assertJsonPath('error.code', ApiErrorCode::Forbidden);
    }

    public function test_a_cross_department_team_todo_is_invisible_in_the_list(): void
    {
        $mine = DepartmentFactory::new()->create();
        $theirs = DepartmentFactory::new()->create();

        $outsider = $this->userInDepartment($theirs->id);

        Todo::factory()->withVisibility(Visibility::Team)->create([
            'department_id' => $mine->id,
        ]);

        Sanctum::actingAs($outsider);

        $this->getJson('/api/v1/todos')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_view_all_sees_everything(): void
    {
        Todo::factory()->count(3)->create();

        Sanctum::actingAs($this->userWithPermissions(['todos.view_all']));

        $this->getJson('/api/v1/todos')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_it_does_not_expose_the_recurrence_rule_internals(): void
    {
        $user = $this->plainUser();

        $todo = Todo::factory()->assignedTo($user)->create([
            'recurrence_rule' => ['frequency' => 'weekly', 'by_weekday' => [1, 3]],
        ]);

        Sanctum::actingAs($user);

        $body = $this->getJson("/api/v1/todos/{$todo->id}")->assertOk()->json('data');

        // `is_recurring` answers "is there more coming?" without pinning the client
        // to the storage shape, which is what a renamed recurrence key would break.
        $this->assertTrue($body['is_recurring']);
        $this->assertArrayNotHasKey('recurrence_rule', $body);
    }

    public function test_it_does_not_expose_soft_delete_state(): void
    {
        $user = $this->plainUser();

        $todo = Todo::factory()->assignedTo($user)->create();
        $todo->delete();

        Sanctum::actingAs($user);

        // The record is gone; the tombstone is not the caller's business.
        $this->getJson("/api/v1/todos/{$todo->id}")->assertNotFound();
    }

    public function test_a_missing_todo_is_a_404_not_a_403(): void
    {
        Sanctum::actingAs($this->plainUser());

        $this->getJson('/api/v1/todos/999999')
            ->assertNotFound()
            ->assertJsonPath('error.code', ApiErrorCode::NotFound);
    }

    // ---- Filtering and sorting ---------------------------------------------

    public function test_it_filters_by_status(): void
    {
        $user = $this->plainUser();

        Todo::factory()->assignedTo($user)->inStatus(WorkItemStatus::Completed)->create(['title' => 'Done']);
        Todo::factory()->assignedTo($user)->inStatus(WorkItemStatus::Pending)->create(['title' => 'Open']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/todos?status=completed')->assertOk();

        $this->assertSame(['Done'], array_column($response->json('data'), 'title'));
    }

    public function test_an_invalid_status_filter_is_a_422(): void
    {
        Sanctum::actingAs($this->plainUser());

        $this->getJson('/api/v1/todos?status=nonsense')
            ->assertStatus(422)
            ->assertJsonPath('error.code', ApiErrorCode::ValidationFailed);
    }

    public function test_an_unknown_sort_column_is_rejected_not_ignored(): void
    {
        Sanctum::actingAs($this->plainUser());

        // Silently defaulting would return a confidently-ordered list that is not
        // the order the client asked for, and the client would never find out.
        $this->getJson('/api/v1/todos?sort=title; DROP TABLE todos')
            ->assertStatus(422)
            ->assertJsonPath('error.code', ApiErrorCode::ValidationFailed);
    }

    public function test_an_oversized_page_is_rejected(): void
    {
        Sanctum::actingAs($this->plainUser());

        $this->getJson('/api/v1/todos?per_page=100000')->assertStatus(422);
    }

    public function test_it_sorts_by_a_whitelisted_column(): void
    {
        $user = $this->plainUser();

        Todo::factory()->assignedTo($user)->create(['title' => 'Zebra']);
        Todo::factory()->assignedTo($user)->create(['title' => 'Apple']);

        Sanctum::actingAs($user);

        $titles = array_column(
            $this->getJson('/api/v1/todos?sort=title&order=asc')->assertOk()->json('data'),
            'title',
        );

        $this->assertSame(['Apple', 'Zebra'], $titles);
    }

    // ---- Write --------------------------------------------------------------

    public function test_it_creates_a_todo(): void
    {
        $user = $this->userWithPermissions(['todos.create']);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/todos', [
            'title' => 'From the API',
            'priority' => 'high',
        ])->assertCreated();

        $this->assertSame('From the API', $response->json('data.title'));
        $this->assertDatabaseHas('todos', ['title' => 'From the API']);
    }

    public function test_creating_for_somebody_else_requires_create_for_others(): void
    {
        $other = $this->plainUser();

        // `todos.create` alone is not enough: assigning to somebody else is a
        // distinct permission (§4), and the API must not be the way around it.
        Sanctum::actingAs($this->userWithPermissions(['todos.create']));

        $this->postJson('/api/v1/todos', ['title' => 'Delegated', 'assignee_id' => $other->id])
            ->assertForbidden();

        Sanctum::actingAs($this->userWithPermissions(['todos.create', 'todos.create_for_others']));

        $this->postJson('/api/v1/todos', ['title' => 'Delegated', 'assignee_id' => $other->id])
            ->assertCreated();
    }

    public function test_creation_validates_against_the_enum(): void
    {
        Sanctum::actingAs($this->userWithPermissions(['todos.create']));

        $this->postJson('/api/v1/todos', ['title' => 'x', 'priority' => 'urgent-ish'])
            ->assertStatus(422)
            ->assertJsonPath('error.code', ApiErrorCode::ValidationFailed)
            ->assertJsonStructure(['error' => ['code', 'message', 'errors' => ['priority']]]);
    }

    public function test_it_updates_a_todo(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create(['title' => 'Before']);

        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/todos/{$todo->id}", [
            'title' => 'After',
            'priority' => 'high',
            'visibility' => 'personal',
        ])->assertOk();

        $this->assertSame('After', $response->json('data.title'));
    }

    public function test_update_rejects_a_status_field(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create(['title' => 'Before']);

        Sanctum::actingAs($user);

        // A status change is a transition through the state machine, not a field
        // write. Silently dropping the field would tell the client the To-Do moved
        // when it did not, so `prohibited` names it in a 422 instead.
        $this->patchJson("/api/v1/todos/{$todo->id}", [
            'title' => 'Renamed',
            'priority' => 'low',
            'visibility' => 'personal',
            'status' => 'completed',
        ])
            ->assertStatus(422)
            ->assertJsonPath('error.code', ApiErrorCode::ValidationFailed)
            ->assertJsonStructure(['error' => ['errors' => ['status']]]);

        $this->assertSame(
            'Before',
            $todo->refresh()->title,
            'A rejected update must not apply the fields that did validate.',
        );
    }

    public function test_it_runs_the_lifecycle_transitions(): void
    {
        // `todos.restore` is a separate permission from `update` (the policy
        // requires it), so an actor exercising the whole lifecycle needs both.
        $user = $this->userWithPermissions(['todos.restore']);

        // InProgress, because `Inbox -> Completed` is not in the transition graph
        // (§3.2) and the 422 that follows would be correct but would prove nothing
        // about the happy path.
        $todo = Todo::factory()
            ->createdBy($user)
            ->inStatus(WorkItemStatus::InProgress)
            ->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/todos/{$todo->id}/complete")->assertOk();

        $this->assertSame(
            WorkItemStatus::Completed,
            $todo->refresh()->status,
        );

        $this->postJson("/api/v1/todos/{$todo->id}/reopen")->assertOk();
        $this->assertNotNull($todo->refresh()->status);

        $this->postJson("/api/v1/todos/{$todo->id}/archive")->assertOk();
        $this->postJson("/api/v1/todos/{$todo->id}/restore")->assertOk();
    }

    public function test_an_illegal_transition_is_a_422_not_a_silent_no_op(): void
    {
        $user = $this->plainUser();

        // `Waiting -> Completed` is not in the transition graph (§3.2). Silently
        // succeeding would tell the client the state changed when it did not.
        $todo = Todo::factory()->createdBy($user)->inStatus(WorkItemStatus::Waiting)->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/todos/{$todo->id}/complete")
            ->assertStatus(422)
            ->assertJsonPath('error.code', ApiErrorCode::ValidationFailed);
    }

    public function test_it_assigns_and_unassigns(): void
    {
        $user = $this->userWithPermissions(['todos.create', 'todos.update', 'todos.assign']);
        $other = $this->plainUser();

        $todo = Todo::factory()->createdBy($user)->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/todos/{$todo->id}/assign", ['assignee_id' => $other->id])
            ->assertOk();

        $this->assertSame($other->id, $todo->refresh()->assignee_id);

        // A null assignee returns the To-Do to the inbox, which is a real operation.
        $this->postJson("/api/v1/todos/{$todo->id}/assign", ['assignee_id' => null])->assertOk();

        $this->assertNull($todo->refresh()->assignee_id);
    }

    public function test_it_deletes_a_todo(): void
    {
        $user = $this->userWithPermissions(['todos.delete']);
        $todo = Todo::factory()->createdBy($user)->create();

        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/todos/{$todo->id}")->assertNoContent();

        $this->assertSoftDeleted('todos', ['id' => $todo->id]);
    }

    public function test_every_mutation_writes_an_activity_row(): void
    {
        $user = $this->userWithPermissions(['todos.create', 'todos.delete']);
        $todo = Todo::factory()->createdBy($user)->create();

        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/todos/{$todo->id}")->assertNoContent();

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Todo::class,
            'subject_id' => $todo->id,
            'action' => 'deleted',
        ]);
    }

    // ---- Comments -----------------------------------------------------------

    public function test_it_comments_on_a_todo(): void
    {
        $user = $this->userWithPermissions(['todos.comment']);
        $todo = Todo::factory()->createdBy($user)->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/todos/{$todo->id}/comments", ['body' => 'Agreed.'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Agreed.');

        $this->assertDatabaseHas('comments', [
            'commentable_type' => Todo::class,
            'commentable_id' => $todo->id,
            'body' => 'Agreed.',
        ]);
    }

    public function test_commenting_on_somebody_elses_todo_is_refused(): void
    {
        $todo = Todo::factory()->create();

        Sanctum::actingAs($this->userWithPermissions(['todos.comment']));

        // The scope refuses to resolve the record, so this is a 404 rather than a
        // 403 — confirming the To-Do exists would itself be the leak.
        $this->postJson("/api/v1/todos/{$todo->id}/comments", ['body' => 'Hello'])
            ->assertNotFound();
    }

    public function test_it_returns_a_comment_thread(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        $parent = $todo->comments()->create(['user_id' => $user->id, 'body' => 'Root']);
        $todo->comments()->create(['user_id' => $user->id, 'parent_id' => $parent->id, 'body' => 'Reply']);

        Sanctum::actingAs($user);

        $data = $this->getJson("/api/v1/todos/{$todo->id}/comments")->assertOk()->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('Root', $data[0]['body']);
        $this->assertCount(1, $data[0]['replies']);
        $this->assertSame('Reply', $data[0]['replies'][0]['body']);
    }

    // ---- Activity -----------------------------------------------------------

    public function test_it_returns_the_activity_trail(): void
    {
        $user = $this->userWithPermissions(['todos.create', 'todos.delete']);
        $todo = Todo::factory()->createdBy($user)->create();

        Sanctum::actingAs($user);

        // Delete LAST: a soft-deleted To-Do no longer resolves through the scope,
        // so asking for its activity afterwards would be a 404 and prove nothing.
        $this->getJson("/api/v1/todos/{$todo->id}/activity")->assertOk()->assertJsonStructure(['data']);

        $this->deleteJson("/api/v1/todos/{$todo->id}")->assertNoContent();

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Todo::class,
            'subject_id' => $todo->id,
            'action' => 'deleted',
        ]);
    }

    // ---- A blocked account --------------------------------------------------

    public function test_a_deactivated_account_cannot_use_its_token(): void
    {
        $user = User::factory()->create(['status' => 'inactive']);

        Todo::factory()->assignedTo($user)->create();

        Sanctum::actingAs($user);

        // A bearer token outlives any account-status change; without enforcement a
        // disabled account keeps full programmatic access until its token expires.
        $this->getJson('/api/v1/todos')->assertForbidden();
    }
}
