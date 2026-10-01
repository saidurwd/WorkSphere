<?php

namespace Tests\Feature;

use Database\Factories\TaskFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Tasks\Models\Task;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Sub-tasks — GAP-025.
 *
 * The cycle guard is the reason this file is mostly about refusing things. A
 * circular `parent_id` is not cosmetic: `descendants()` walks the chain, so a
 * cycle turns a task page into an infinite walk rather than a slightly odd
 * display, and the self-parent case is the shortest version of it.
 */
class TaskSubtaskTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    // ---- Cycle prevention --------------------------------------------------

    public function test_a_task_cannot_be_its_own_parent(): void
    {
        $task = TaskFactory::new()->create();

        $this->assertTrue($task->wouldCreateCycle($task->id));

        $this->actingAs($task->user)
            ->put(route('tasks.parent.update', $task), ['parent_id' => $task->id])
            ->assertSessionHasErrors('parent_id');

        $this->assertNull($task->fresh()->parent_id);
    }

    public function test_a_task_cannot_move_under_its_own_child(): void
    {
        $parent = TaskFactory::new()->create();
        $child = TaskFactory::new()->withParent($parent)->create();

        // parent -> child, so parent under child would be a two-node cycle.
        $this->assertTrue($parent->wouldCreateCycle($child->id));

        $this->actingAs($parent->user)
            ->put(route('tasks.parent.update', $parent), ['parent_id' => $child->id])
            ->assertSessionHasErrors('parent_id');

        $this->assertNull($parent->fresh()->parent_id);
    }

    public function test_a_task_cannot_move_under_a_deep_descendant(): void
    {
        $root = TaskFactory::new()->create();
        $mid = TaskFactory::new()->withParent($root)->create();
        $leaf = TaskFactory::new()->withParent($mid)->create();

        $this->assertTrue($root->wouldCreateCycle($leaf->id));
        $this->assertTrue($root->wouldCreateCycle($mid->id));
        $this->assertFalse($root->wouldCreateCycle($root->id) === false);
    }

    public function test_moving_a_task_under_an_unrelated_parent_is_allowed(): void
    {
        $task = TaskFactory::new()->create();
        $newParent = TaskFactory::new()->create();

        $this->assertFalse($task->wouldCreateCycle($newParent->id));

        $this->actingAs($task->user)
            ->put(route('tasks.parent.update', $task), ['parent_id' => $newParent->id])
            ->assertRedirect();

        $this->assertSame($newParent->id, $task->fresh()->parent_id);
    }

    public function test_a_task_can_be_detached_by_sending_null(): void
    {
        $parent = TaskFactory::new()->create();
        $child = TaskFactory::new()->withParent($parent)->create();

        $this->assertFalse($child->wouldCreateCycle(null));

        $this->actingAs($child->user)
            ->put(route('tasks.parent.update', $child), ['parent_id' => ''])
            ->assertRedirect();

        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_a_stranger_cannot_reparent_someone_elses_task(): void
    {
        $task = TaskFactory::new()->create();
        $newParent = TaskFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->put(route('tasks.parent.update', $task), ['parent_id' => $newParent->id])
            ->assertForbidden();

        $this->assertNull($task->fresh()->parent_id);
    }

    public function test_descendants_walks_the_whole_subtree(): void
    {
        $root = TaskFactory::new()->create();
        $mid = TaskFactory::new()->withParent($root)->create();
        $leaf = TaskFactory::new()->withParent($mid)->create();

        $ids = $root->descendants()->pluck('id')->all();

        $this->assertContains($mid->id, $ids);
        $this->assertContains($leaf->id, $ids);
        $this->assertCount(2, $ids);
    }

    public function test_descendants_terminates_on_a_corrupt_cycle(): void
    {
        // The guard: even with a cycle already in the data, the walk must end.
        $a = TaskFactory::new()->create();
        $b = TaskFactory::new()->create();

        $a->forceFill(['parent_id' => $b->id])->saveQuietly();
        $b->forceFill(['parent_id' => $a->id])->saveQuietly();

        $this->assertLessThan(
            10,
            $a->fresh()->descendants()->count(),
            'A corrupt cycle must not make descendants() walk forever.',
        );
    }

    public function test_ancestors_walks_up_the_chain(): void
    {
        $root = TaskFactory::new()->create();
        $mid = TaskFactory::new()->withParent($root)->create();
        $leaf = TaskFactory::new()->withParent($mid)->create();

        $ids = $leaf->ancestors()->pluck('id')->all();

        $this->assertSame([$mid->id, $root->id], $ids);
    }

    // ---- Creation -----------------------------------------------------------

    public function test_a_subtask_can_be_created(): void
    {
        $parent = TaskFactory::new()->create();

        $this->actingAs($parent->user)
            ->post(route('tasks.subtasks.store', $parent), ['title' => 'Write the migration'])
            ->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'parent_id' => $parent->id,
            'title' => 'Write the migration',
            'status' => 'pending',
        ]);
    }

    public function test_creating_a_subtask_is_recorded_on_the_activity_trail(): void
    {
        $parent = TaskFactory::new()->create();

        $this->actingAs($parent->user)
            ->post(route('tasks.subtasks.store', $parent), ['title' => 'Logged']);

        $this->assertDatabaseHas('activity_logs', [
            'module_name' => 'Task',
            'record_id' => $parent->id,
            'action' => 'subtask_created',
        ]);
    }

    public function test_a_stranger_cannot_add_a_subtask_to_someone_elses_task(): void
    {
        $parent = TaskFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->post(route('tasks.subtasks.store', $parent), ['title' => 'Not mine'])
            ->assertForbidden();
    }

    public function test_a_subtask_needs_a_title(): void
    {
        $parent = TaskFactory::new()->create();

        $this->actingAs($parent->user)
            ->post(route('tasks.subtasks.store', $parent), ['title' => ''])
            ->assertSessionHasErrors('title');
    }

    // ---- Hierarchy ----------------------------------------------------------

    public function test_the_top_level_scope_excludes_subtasks(): void
    {
        $parent = TaskFactory::new()->create();
        TaskFactory::new()->withParent($parent)->create();

        $this->assertSame(1, Task::query()->topLevel()->count());
    }

    public function test_deleting_a_parent_orphans_rather_than_deletes_its_subtasks(): void
    {
        $parent = TaskFactory::new()->create();
        $child = TaskFactory::new()->withParent($parent)->create();

        $parent->delete();

        // The sub-task is real work; losing the parent must not destroy it.
        $this->assertNull($child->fresh()->parent_id);
        $this->assertNotNull(Task::query()->find($child->id));
    }
}
