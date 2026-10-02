<?php

namespace Modules\Todos\Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Todos\Events\TodoCommented;
use Modules\Todos\Events\TodoMentioned;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoCommentService;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * `TodoCommentService` — the write path both the web controller and the API
 * controller share.
 *
 * This lives in the MODULE because the module owns the behaviour. It is also the
 * file's proof of purpose: Phase 12 moved comment creation out of the controllers
 * because two controllers each had to remember the same three steps, and a shared
 * service with no test is a shared bug waiting to happen.
 *
 * The two things worth pinning are the ones a controller test cannot see:
 *
 * - the events fire INSIDE the transaction, so a listener that loads the comment
 *   cannot race its own creation;
 * - a comment with no mention dispatches `TodoCommented` and NOT `TodoMentioned`.
 */
class TodoCommentServiceTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private function service(): TodoCommentService
    {
        return app(TodoCommentService::class);
    }

    public function test_it_creates_a_comment_and_its_activity_row(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        $comment = $this->service()->store($user, $todo, 'Agreed.', null, []);

        $this->assertTrue($comment->exists);
        $this->assertSame('Agreed.', $comment->body);
        $this->assertSame($user->id, $comment->user_id);
        $this->assertNull($comment->mentions, 'Nobody was mentioned, so mentions must be NULL rather than an empty array.');

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Todo::class,
            'subject_id' => $todo->id,
            'action' => 'commented',
            'user_id' => $user->id,
        ]);
    }

    public function test_the_commented_event_sees_the_persisted_comment(): void
    {
        Event::fake([TodoCommented::class]);

        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        $this->service()->store($user, $todo, 'Agreed.', null, []);

        Event::assertDispatched(TodoCommented::class, function (TodoCommented $event) use ($user): bool {
            // The listener receives a model with a key and a readable body. If the
            // event were dispatched before the insert, this assertion would be
            // asserting on nothing.
            return $event->comment->exists
                && $event->comment->body === 'Agreed.'
                && $event->actorId === $user->id;
        });
    }

    public function test_a_comment_with_mentions_dispatches_both_events(): void
    {
        Event::fake([TodoCommented::class, TodoMentioned::class]);

        $user = $this->plainUser();
        $mentioned = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        $this->service()->store($user, $todo, 'cc @someone', null, [$mentioned->id]);

        Event::assertDispatched(TodoCommented::class);
        Event::assertDispatched(
            TodoMentioned::class,
            fn (TodoMentioned $event): bool => $event->mentionedUserIds === [$mentioned->id],
        );

        $this->assertSame(
            [$mentioned->id],
            $todo->comments()->firstOrFail()->mentions,
        );
    }

    public function test_no_mention_means_no_mention_event(): void
    {
        Event::fake([TodoCommented::class, TodoMentioned::class]);

        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        $this->service()->store($user, $todo, 'No names here.', null, []);

        Event::assertDispatched(TodoCommented::class);
        // Silence is the assertion: dispatching TodoMentioned with an empty list
        // would notify nobody while still recording that a mention happened.
        Event::assertNotDispatched(TodoMentioned::class);
    }

    public function test_a_reply_carries_its_parent(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        $parent = $this->service()->store($user, $todo, 'Root', null, []);

        $reply = $this->service()->store($user, $todo, 'Reply', $parent->id, []);

        $this->assertSame($parent->id, $reply->parent_id);
        $this->assertSame(
            1,
            $todo->comments()->whereNull('parent_id')->count(),
        );
    }

    public function test_the_thread_returns_top_level_comments_with_nested_replies(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        $first = $this->service()->store($user, $todo, 'First', null, []);
        $this->service()->store($user, $todo, 'A reply', $first->id, []);
        $this->service()->store($user, $todo, 'Second', null, []);

        $thread = $this->service()->thread($todo);

        $this->assertCount(2, $thread);
        $this->assertSame(['First', 'Second'], $thread->pluck('body')->all());
        $this->assertTrue($thread->first()->relationLoaded('author'));
        $this->assertCount(1, $thread->first()->replies);
        $this->assertSame('A reply', $thread->first()->replies->first()->body);
    }

    public function test_comments_write_to_the_shared_table_not_a_module_one(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        $this->service()->store($user, $todo, 'Shared.', null, []);

        // §4.5 exists so Meetings and To-Dos stop each having their own comment
        // table. Asserting the shared table is used keeps that from regressing.
        $this->assertDatabaseHas('comments', [
            'commentable_type' => Todo::class,
            'commentable_id' => $todo->id,
            'body' => 'Shared.',
        ]);

        $this->assertSame(
            1,
            Comment::query()->where('commentable_type', Todo::class)->count(),
        );
    }

    public function test_the_activity_row_names_the_comment(): void
    {
        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create();

        $comment = $this->service()->store($user, $todo, 'Tracked.', null, []);

        $log = ActivityLog::query()
            ->where('subject_type', Todo::class)
            ->where('action', 'commented')
            ->latest('id')
            ->firstOrFail();

        // Without the comment id the trail says "something happened" and not what.
        $this->assertSame(['comment_id' => $comment->id], $log->new_value);
    }

    public function test_the_service_does_not_authorize(): void
    {
        // Authorization belongs to `TodoPolicy`. The service accepts an already-
        // authorised actor and writes; a test asserting the service refuses would
        // be asserting a second, divergent permission layer exists.
        $author = $this->plainUser();
        $todo = Todo::factory()->create();

        $comment = $this->service()->store($author, $todo, 'Written by anyone.', null, []);

        $this->assertTrue($comment->exists);
        $this->assertSame($author->id, $comment->user_id);
    }

    public function test_it_works_for_a_user_with_no_employee_record(): void
    {
        // `users.employee_id` is NOT NULL as of Phase 8, but the service takes a
        // `User`, not an employee, and must not reach through one.
        $user = User::factory()->create();

        $todo = Todo::factory()->createdBy($user)->create();

        $this->assertTrue(
            $this->service()->store($user, $todo, 'No employee needed.', null, [])->exists,
        );
    }
}
