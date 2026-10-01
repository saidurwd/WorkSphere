<?php

namespace Tests\Feature;

use App\Http\ApiErrorCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Modules\Meetings\Models\Meeting;
use Modules\Obligations\Models\Obligation;
use Modules\Tasks\Models\Task;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * `/api/v1/tasks`, `/api/v1/meetings` and `/api/v1/obligations` — read only.
 *
 * Three things are asserted for each module, and the third is the one that matters:
 *
 * 1. The endpoint works.
 * 2. A caller without the module's view permission does not see other people's rows.
 * 3. A caller who cannot see a row **cannot open it** — the same answer the web
 *    gives. A list endpoint that filters correctly but whose `show` does not is the
 *    classic half-finished IDOR fix, and it is exactly what these tests pin down.
 */
class ApiReadEndpointTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    // ---- Tasks --------------------------------------------------------------

    public function test_tasks_require_a_token(): void
    {
        $this->getJson('/api/v1/tasks')->assertUnauthorized();
        $this->getJson('/api/v1/tasks/1')->assertUnauthorized();
    }

    public function test_a_task_list_is_scoped_to_the_caller(): void
    {
        $mine = $this->plainUser();

        Task::factory()->create(['title' => 'Mine', 'user_id' => $mine->id]);
        Task::factory()->create(['title' => 'Theirs']);

        Sanctum::actingAs($mine);

        $this->getJson('/api/v1/tasks')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Mine')
            ->assertJsonCount(1, 'data');
    }

    public function test_task_view_permission_widens_the_list(): void
    {
        $user = $this->userWithPermissions(['task.view']);

        Task::factory()->count(3)->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/tasks')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_a_task_the_caller_cannot_see_is_a_403(): void
    {
        $task = Task::factory()->create();

        Sanctum::actingAs($this->plainUser());

        // Tasks have no global scope, so route-model-binding resolves the row and
        // `TaskPolicy::view` refuses: a 403 on a record the caller demonstrably
        // could not list. Same answer as the web controller, which is the point.
        $this->getJson("/api/v1/tasks/{$task->id}")
            ->assertForbidden()
            ->assertJsonPath('error.code', ApiErrorCode::Forbidden);
    }

    public function test_task_writes_are_not_exposed(): void
    {
        Sanctum::actingAs($this->userWithPermissions(['task.create', 'task.update', 'task.delete']));

        // Deliberately absent in v1: the write path carries transfer, sub-task and
        // time-entry side effects, and an API that reimplemented them would be a
        // second set of rules. Exposing the verb would be worse than not routing it.
        // 405 rather than 404: the `/tasks` path exists and accepts GET. The
        // distinction matters to a client deciding whether the verb is wrong or the
        // endpoint is missing.
        $this->postJson('/api/v1/tasks', ['title' => 'x'])->assertStatus(405);
        $this->patchJson('/api/v1/tasks/1', ['title' => 'x'])->assertStatus(405);
        $this->deleteJson('/api/v1/tasks/1')->assertStatus(405);
    }

    public function test_tasks_filter_and_sort(): void
    {
        $user = $this->plainUser();

        Task::factory()->create(['title' => 'Beta', 'user_id' => $user->id, 'status' => 'pending']);
        Task::factory()->create(['title' => 'Alpha', 'user_id' => $user->id, 'status' => 'completed']);

        Sanctum::actingAs($user);

        $this->assertSame(
            ['Beta'],
            array_column($this->getJson('/api/v1/tasks?status=pending')->assertOk()->json('data'), 'title'),
        );

        $this->assertSame(
            ['Alpha', 'Beta'],
            array_column(
                $this->getJson('/api/v1/tasks?sort=title&order=asc')->assertOk()->json('data'),
                'title',
            ),
        );
    }

    public function test_an_unknown_task_sort_column_is_a_422(): void
    {
        Sanctum::actingAs($this->userWithPermissions(['task.view']));

        $this->getJson('/api/v1/tasks?sort=(select+1)')->assertStatus(422);
    }

    // ---- Meetings -----------------------------------------------------------

    public function test_a_meeting_list_is_scoped_to_the_caller(): void
    {
        $mine = $this->plainUser();

        $mineOrganised = Meeting::factory()->create(['title' => 'Mine', 'organizer_id' => $mine->id]);
        Meeting::factory()->create(['title' => 'Theirs']);

        Sanctum::actingAs($mine);

        $this->getJson('/api/v1/meetings')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Mine')
            ->assertJsonCount(1, 'data');
    }

    public function test_a_participant_sees_their_meeting(): void
    {
        $participant = $this->plainUser();

        $meeting = Meeting::factory()->create(['organizer_id' => $this->plainUser()->id]);

        $meeting->participants()->create(['user_id' => $participant->id]);

        Sanctum::actingAs($participant);

        $this->getJson("/api/v1/meetings/{$meeting->id}")->assertOk();
    }

    public function test_meeting_view_permission_widens_the_list(): void
    {
        Meeting::factory()->count(2)->create();

        Sanctum::actingAs($this->userWithPermissions(['meeting.view']));

        $this->getJson('/api/v1/meetings')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_a_meeting_the_caller_cannot_see_is_a_403(): void
    {
        $meeting = Meeting::factory()->create(['organizer_id' => $this->plainUser()->id]);

        Sanctum::actingAs($this->plainUser());

        $this->getJson("/api/v1/meetings/{$meeting->id}")
            ->assertForbidden()
            ->assertJsonPath('error.code', ApiErrorCode::Forbidden);
    }

    public function test_meetings_expose_both_location_forms(): void
    {
        $user = $this->userWithPermissions(['meeting.view']);

        Meeting::factory()->create(['location' => 'Room 3', 'location_id' => null]);

        Sanctum::actingAs($user);

        $data = $this->getJson('/api/v1/meetings')->assertOk()->json('data.0');

        // Both exist in the schema; rows created before `location_id` only have the
        // text, so the API must not present one as the replacement for the other.
        $this->assertSame('Room 3', $data['location']);
        $this->assertNull($data['location_id']);
    }

    public function test_an_invalid_meeting_status_filter_is_a_422(): void
    {
        Sanctum::actingAs($this->userWithPermissions(['meeting.view']));

        $this->getJson('/api/v1/meetings?status=pending')->assertStatus(422);
    }

    // ---- Obligations --------------------------------------------------------

    public function test_an_obligation_list_is_scoped_to_the_caller(): void
    {
        $mine = $this->plainUser();

        Obligation::factory()->create(['title' => 'Mine', 'owner_user_id' => $mine->id]);
        Obligation::factory()->create(['title' => 'Theirs']);

        Sanctum::actingAs($mine);

        $this->getJson('/api/v1/obligations')
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Mine')
            ->assertJsonCount(1, 'data');
    }

    public function test_obligation_view_permission_widens_the_list(): void
    {
        Obligation::factory()->count(3)->create();

        Sanctum::actingAs($this->userWithPermissions(['obligation.view']));

        $this->getJson('/api/v1/obligations')->assertOk()->assertJsonCount(3, 'data');
    }

    public function test_an_obligation_the_caller_cannot_see_is_a_403(): void
    {
        $obligation = Obligation::factory()->create(['owner_user_id' => $this->plainUser()->id]);

        Sanctum::actingAs($this->plainUser());

        $this->getJson("/api/v1/obligations/{$obligation->id}")
            ->assertForbidden()
            ->assertJsonPath('error.code', ApiErrorCode::Forbidden);
    }

    public function test_an_inactive_responsibility_does_not_grant_visibility(): void
    {
        // A closed-out responsibility row must stop granting access. Forgetting
        // the `active` flag here would hand an ex-responsible user read access
        // indefinitely.
        $former = $this->plainUser();

        $obligation = Obligation::factory()->create(['owner_user_id' => $this->plainUser()->id]);

        $obligation->responsibilities()->create([
            'user_id' => $former->id,
            'responsibility_type' => 'owner',
            'active' => false,
        ]);

        Sanctum::actingAs($former);

        $this->getJson("/api/v1/obligations/{$obligation->id}")->assertForbidden();
    }

    public function test_an_invalid_obligation_filter_is_a_422(): void
    {
        Sanctum::actingAs($this->userWithPermissions(['obligation.view']));

        $this->getJson('/api/v1/obligations?status=pending')->assertStatus(422);
    }

    // ---- Cross-cutting ------------------------------------------------------

    public function test_no_endpoint_leaks_a_record_the_web_hides(): void
    {
        $user = $this->plainUser();

        $task = Task::factory()->create();
        $meeting = Meeting::factory()->create();
        $obligation = Obligation::factory()->create();

        Sanctum::actingAs($user);

        // The acceptance criterion in one assertion: for a caller with no module
        // permission, every module's `show` refuses exactly what its `index` hides.
        $this->getJson("/api/v1/tasks/{$task->id}")->assertForbidden();
        $this->getJson("/api/v1/meetings/{$meeting->id}")->assertForbidden();
        $this->getJson("/api/v1/obligations/{$obligation->id}")->assertForbidden();

        $this->getJson('/api/v1/tasks')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/meetings')->assertJsonCount(0, 'data');
        $this->getJson('/api/v1/obligations')->assertJsonCount(0, 'data');
    }

    public function test_a_super_admin_sees_every_module(): void
    {
        Task::factory()->count(2)->create();
        Meeting::factory()->count(2)->create();
        Obligation::factory()->count(2)->create();

        // The module permissions are granted explicitly rather than relying on the
        // `super-admin` role alone. `Gate::before` bypasses the POLICY, but the
        // list scoping these controllers inherit from the web layer is a hand-
        // written permission check, not a policy call — a gap Phase 12 reports but
        // does not close, because closing it changes working web behaviour.
        Sanctum::actingAs($this->superAdmin([
            'task.view', 'meeting.view', 'obligation.view',
        ]));

        $this->getJson('/api/v1/tasks')->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/meetings')->assertJsonCount(2, 'data');
        $this->getJson('/api/v1/obligations')->assertJsonCount(2, 'data');
    }

    public function test_a_super_admin_bypasses_the_object_policy_without_the_list_permission(): void
    {
        // Pins the gap deliberately: the policy says yes, the list says no. It is
        // a pre-existing inconsistency in the web controllers, reproduced rather
        // than fixed here, so the behaviour is visible and cannot change silently.
        $task = Task::factory()->create();

        Sanctum::actingAs($this->superAdmin());

        $this->getJson("/api/v1/tasks/{$task->id}")->assertOk();
        $this->getJson('/api/v1/tasks')->assertJsonCount(0, 'data');
    }

    public function test_search_input_containing_sql_is_bound_not_interpolated(): void
    {
        $user = $this->userWithPermissions(['task.view']);

        Task::factory()->create(['title' => 'Legit']);

        Sanctum::actingAs($user);

        // `'; DROP TABLE tasks; --` must simply match nothing. A 500 here would
        // mean the term reached SQL as text.
        $this->getJson("/api/v1/tasks?q='; DROP TABLE tasks; --")->assertOk();

        $this->assertDatabaseCount('tasks', 1);
    }
}
