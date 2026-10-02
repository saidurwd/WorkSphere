<?php

namespace Modules\Todos\Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Models\NotificationPreference;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Modules\Todos\Events\TodoAssigned;
use Modules\Todos\Events\TodoCommented;
use Modules\Todos\Events\TodoCompleted;
use Modules\Todos\Events\TodoCreated;
use Modules\Todos\Events\TodoDueSoon;
use Modules\Todos\Events\TodoMentioned;
use Modules\Todos\Events\TodoOverdue;
use Modules\Todos\Events\TodoRecurringGenerated;
use Modules\Todos\Events\TodoReminderFired;
use Modules\Todos\Events\TodoReopened;
use Modules\Todos\Jobs\SendTodoAssignedJob;
use Modules\Todos\Jobs\SendTodoCommentJob;
use Modules\Todos\Jobs\SendTodoCompletedJob;
use Modules\Todos\Jobs\SendTodoCreatedJob;
use Modules\Todos\Jobs\SendTodoDueSoonJob;
use Modules\Todos\Jobs\SendTodoMentionJob;
use Modules\Todos\Jobs\SendTodoNotificationJob;
use Modules\Todos\Jobs\SendTodoOverdueJob;
use Modules\Todos\Jobs\SendTodoReassignedJob;
use Modules\Todos\Jobs\SendTodoRecurringJob;
use Modules\Todos\Jobs\SendTodoReminderJob;
use Modules\Todos\Jobs\SendTodoReopenedJob;
use Modules\Todos\Listeners\NotifyTodoAssigned;
use Modules\Todos\Listeners\NotifyTodoCommented;
use Modules\Todos\Listeners\NotifyTodoCompleted;
use Modules\Todos\Listeners\NotifyTodoCreated;
use Modules\Todos\Listeners\NotifyTodoDueSoon;
use Modules\Todos\Listeners\NotifyTodoMentioned;
use Modules\Todos\Listeners\NotifyTodoOverdue;
use Modules\Todos\Listeners\NotifyTodoReassigned;
use Modules\Todos\Listeners\NotifyTodoRecurringGenerated;
use Modules\Todos\Listeners\NotifyTodoReminderFired;
use Modules\Todos\Listeners\NotifyTodoReopened;
use Modules\Todos\Models\Todo;
use Modules\Todos\Notifications\TodoNotification;
use Modules\Todos\Services\TodoNotificationService;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The notification pipeline's four guarantees — §6, §11.
 *
 * They are separated because they fail independently, and a single happy-path test
 * would keep passing while any one of them broke:
 *
 * - **Queueing.** No mail leaves a request or a scheduler tick (GAP-022).
 * - **Idempotency.** A re-fired cron sends nothing, because the dedupe key is
 *   UNIQUE and the first run claimed it.
 * - **Preferences.** A user can genuinely switch a type off (GAP-024).
 * - **Retry safety.** A job run twice is not a double send.
 */
class TodoNotificationPipelineTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    // ---- Queueing -----------------------------------------------------------

    /**
     * The queue runs synchronously in tests (`QUEUE_CONNECTION=sync`), which is
     * what makes this test possible: if the listener were NOT queued, the job would
     * run inline and the notification WOULD be sent, and the assertion below would
     * fail. Faking the queue would hide exactly the defect being guarded.
     */
    public function test_a_comment_does_not_send_inside_the_request(): void
    {
        Notification::fake();
        Mail::fake();

        $author = $this->userWithPermissions(['todos.comment']);
        $todo = Todo::factory()->createdBy($author)->create();

        Sanctum::actingAs($author);

        $this->postJson("/api/v1/todos/{$todo->id}/comments", ['body' => 'Agreed.'])->assertCreated();

        Notification::assertNothingSent();
        Mail::assertNothingSent();
    }

    public function test_every_listener_and_job_is_queueable(): void
    {
        // GAP-022, enforced structurally across the whole set at once: a single
        // listener or job that is not `ShouldQueue` runs inline at dispatch, which
        // is the exact failure this rule exists to prevent.
        $todo = Todo::factory()->assignedTo($this->assignee())->create();

        foreach ($this->jobClasses() as $job) {
            $constructor = (new \ReflectionClass($job))->getConstructor();
            $firstParameter = $constructor?->getParameters()[0] ?? null;

            $argument = match ($firstParameter?->getName()) {
                'comment' => $todo->comments()->make(['user_id' => $todo->creator_id, 'body' => 'x']),
                'reminder' => Reminder::factory()->make(),
                default => $todo,
            };

            $this->assertInstanceOf(
                ShouldQueue::class,
                new $job($argument),
                "{$job} is not a ShouldQueue job, so it would run inline at dispatch.",
            );
        }

        foreach ($this->listenerClasses() as $listener) {
            $this->assertInstanceOf(
                ShouldQueue::class,
                app($listener),
                "{$listener} is not a ShouldQueue listener, so it runs inside the request that dispatched its event.",
            );
        }
    }

    public function test_a_listener_only_pushes_a_job(): void
    {
        // `Queue::fake()` is what makes this observable: with the synchronous test
        // queue a dispatched job RUNS, so "the listener dispatched a job" and "the
        // job delivered a notification" are indistinguishable. Faking the queue
        // leaves the dispatch visible and the delivery not.
        Queue::fake();
        Notification::fake();
        Mail::fake();

        foreach ($this->todoLifecycleEvents() as [$event, $class]) {
            app($class)->handle($event);
        }

        Notification::assertNothingSent();
        Mail::assertNothingSent();

        // Every event reached a queued job rather than a delivery.
        Queue::assertPushed(SendTodoAssignedJob::class);
        Queue::assertPushed(SendTodoCommentJob::class);
        Queue::assertPushed(SendTodoCompletedJob::class);
        Queue::assertPushed(SendTodoCreatedJob::class);
        Queue::assertPushed(SendTodoReopenedJob::class);
        Queue::assertPushed(SendTodoMentionJob::class);
        Queue::assertPushed(SendTodoReminderJob::class);
        Queue::assertPushed(SendTodoDueSoonJob::class);
        Queue::assertPushed(SendTodoOverdueJob::class);
        Queue::assertPushed(SendTodoRecurringJob::class);
    }

    /**
     * @return list<class-string>
     */
    private function listenerClasses(): array
    {
        return [
            NotifyTodoAssigned::class,
            NotifyTodoCommented::class,
            NotifyTodoCompleted::class,
            NotifyTodoCreated::class,
            NotifyTodoDueSoon::class,
            NotifyTodoMentioned::class,
            NotifyTodoOverdue::class,
            NotifyTodoReassigned::class,
            NotifyTodoRecurringGenerated::class,
            NotifyTodoReminderFired::class,
            NotifyTodoReopened::class,
        ];
    }

    // ---- Idempotency --------------------------------------------------------

    public function test_running_the_overdue_command_twice_sends_once(): void
    {
        Notification::fake();

        $assignee = $this->userWithPermissions(['todos.view_all']);
        $todo = Todo::factory()->assignedTo($assignee)->overdue()->create();

        $this->artisan('todos:overdue')->assertSuccessful();
        $this->artisan('todos:overdue')->assertSuccessful();

        // One notification, one log row — for TWO runs. The dedupe key is the whole
        // mechanism, and this is the assertion that proves it works.
        Notification::assertSentToTimes($assignee, TodoNotification::class, 1);

        $this->assertSame(
            1,
            DB::table('notification_logs')->where('dedupe_key', 'like', 'todo.overdue:'.$todo->id.'%')->count(),
        );
    }

    public function test_the_dedupe_key_is_claimed_by_the_first_run(): void
    {
        Notification::fake();

        $service = app(TodoNotificationService::class);
        $todo = Todo::factory()->assignedTo($this->assignee())->create();

        $this->assertTrue($service->deliver($todo->assignee, $todo, NotificationType::TodoOverdue, '2026-10-01'));
        $this->assertFalse($service->deliver($todo->assignee, $todo, NotificationType::TodoOverdue, '2026-10-01'));

        Notification::assertSentToTimes($todo->assignee, TodoNotification::class, 1);
    }

    public function test_a_different_discriminator_is_a_different_notification(): void
    {
        Notification::fake();

        $service = app(TodoNotificationService::class);
        $todo = Todo::factory()->assignedTo($this->assignee())->create();

        $this->assertTrue($service->deliver($todo->assignee, $todo, NotificationType::TodoOverdue, '2026-10-01'));
        $this->assertTrue($service->deliver($todo->assignee, $todo, NotificationType::TodoOverdue, '2026-10-02'));

        // The discriminator is the RUN DATE on purpose: "overdue today" is a
        // different fact from "overdue yesterday", and suppressing the second would
        // be suppressing a real reminder.
        Notification::assertSentToTimes($todo->assignee, TodoNotification::class, 2);
    }

    public function test_running_the_same_job_twice_sends_once(): void
    {
        Notification::fake();

        $todo = Todo::factory()->assignedTo($this->assignee())->create();

        $job = new SendTodoAssignedJob($todo, $todo->creator_id, '2026-10-01');

        $job->handle(app(TodoNotificationService::class));
        $job->handle(app(TodoNotificationService::class));

        // A queue redelivers on failure. Without dedupe a retry is a second email,
        // which is the failure mode retries are supposed to prevent.
        Notification::assertSentToTimes($todo->assignee, TodoNotification::class, 1);
    }

    // ---- Preferences --------------------------------------------------------

    public function test_a_user_with_no_preference_row_is_opted_in(): void
    {
        Notification::fake();

        $todo = Todo::factory()->assignedTo($this->assignee())->create();

        $this->assertTrue(app(TodoNotificationService::class)->shouldNotify(
            $todo->assignee,
            NotificationType::TodoOverdue,
            NotificationChannel::Mail,
        ));
    }

    public function test_an_opt_out_suppresses_delivery(): void
    {
        Notification::fake();

        $service = app(TodoNotificationService::class);
        $todo = Todo::factory()->assignedTo($this->assignee())->create();

        $this->optOutOfEveryChannel($todo->assignee, NotificationType::TodoOverdue);

        $this->assertFalse($service->deliver($todo->assignee, $todo, NotificationType::TodoOverdue));

        Notification::assertNothingSent();
    }

    public function test_an_opt_out_writes_no_log_row(): void
    {
        Notification::fake();

        $service = app(TodoNotificationService::class);
        $todo = Todo::factory()->assignedTo($this->assignee())->create();

        $this->optOutOfEveryChannel($todo->assignee, NotificationType::TodoOverdue);

        $this->assertFalse($service->deliver($todo->assignee, $todo, NotificationType::TodoOverdue));

        // No log row, because a row would CONSUME the dedupe key. A user who opted
        // back in would then find their first real notification already claimed and
        // silently never delivered.
        $this->assertSame(
            0,
            DB::table('notification_logs')->where('user_id', $todo->assignee->id)->count(),
        );
    }

    public function test_opting_back_in_delivers(): void
    {
        Notification::fake();

        $service = app(TodoNotificationService::class);
        $todo = Todo::factory()->assignedTo($this->assignee())->create();

        $this->optOutOfEveryChannel($todo->assignee, NotificationType::TodoOverdue);
        $this->assertFalse($service->deliver($todo->assignee, $todo, NotificationType::TodoOverdue));

        $this->optInToEveryChannel($todo->assignee, NotificationType::TodoOverdue);
        $this->assertTrue($service->deliver($todo->assignee, $todo, NotificationType::TodoOverdue));

        Notification::assertSentToTimes($todo->assignee, TodoNotification::class, 1);
    }

    public function test_a_preference_is_scoped_to_one_notification_type(): void
    {
        Notification::fake();

        $service = app(TodoNotificationService::class);
        $todo = Todo::factory()->assignedTo($this->assignee())->create();

        NotificationPreference::factory()->disabled()->forType(NotificationType::TodoOverdue)
            ->create(['user_id' => $todo->assignee->id, 'channel' => NotificationChannel::Mail->value]);

        // Opting out of "overdue" must not silence "assigned".
        $this->assertTrue($service->shouldNotify($todo->assignee, NotificationType::TodoAssigned, NotificationChannel::Mail));
    }

    public function test_opting_out_of_one_channel_keeps_the_other(): void
    {
        Notification::fake();

        $service = app(TodoNotificationService::class);
        $todo = Todo::factory()->assignedTo($this->assignee())->create();

        NotificationPreference::factory()->disabled()
            ->forType(NotificationType::TodoOverdue)
            ->onChannel(NotificationChannel::Mail)
            ->create(['user_id' => $todo->assignee->id]);

        $channels = $service->channelsFor($todo->assignee, NotificationType::TodoOverdue);

        // Per-channel, not all-or-nothing: a user who silences email still wants the
        // in-app notice. The notification's `via()` has to honour this, which it did
        // not until Phase 13 made the resolved channels an argument.
        // `channelsFor()` returns ENUM CASES, not strings — `json_encode` makes an
        // enum print as its value, which hides the difference until an assertion
        // compares against a plain string and fails.
        $this->assertNotContains(NotificationChannel::Mail, $channels);
        $this->assertContains(NotificationChannel::Database, $channels);

        $this->assertTrue($service->deliver($todo->assignee, $todo, NotificationType::TodoOverdue));

        Notification::assertSentTo(
            $todo->assignee,
            TodoNotification::class,
            fn (TodoNotification $notification, array $channels): bool => ! in_array('mail', $channels, true),
        );
    }

    // ---- What is logged -----------------------------------------------------

    public function test_a_delivery_log_holds_no_message_body_or_recipient_address(): void
    {
        Notification::fake();

        $assignee = $this->assignee();
        $todo = Todo::factory()->assignedTo($assignee)->create(['title' => 'A confidential matter']);

        app(TodoNotificationService::class)->deliver($assignee, $todo, NotificationType::TodoOverdue);

        $row = DB::table('notification_logs')->first();

        $this->assertNotNull($row);
        // `notification_logs` is read by admin screens. Copying the body or the
        // address in would put text in front of readers who are not cleared to see
        // it — the very reason the shared table exists.
        foreach (['subject', 'message'] as $column) {
            $this->assertTrue(
                array_key_exists($column, (array) $row),
                "notification_logs should declare {$column} so it can be left empty on purpose.",
            );
            $this->assertNull($row->{$column});
        }

        $this->assertStringNotContainsString('A confidential matter', json_encode($row));
        $this->assertStringNotContainsString((string) $todo->assignee->email, json_encode($row));
    }

    public function test_a_delivery_records_its_dedupe_key(): void
    {
        Notification::fake();

        $todo = Todo::factory()->assignedTo($this->assignee())->create();

        app(TodoNotificationService::class)->deliver($todo->assignee, $todo, NotificationType::TodoAssigned);

        $this->assertSame(
            'todo.assigned:'.$todo->id,
            DB::table('notification_logs')->value('dedupe_key'),
        );
    }

    // ---- Reminders ----------------------------------------------------------

    public function test_a_duplicate_reminder_cannot_be_created(): void
    {
        $todo = Todo::factory()->assignedTo($this->assignee())->create();

        Reminder::factory()->create([
            'subject_type' => Todo::class,
            'subject_id' => $todo->id,
            'remind_at' => now()->addHour(),
        ]);

        // The UNIQUE index, not a check-then-insert. Without the index this is a
        // race two dispatches can both win.
        $this->expectException(UniqueConstraintViolationException::class);

        Reminder::factory()->create([
            'subject_type' => Todo::class,
            'subject_id' => $todo->id,
            'remind_at' => now()->addHour(),
        ]);
    }

    // ---- Fixtures -----------------------------------------------------------

    private function optOutOfEveryChannel(User $user, NotificationType $type): void
    {
        foreach (NotificationChannel::deliverableCases() as $channel) {
            NotificationPreference::set($user, $type->value, $channel, false);
        }
    }

    private function optInToEveryChannel(User $user, NotificationType $type): void
    {
        foreach (NotificationChannel::deliverableCases() as $channel) {
            NotificationPreference::set($user, $type->value, $channel, true);
        }
    }

    private ?User $assignee = null;

    private function assignee(): User
    {
        return $this->assignee ??= User::factory()->create();
    }

    /**
     * @return list<class-string<SendTodoNotificationJob>>
     */
    private function jobClasses(): array
    {
        return [
            SendTodoAssignedJob::class,
            SendTodoCommentJob::class,
            SendTodoCompletedJob::class,
            SendTodoCreatedJob::class,
            SendTodoDueSoonJob::class,
            SendTodoMentionJob::class,
            SendTodoOverdueJob::class,
            SendTodoReassignedJob::class,
            SendTodoRecurringJob::class,
            SendTodoReminderJob::class,
            SendTodoReopenedJob::class,
        ];
    }

    /**
     * A constructed event and the listener that consumes it.
     *
     * @return list<array{0: object, 1: class-string}>
     */
    private function todoLifecycleEvents(): array
    {
        $todo = Todo::factory()->assignedTo($this->assignee())->create();
        $comment = $todo->comments()->create(['user_id' => $todo->creator_id, 'body' => 'Hello']);

        return [
            [new TodoAssigned($todo, $todo->creator_id), NotifyTodoAssigned::class],
            [new TodoCommented($comment, $todo->creator_id), NotifyTodoCommented::class],
            [new TodoCompleted($todo, $todo->creator_id), NotifyTodoCompleted::class],
            [new TodoCreated($todo, $todo->creator_id), NotifyTodoCreated::class],
            [new TodoReopened($todo, $todo->creator_id), NotifyTodoReopened::class],
            [new TodoMentioned($comment, [$todo->assignee_id], $todo->creator_id), NotifyTodoMentioned::class],
            [new TodoReminderFired(Reminder::factory()->create(['subject_type' => Todo::class, 'subject_id' => $todo->id]), $todo->id), NotifyTodoReminderFired::class],
            [new TodoDueSoon($todo, $todo->assignee_id), NotifyTodoDueSoon::class],
            [new TodoOverdue($todo, $todo->assignee_id), NotifyTodoOverdue::class],
            [new TodoRecurringGenerated($todo, $todo->id), NotifyTodoRecurringGenerated::class],
        ];
    }
}
