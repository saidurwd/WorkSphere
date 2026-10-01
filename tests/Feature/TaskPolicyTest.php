<?php

namespace Tests\Feature;

use Database\Factories\TaskFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The core IDOR regression net. Before Phase 2 every mutating route on Tasks,
 * Meetings and Obligations was reachable by any authenticated user who knew an
 * id, so each of these cases is a proof that the hole is closed.
 */
class TaskPolicyTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_a_non_assignee_cannot_view_a_task(): void
    {
        $outsider = $this->plainUser();
        $task = TaskFactory::new()->create();

        $this->actingAs($outsider)->get(route('tasks.show', $task))->assertForbidden();
    }

    public function test_a_non_assignee_cannot_edit_a_task(): void
    {
        $outsider = $this->plainUser();
        $task = TaskFactory::new()->create();

        $this->actingAs($outsider)->get(route('tasks.edit', $task))->assertForbidden();
    }

    public function test_a_non_assignee_cannot_update_a_task(): void
    {
        $outsider = $this->plainUser();
        $task = TaskFactory::new()->create();

        $this->actingAs($outsider)
            ->put(route('tasks.update', $task), [
                'title' => 'Hijacked',
                'priority' => 'high',
                'status' => 'pending',
                'due_date' => now()->format('Y-m-d'),
                'responsible_user_id' => $outsider->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id, 'title' => 'Hijacked']);
    }

    public function test_a_non_assignee_cannot_delete_a_task(): void
    {
        $outsider = $this->plainUser();
        $task = TaskFactory::new()->create();

        $this->actingAs($outsider)
            ->delete(route('tasks.destroy', $task))
            ->assertForbidden();

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_a_non_assignee_cannot_add_a_remark(): void
    {
        $outsider = $this->plainUser();
        $task = TaskFactory::new()->create();

        $this->actingAs($outsider)
            ->post(route('tasks.remarks.store', $task), ['remark' => 'sneaky'])
            ->assertForbidden();

        $this->assertDatabaseCount('task_remarks', 0);
    }

    public function test_the_owner_can_view_and_update_their_task(): void
    {
        $owner = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($owner)->create();

        $this->actingAs($owner)->get(route('tasks.show', $task))->assertOk();
        $this->actingAs($owner)->get(route('tasks.edit', $task))->assertOk();
    }

    public function test_the_responsible_user_may_also_act_on_the_task(): void
    {
        $owner = $this->plainUser();
        $responsible = $this->plainUser();

        $task = TaskFactory::new()->ownedBy($owner)->assignedTo($responsible)->create();

        $this->actingAs($responsible)->get(route('tasks.show', $task))->assertOk();
    }

    public function test_a_user_with_task_view_permission_sees_a_task_they_do_not_own(): void
    {
        $viewer = $this->userWithPermissions(['task.view']);
        $task = TaskFactory::new()->create();

        $this->actingAs($viewer)->get(route('tasks.show', $task))->assertOk();
    }

    public function test_task_view_permission_does_not_grant_update(): void
    {
        $viewer = $this->userWithPermissions(['task.view']);
        $task = TaskFactory::new()->create();

        $this->actingAs($viewer)->get(route('tasks.edit', $task))->assertForbidden();
    }

    public function test_a_super_admin_may_act_on_any_task(): void
    {
        $task = TaskFactory::new()->create();

        $this->actingAs($this->superAdmin())->get(route('tasks.show', $task))->assertOk();
        $this->actingAs($this->superAdmin())->delete(route('tasks.destroy', $task))->assertRedirect();

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_a_user_without_task_create_cannot_create_a_task(): void
    {
        $this->actingAs($this->plainUser())
            ->post(route('tasks.store'), [
                'title' => 'Nope',
                'priority' => 'high',
                'status' => 'pending',
                'due_date' => now()->format('Y-m-d'),
                'responsible_user_id' => $this->plainUser()->id,
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_a_user_with_task_create_can_create_a_task(): void
    {
        $user = $this->userWithPermissions(['task.create']);
        $responsible = $this->plainUser();

        $this->actingAs($user)
            ->post(route('tasks.store'), [
                'title' => 'Legitimate',
                'priority' => 'high',
                'status' => 'pending',
                'due_date' => now()->format('Y-m-d'),
                'responsible_user_id' => $responsible->id,
            ])
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', ['title' => 'Legitimate']);
    }

    public function test_the_index_only_lists_tasks_the_user_may_see(): void
    {
        $user = $this->plainUser();
        $mine = TaskFactory::new()->ownedBy($user)->create(['title' => 'Mine']);
        TaskFactory::new()->create(['title' => 'Theirs']);

        $response = $this->actingAs($user)->get(route('tasks.index'));

        $response->assertOk();
        $response->assertSee('Mine');
        $response->assertDontSee('Theirs');
    }

    public function test_a_user_with_task_view_sees_every_task_in_the_index(): void
    {
        $viewer = $this->userWithPermissions(['task.view']);
        TaskFactory::new()->create(['title' => 'Theirs']);

        $this->actingAs($viewer)->get(route('tasks.index'))->assertOk()->assertSee('Theirs');
    }

    public function test_an_anonymous_visitor_cannot_reach_a_task(): void
    {
        $task = TaskFactory::new()->create();

        $this->get(route('tasks.show', $task))->assertRedirect(route('login'));
    }
}
