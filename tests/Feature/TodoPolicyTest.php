<?php

namespace Tests\Feature;

use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Models\User;
use Database\Factories\DepartmentFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Todos\Models\Scopes\TodoScope;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoWatcher;
use Modules\Todos\Policies\TodoPolicy;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * TodoPolicy (§4.1) and the TodoScope global scope that mirrors it.
 *
 * The parity cases at the bottom are the important ones. The existing three
 * modules scope their lists inside controllers and check nothing per record, so
 * a To-Do that is visible in the list but 403s on open — or worse, the reverse —
 * is the exact regression this pair must prevent.
 */
class TodoPolicyTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private TodoPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new TodoPolicy;
    }

    // ---- view ---------------------------------------------------------------

    public function test_the_assignee_can_view(): void
    {
        $assignee = $this->plainUser();
        $todo = Todo::factory()->assignedTo($assignee)->create();

        $this->assertTrue($this->policy->view($assignee, $todo));
    }

    public function test_the_creator_can_view(): void
    {
        $creator = $this->plainUser();
        $todo = Todo::factory()->createdBy($creator)->create();

        $this->assertTrue($this->policy->view($creator, $todo));
    }

    public function test_a_watcher_can_view(): void
    {
        $watcher = $this->plainUser();
        $todo = Todo::factory()->create();

        TodoWatcher::query()->create(['todo_id' => $todo->id, 'user_id' => $watcher->id]);

        $this->assertTrue($this->policy->view($watcher, $todo->fresh()));
    }

    public function test_an_unrelated_user_cannot_view(): void
    {
        $todo = Todo::factory()->create();

        $this->assertFalse($this->policy->view($this->plainUser(), $todo));
    }

    public function test_view_all_sees_everything(): void
    {
        $viewer = $this->userWithPermissions(['todos.view_all']);
        $todo = Todo::factory()->create();

        $this->assertTrue($this->policy->view($viewer, $todo));
    }

    public function test_a_team_todo_is_visible_within_its_department(): void
    {
        $department = DepartmentFactory::new()->create();
        $colleague = $this->userInDepartment($department->id);

        $todo = Todo::factory()->withVisibility(Visibility::Team)->create([
            'department_id' => $department->id,
        ]);

        $this->assertTrue($this->policy->view($colleague, $todo));
    }

    public function test_a_team_todo_is_invisible_outside_its_department(): void
    {
        $mine = DepartmentFactory::new()->create();
        $theirs = DepartmentFactory::new()->create();

        $outsider = $this->userInDepartment($theirs->id);

        $todo = Todo::factory()->withVisibility(Visibility::Team)->create([
            'department_id' => $mine->id,
        ]);

        $this->assertFalse(
            $this->policy->view($outsider, $todo),
            'A cross-department Team To-Do must not be visible.',
        );
    }

    public function test_a_team_todo_with_no_department_matches_nobody(): void
    {
        // Without a department it would otherwise be visible to every user whose
        // department is also null.
        $todo = Todo::factory()->withVisibility(Visibility::Team)->create([
            'department_id' => null,
        ]);

        $this->assertFalse($this->policy->view($this->userInDepartment(null), $todo));
    }

    // ---- update -------------------------------------------------------------

    public function test_the_creator_may_always_amend_without_any_permission(): void
    {
        $creator = $this->plainUser();
        $todo = Todo::factory()->createdBy($creator)->create();

        $this->assertTrue(
            $this->policy->update($creator, $todo),
            '§4.1: the creator always may amend.',
        );
    }

    public function test_update_own_does_not_permit_editing_someone_elses_todo(): void
    {
        $user = $this->userWithPermissions(['todos.update_own']);
        $todo = Todo::factory()->create();

        $this->assertFalse($this->policy->update($user, $todo));
    }

    public function test_update_own_permits_editing_a_todo_they_created(): void
    {
        $user = $this->userWithPermissions(['todos.update_own']);
        $todo = Todo::factory()->createdBy($user)->create();

        $this->assertTrue($this->policy->update($user, $todo));
    }

    public function test_update_any_permits_editing_a_visible_todo(): void
    {
        $user = $this->userWithPermissions(['todos.update_any', 'todos.view_all']);
        $todo = Todo::factory()->create();

        $this->assertTrue($this->policy->update($user, $todo));
    }

    public function test_update_any_still_requires_the_ability_to_see_it(): void
    {
        // update_any alone is not an override: §4.1 gates it behind `view`.
        $user = $this->userWithPermissions(['todos.update_any']);
        $todo = Todo::factory()->create();

        $this->assertFalse($this->policy->update($user, $todo));
    }

    // ---- delete / assign / restore ------------------------------------------

    public function test_delete_requires_both_the_permission_and_a_relationship(): void
    {
        $stranger = $this->plainUser();
        $todo = Todo::factory()->create();

        $this->assertFalse(
            $this->policy->delete($this->userWithPermissions(['todos.delete'], 'stranger'), $todo),
            'Holding todos.delete alone must not permit deleting anyone else\'s To-Do.',
        );

        $creator = $this->userWithPermissions(['todos.delete'], 'creator');
        $own = Todo::factory()->createdBy($creator)->create();

        $this->assertTrue($this->policy->delete($creator, $own));
    }

    public function test_creator_without_todos_delete_cannot_delete(): void
    {
        $creator = $this->plainUser();
        $todo = Todo::factory()->createdBy($creator)->create();

        $this->assertFalse(
            $this->policy->delete($creator, $todo),
            'Being the creator is not sufficient; the permission is also required.',
        );
    }

    public function test_update_any_grants_deletion(): void
    {
        $user = $this->userWithPermissions(['todos.delete', 'todos.update_any']);
        $todo = Todo::factory()->create();

        $this->assertTrue($this->policy->delete($user, $todo));
    }

    public function test_assign_is_open_to_the_creator_the_assignee_and_update_any(): void
    {
        $creator = $this->userWithPermissions(['todos.assign']);
        $assignee = $this->userWithPermissions(['todos.assign']);
        $todo = Todo::factory()->createdBy($creator)->assignedTo($assignee)->create();

        $this->assertTrue($this->policy->assign($creator, $todo));
        $this->assertTrue($this->policy->assign($assignee, $todo));

        $powerful = $this->userWithPermissions(['todos.assign', 'todos.update_any']);
        $this->assertTrue($this->policy->assign($powerful, $todo));

        // `todos.assign` alone is not enough: §4.1 also requires a relationship.
        $this->assertFalse($this->policy->assign($this->userWithPermissions(['todos.assign']), $todo));
    }

    public function test_restore_requires_the_permission(): void
    {
        $creator = $this->plainUser();
        $todo = Todo::factory()->createdBy($creator)->create();

        $this->assertFalse($this->policy->restore($creator, $todo));

        $permitted = $this->userWithPermissions(['todos.restore']);
        $own = Todo::factory()->createdBy($permitted)->create();

        $this->assertTrue($this->policy->restore($permitted, $own));
    }

    public function test_create_requires_the_permission(): void
    {
        $this->assertFalse($this->policy->create($this->plainUser()));
        $this->assertTrue($this->policy->create($this->userWithPermissions(['todos.create'])));
    }

    public function test_comment_requires_the_permission_and_visibility(): void
    {
        $todo = Todo::factory()->create();

        $this->assertFalse($this->policy->comment($this->userWithPermissions(['todos.comment']), $todo));

        $participant = $this->userWithPermissions(['todos.comment']);
        $own = Todo::factory()->createdBy($participant)->create();

        $this->assertTrue($this->policy->comment($participant, $own));
    }

    // ---- Scope parity --------------------------------------------------------

    public function test_the_scope_and_the_policy_agree_on_what_is_visible(): void
    {
        $department = DepartmentFactory::new()->create();
        $viewer = $this->userInDepartment($department->id);

        $mine = Todo::factory()->createdBy($viewer)->create(['title' => 'Mine']);
        $assigned = Todo::factory()->assignedTo($viewer)->create(['title' => 'Assigned to me']);
        $team = Todo::factory()->withVisibility(Visibility::Team)->create([
            'title' => 'Team to me',
            'department_id' => $department->id,
        ]);
        $theirs = Todo::factory()->create(['title' => 'Not mine']);

        // TodoScope reads auth()->user(), so it only applies once authenticated.
        $this->actingAs($viewer);

        foreach ([$mine, $assigned, $team, $theirs] as $candidate) {
            $todo = Todo::query()->withoutGlobalScope(TodoScope::class)->findOrFail($candidate->id);

            $this->assertSame(
                $this->policy->view($viewer, $todo),
                Todo::query()->whereKey($todo->id)->exists(),
                "Policy and scope disagree for To-Do #{$todo->id} ({$todo->title}).",
            );
        }
    }

    public function test_the_scope_hides_a_cross_department_team_todo_from_the_list(): void
    {
        $mine = DepartmentFactory::new()->create();
        $theirs = DepartmentFactory::new()->create();

        $outsider = $this->userInDepartment($theirs->id);

        $todo = Todo::factory()->withVisibility(Visibility::Team)->create([
            'title' => 'Secret team work',
            'department_id' => $mine->id,
        ]);

        $this->actingAs($outsider);

        $this->actingAs($outsider);

        $this->assertFalse($this->policy->view($outsider, $todo));
        $this->assertFalse(
            Todo::query()->whereKey($todo->id)->exists(),
            'A To-Do the policy denies must not appear in the scoped list either.',
        );
    }

    public function test_view_all_bypasses_the_scope_entirely(): void
    {
        $viewer = $this->userWithPermissions(['todos.view_all']);

        Todo::factory()->count(3)->create();

        $this->assertSame(3, Todo::query()->count());
    }

    public function test_the_scope_is_a_no_op_without_an_authenticated_user(): void
    {
        // A console report must not be silently narrowed to nothing.
        Todo::factory()->count(2)->create();

        $this->assertSame(2, Todo::query()->count());
    }

    public function test_scopes_narrow_the_query_as_documented(): void
    {
        $user = $this->plainUser();

        Todo::factory()->create(['title' => 'open', 'status' => WorkItemStatus::Inbox]);
        Todo::factory()->create(['title' => 'done', 'status' => WorkItemStatus::Completed]);
        Todo::factory()->create(['title' => 'archived', 'status' => WorkItemStatus::Archived]);

        $this->assertSame(1, Todo::query()->active()->count(), 'Completed and Archived are both terminal.');
        $this->assertSame(1, Todo::query()->status(WorkItemStatus::Completed)->count());
        $this->assertSame(2, Todo::query()->status([WorkItemStatus::Completed, WorkItemStatus::Archived])->count());

        Todo::factory()->overdue()->create(['title' => 'late']);
        $this->assertSame(1, Todo::query()->overdue()->count());

        $this->assertSame(0, Todo::query()->recurring()->count());
        Todo::factory()->create(['recurrence_rule' => ['frequency' => 'daily']]);
        $this->assertSame(1, Todo::query()->recurring()->count());

        $this->assertSame(1, Todo::query()->search('open')->count());
        $this->assertSame(5, Todo::query()->search(null)->count());
    }
}
