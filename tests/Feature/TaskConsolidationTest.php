<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Tag;
use Database\Factories\TaskFactory;
use Database\Factories\TaskRemarkFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Services\TaskRemarkSynchroniser;
use Modules\Todos\Models\Todo;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Task tags and the `task_remarks` consolidation — GAP-025 / GAP-048.
 *
 * Both are dual-write. Every case asserts the legacy table is *still* written
 * alongside the shared one, because the moment it stops being written, every
 * existing task screen, mail template and job that reads it silently loses data.
 */
class TaskConsolidationTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    // ---- Shared tags -------------------------------------------------------

    public function test_a_tag_can_be_attached_to_a_task(): void
    {
        $owner = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($owner)->create();

        $this->actingAs($owner)
            ->post(route('tasks.tags.store', $task), ['tag' => 'Budget'])
            ->assertRedirect();

        $this->assertDatabaseHas('taggables', [
            'taggable_type' => Task::class,
            'taggable_id' => $task->id,
        ]);
        $this->assertDatabaseHas('tags', ['name' => 'Budget']);
    }

    public function test_attaching_the_same_tag_twice_is_idempotent(): void
    {
        $owner = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($owner)->create();

        $this->actingAs($owner)->post(route('tasks.tags.store', $task), ['tag' => 'Budget']);
        $this->actingAs($owner)->post(route('tasks.tags.store', $task), ['tag' => 'Budget']);

        $this->assertSame(1, $task->tags()->count());
        $this->assertSame(1, Tag::query()->where('name', 'Budget')->count());
    }

    public function test_a_tag_is_the_same_row_across_modules(): void
    {
        // The whole point of a shared vocabulary: one "Budget" tag, not three
        // near-identical rows that no cross-module query can match.
        $owner = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($owner)->create();

        $this->actingAs($owner)->post(route('tasks.tags.store', $task), ['tag' => 'Budget']);

        $todo = Todo::factory()->createdBy($owner)->create();
        $todo->tags()->syncWithoutDetaching([Tag::query()->where('name', 'Budget')->value('id')]);

        $this->assertSame(1, Tag::query()->where('name', 'Budget')->count());
        $this->assertTrue($task->tags()->where('name', 'Budget')->exists());
        $this->assertTrue($todo->tags()->where('name', 'Budget')->exists());
    }

    public function test_a_tag_can_be_removed(): void
    {
        $owner = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($owner)->create();

        $this->actingAs($owner)->post(route('tasks.tags.store', $task), ['tag' => 'Budget']);
        $tag = Tag::query()->where('name', 'Budget')->firstOrFail();

        $this->actingAs($owner)
            ->delete(route('tasks.tags.destroy', [$task, $tag]))
            ->assertRedirect();

        $this->assertDatabaseMissing('taggables', ['taggable_id' => $task->id, 'tag_id' => $tag->id]);
    }

    public function test_a_stranger_cannot_tag_someone_elses_task(): void
    {
        $task = TaskFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->post(route('tasks.tags.store', $task), ['tag' => 'Budget'])
            ->assertForbidden();

        $this->assertDatabaseCount('taggables', 0);
    }

    public function test_tagging_requires_authentication(): void
    {
        $task = TaskFactory::new()->create();

        $this->post(route('tasks.tags.store', $task), ['tag' => 'Budget'])
            ->assertRedirect(route('login'));
    }

    // ---- Remark dual-write -------------------------------------------------

    public function test_a_new_remark_is_written_to_both_tables(): void
    {
        $owner = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($owner)->create();

        $this->actingAs($owner)
            ->post(route('tasks.remarks.store', $task), ['remark' => 'Blocked on legal'])
            ->assertRedirect();

        // The legacy table must keep being written, or every existing reader of
        // task_remarks — the index, the mail templates, the assigned job — loses
        // data without any error.
        $this->assertDatabaseHas('task_remarks', [
            'task_id' => $task->id,
            'remark' => 'Blocked on legal',
        ]);

        $this->assertDatabaseHas('comments', [
            'commentable_type' => Task::class,
            'commentable_id' => $task->id,
            'body' => 'Blocked on legal',
        ]);
    }

    public function test_a_comment_stream_spans_tasks_and_todos(): void
    {
        $user = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($user)->create();
        $todo = Todo::factory()->createdBy($user)->create();

        $task->sharedComments()->create(['user_id' => $user->id, 'body' => 'On the task']);
        $todo->comments()->create(['user_id' => $user->id, 'body' => 'On the To-Do']);

        $bodies = Comment::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at')
            ->pluck('body')
            ->all();

        $this->assertSame(['On the task', 'On the To-Do'], $bodies);
    }

    public function test_the_backfill_mirrors_historical_remarks(): void
    {
        $task = TaskFactory::new()->create();

        TaskRemarkFactory::new()->count(3)->create(['task_id' => $task->id]);

        $mirrored = app(TaskRemarkSynchroniser::class)->backfill();

        $this->assertSame(3, $mirrored);
        $this->assertSame(3, Comment::query()->where('commentable_type', Task::class)->count());
    }

    public function test_the_backfill_is_idempotent(): void
    {
        $task = TaskFactory::new()->create();
        TaskRemarkFactory::new()->count(3)->create(['task_id' => $task->id]);

        $service = app(TaskRemarkSynchroniser::class);

        $this->assertSame(3, $service->backfill());
        $this->assertSame(0, $service->backfill(), 'A second backfill must mirror nothing.');

        $this->assertSame(
            3,
            Comment::query()->where('commentable_type', Task::class)->count(),
            'A remark must never be duplicated in the shared table.',
        );
    }

    public function test_the_backfill_preserves_the_original_remark_time(): void
    {
        $task = TaskFactory::new()->create();
        $remark = TaskRemarkFactory::new()->create([
            'task_id' => $task->id,
            'created_at' => '2019-03-04 09:00:00',
        ]);

        app(TaskRemarkSynchroniser::class)->backfill();

        $comment = Comment::query()
            ->where('commentable_type', Task::class)
            ->where('body', $remark->remark)
            ->firstOrFail();

        // A comment from 2019 must not surface at the top of a timeline as though
        // it were written today.
        $this->assertSame(
            $remark->created_at->format('Y-m-d H:i:s'),
            $comment->created_at->format('Y-m-d H:i:s'),
        );
    }

    public function test_an_orphaned_remark_cannot_exist_so_the_backfill_cannot_stall_on_one(): void
    {
        // `task_remarks.task_id` is a real foreign key, so a remark whose task has
        // gone cannot be written at all — which is what makes the backfill's
        // defensive `task === null` guard unreachable rather than load-bearing.
        $this->assertTrue(
            Schema::hasTable('task_remarks'),
        );

        $this->expectException(QueryException::class);

        DB::table('task_remarks')->insert([
            'task_id' => 999999,
            'user_id' => $this->plainUser()->id,
            'remark' => 'Orphan',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_the_backfill_completes_over_a_large_remark_table(): void
    {
        $task = TaskFactory::new()->create();
        TaskRemarkFactory::new()->count(12)->create(['task_id' => $task->id]);

        // The chunk size is 200, so this exercises the loop's termination rather
        // than a single pass.
        $this->assertSame(12, app(TaskRemarkSynchroniser::class)->backfill());
    }

    public function test_the_legacy_table_is_not_dropped(): void
    {
        $this->assertTrue(
            Schema::hasTable('task_remarks'),
            'task_remarks is dropped; the consolidation is dual-write until Phase 15.',
        );
    }

    public function test_the_cutover_plan_names_every_step(): void
    {
        $plan = TaskRemarkSynchroniser::cutoverPlan();

        foreach (['step_1', 'step_2', 'step_3', 'step_4', 'step_5'] as $step) {
            $this->assertArrayHasKey($step, $plan);
        }

        $this->assertStringContainsString('Phase 15', $plan['step_5']);
    }
}
