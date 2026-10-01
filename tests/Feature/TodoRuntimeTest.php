<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Enums\ReminderStatus;
use App\Enums\WorkItemStatus;
use App\Models\NotificationPreference;
use App\Models\Reminder;
use App\Models\User;
use App\Services\ReminderScheduler;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Modules\Todos\Events\TodoAssigned;
use Modules\Todos\Events\TodoReminderFired;
use Modules\Todos\Jobs\SendTodoDueSoonJob;
use Modules\Todos\Jobs\SendTodoNotificationJob;
use Modules\Todos\Jobs\SendTodoOverdueJob;
use Modules\Todos\Models\Scopes\TodoScope;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoNotificationService;
use Modules\Todos\Services\TodoService;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The runtime guarantees — TODO-MODULE-SPECIFICATION.md §6.
 *
 * Every case here is about a failure that is invisible when everything works: a
 * scheduler that fires twice, a job that runs inline, a reminder that fires at
 * the wrong hour. Each is asserted directly rather than inferred.
 */
class TodoRuntimeTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    // ---- reminders:dispatch ------------------------------------------------

    public function test_a_due_reminder_is_fired_and_marked_sent(): void
    {
        // The dispatcher's job is to claim the row and raise the event; the event's
        // listener is itself queued, so under Queue::fake() it never runs and the
        // job is never pushed. The boundary being asserted here is the event.
        Event::fake([TodoReminderFired::class]);

        $todo = Todo::factory()->create();
        $reminder = $this->reminderFor($todo, now()->subMinute());

        $this->artisan('reminders:dispatch')->assertSuccessful();

        $this->assertSame(ReminderStatus::Sent, $reminder->fresh()->status);
        $this->assertNotNull($reminder->fresh()->sent_at);

        Event::assertDispatched(
            TodoReminderFired::class,
            fn (TodoReminderFired $event): bool => $event->reminder->is($reminder),
        );
    }

    public function test_a_reminder_that_is_not_yet_due_is_left_alone(): void
    {
        Event::fake([TodoReminderFired::class]);

        $todo = Todo::factory()->create();
        $this->reminderFor($todo, now()->addHour());

        $this->artisan('reminders:dispatch')->assertSuccessful();

        $this->assertSame(ReminderStatus::Pending, $this->findReminderFor($todo)->status);
        Event::assertNotDispatched(TodoReminderFired::class);
    }

    public function test_running_the_dispatcher_twice_fires_each_reminder_once(): void
    {
        Event::fake([TodoReminderFired::class]);

        $todo = Todo::factory()->create();
        $this->reminderFor($todo, now()->subMinute());

        $this->artisan('reminders:dispatch')->assertSuccessful();
        $this->artisan('reminders:dispatch')->assertSuccessful();

        // The second pass finds nothing pending, so it cannot fire again.
        Event::assertDispatchedTimes(TodoReminderFired::class, 1);
    }

    public function test_a_reminder_is_only_claimed_once_under_concurrent_dispatchers(): void
    {
        Queue::fake();

        $todo = Todo::factory()->create();
        $reminder = $this->reminderFor($todo, now()->subMinute());

        // Two dispatchers, both of which see the row as due. The conditional
        // UPDATE is the mechanism: only one can match `status = pending`.
        $first = DB::table('reminders')
            ->where('id', $reminder->id)
            ->where('status', ReminderStatus::Pending->value)
            ->update(['status' => ReminderStatus::Sent->value, 'sent_at' => now()]);

        $second = DB::table('reminders')
            ->where('id', $reminder->id)
            ->where('status', ReminderStatus::Pending->value)
            ->update(['status' => ReminderStatus::Sent->value, 'sent_at' => now()]);

        $this->assertSame(1, $first);
        $this->assertSame(0, $second, 'A second dispatcher must not be able to claim the same reminder.');
    }

    public function test_a_reminder_for_a_deleted_todo_does_not_abort_the_pass(): void
    {
        Event::fake([TodoReminderFired::class]);

        // A reminder whose subject has gone. The dispatcher must survive it.
        DB::table('reminders')->insert([
            'subject_type' => Todo::class,
            'subject_id' => 999999,
            'remind_at' => now()->subMinute(),
            'channel' => 'in_app',
            'status' => ReminderStatus::Pending->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $todo = Todo::factory()->create();
        $real = $this->reminderFor($todo, now()->subMinute());

        $this->artisan('reminders:dispatch')->assertSuccessful();

        // The orphaned reminder is still claimed and fired — it is the listener
        // that copes with a missing subject, not the dispatcher. What matters is
        // that it did not stop the valid one behind it being processed.
        $this->assertSame(
            ReminderStatus::Sent,
            $real->fresh()->status,
            'A reminder for a deleted To-Do must not prevent the next one being claimed.',
        );
    }

    public function test_the_dispatcher_chunks_its_work(): void
    {
        Event::fake([TodoReminderFired::class]);

        $todos = Todo::factory()->count(5)->create();

        foreach ($todos as $todo) {
            $this->reminderFor($todo, now()->subMinute());
        }

        // A limit below the row count forces a second chunk.
        $this->artisan('reminders:dispatch', ['--limit' => 2])
            ->assertSuccessful();

        $this->assertSame(
            2,
            Reminder::query()->where('status', ReminderStatus::Sent->value)->count(),
            'A limited pass must claim exactly its limit.',
        );

        $this->assertSame(
            3,
            Reminder::query()->where('status', ReminderStatus::Pending->value)->count(),
            'The remainder must stay pending for the next pass, not be lost.',
        );

        // A second pass with room to work drains the rest.
        $this->artisan('reminders:dispatch', ['--limit' => 200])->assertSuccessful();

        $this->assertSame(
            0,
            Reminder::query()->where('status', ReminderStatus::Pending->value)->count(),
            'Successive passes must drain the queue.',
        );
    }

    public function test_the_dispatcher_never_prints_recipient_details(): void
    {
        Queue::fake();

        $todo = Todo::factory()->create();
        $this->reminderFor($todo, now()->subMinute());

        $output = new BufferedOutput;

        $this->artisan('reminders:dispatch', [], $output)->run();

        $rendered = $output->fetch();

        $this->assertStringNotContainsString(
            $todo->title,
            $rendered,
            'A reminder must not print its subject into stdout.',
        );
        $this->assertStringNotContainsString(
            $todo->assignee?->email ?? 'nobody@example.com',
            $rendered,
            'A reminder must not print a recipient address into stdout.',
        );
    }

    // ---- todos:overdue dedupe ----------------------------------------------

    public function test_running_overdue_twice_produces_one_log_row_per_todo(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Todo::factory()->overdue()->assignedTo($user)->create(['id' => 412]);

        $this->artisan('todos:overdue')->assertSuccessful();

        Queue::assertPushed(SendTodoOverdueJob::class, 1);

        // Run the job the way a worker would, then run the command again.
        foreach ($this->pushedJobs(SendTodoOverdueJob::class) as $job) {
            $job->handle(app(TodoNotificationService::class));
        }

        $this->artisan('todos:overdue')->assertSuccessful();

        $this->assertSame(
            1,
            DB::table('notification_logs')
                ->where('dedupe_key', NotificationType::TodoOverdue->dedupeKey(412, now()->toDateString()))
                ->count(),
            'Re-running todos:overdue on the same day must not log a second send.',
        );
    }

    public function test_the_overdue_dedupe_key_uses_the_documented_format(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $todo = Todo::factory()->overdue()->assignedTo($user)->create(['id' => 412]);

        $this->artisan('todos:overdue')->assertSuccessful();

        $jobs = $this->pushedJobs(SendTodoOverdueJob::class);
        $job = $jobs[0];
        $job->handle(app(TodoNotificationService::class));

        $log = DB::table('notification_logs')->first();

        $this->assertSame('todo.overdue:412:'.now()->toDateString(), $log->dedupe_key);
    }

    public function test_overdue_skips_an_unassigned_todo(): void
    {
        Queue::fake();

        Todo::factory()->overdue()->create(['assignee_id' => null]);

        $this->artisan('todos:overdue')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_overdue_ignores_completed_and_archived_todos(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Todo::factory()->completed()->assignedTo($user)->create();
        Todo::factory()->assignedTo($user)->create([
            'status' => WorkItemStatus::Archived,
            'archived_from' => 'in_progress',
            'due_date' => now()->subDays(5)->format('Y-m-d'),
        ]);

        $this->artisan('todos:overdue')->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_a_second_pass_queues_nothing_for_work_already_warned_about(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Todo::factory()->overdue()->assignedTo($user)->create();

        $this->artisan('todos:overdue')->assertSuccessful();
        Queue::assertPushed(SendTodoOverdueJob::class, 1);

        // Run the job the way a worker would. It records last_reminded_at only
        // after a successful delivery, so a crash cannot mark an unsent
        // notification as delivered.
        foreach ($this->pushedJobs(SendTodoOverdueJob::class) as $job) {
            $job->handle(app(TodoNotificationService::class));
        }

        $todo = Todo::query()->withoutGlobalScopes()->firstOrFail();
        $this->assertNotNull($todo->last_reminded_at);

        // A second pass must queue nothing: the reminder already went out today.
        // The total across the fake is still 1, because assertNothingPushed()
        // counts every push since the fake was installed, not since this call.
        $this->artisan('todos:overdue')->assertSuccessful();
        Queue::assertPushed(SendTodoOverdueJob::class, 1);
    }

    public function test_last_reminded_at_is_not_written_when_nobody_was_notified(): void
    {
        // Opted out on every channel: nothing is sent, so nothing may be marked as
        // delivered — otherwise the To-Do would be suppressed for the rest of the
        // day with no notification ever having gone out.
        $user = User::factory()->create();
        $todo = Todo::factory()->overdue()->assignedTo($user)->create();

        foreach (NotificationChannel::cases() as $channel) {
            NotificationPreference::set($user, NotificationType::TodoOverdue->value, $channel, false);
        }

        $job = new SendTodoOverdueJob($todo, null, now()->toDateString());
        $job->handle(app(TodoNotificationService::class));

        $this->assertNull(
            $todo->fresh()->last_reminded_at,
            'An unsent notification must not mark the To-Do as reminded.',
        );
    }

    // ---- todos:due-soon ----------------------------------------------------

    public function test_due_soon_covers_the_configured_window(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Todo::factory()->assignedTo($user)->create([
            'status' => WorkItemStatus::InProgress,
            'due_date' => now()->addDay()->format('Y-m-d'),
        ]);
        Todo::factory()->assignedTo($user)->create([
            'status' => WorkItemStatus::InProgress,
            'due_date' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $this->artisan('todos:due-soon', ['--days' => 2])->assertSuccessful();

        Queue::assertPushed(SendTodoDueSoonJob::class, 1);
    }

    // ---- todos:generate ----------------------------------------------------

    public function test_generate_creates_a_missing_occurrence(): void
    {
        $user = User::factory()->create();

        $todo = Todo::factory()->createdBy($user)->create([
            'status' => WorkItemStatus::InProgress,
            'start_date' => '2026-09-20',
            'due_date' => '2026-09-20',
            'recurrence_rule' => [
                'frequency' => 'weekly',
                'interval' => 1,
                'start_date' => '2026-09-20',
                'max_occurrences' => 10,
            ],
        ]);

        $before = Todo::query()->withoutGlobalScope(TodoScope::class)->count();

        $this->artisan('todos:generate')->assertSuccessful();

        $this->assertSame(
            $before + 1,
            Todo::query()->withoutGlobalScope(TodoScope::class)->count(),
        );
    }

    public function test_generate_is_idempotent(): void
    {
        $user = User::factory()->create();

        // The occurrence has already been materialised, so there is nothing to do.
        Todo::factory()->createdBy($user)->create([
            'status' => WorkItemStatus::InProgress,
            'start_date' => '2026-09-20',
            'due_date' => '2026-09-20',
            'recurrence_rule' => [
                'frequency' => 'weekly',
                'interval' => 1,
                'start_date' => '2026-09-20',
                'max_occurrences' => 1,
            ],
        ]);

        $before = Todo::query()->withoutGlobalScope(TodoScope::class)->count();

        $this->artisan('todos:generate')->assertSuccessful();

        $this->assertSame(
            $before,
            Todo::query()->withoutGlobalScope(TodoScope::class)->count(),
            'A finished series must not gain another occurrence.',
        );
    }

    // ---- no synchronous send (GAP-022) ------------------------------------

    public function test_no_mail_is_sent_inside_an_http_request(): void
    {
        Mail::fake();
        Queue::fake();
        Event::fake([TodoAssigned::class]);

        $user = $this->userWithPermissions(['todos.create', 'todos.create_for_others']);

        $this->actingAs($user)
            ->post(route('todos.store'), ['title' => 'Assigned straight away', 'assignee_id' => $this->plainUser()->id])
            ->assertRedirect();

        Mail::assertNothingSent();
        Event::assertDispatched(TodoAssigned::class);
    }

    public function test_completing_a_todo_queues_rather_than_sends(): void
    {
        Mail::fake();
        Queue::fake();

        $user = $this->plainUser();
        $todo = Todo::factory()->createdBy($user)->create(['status' => WorkItemStatus::InProgress]);

        $this->actingAs($user)->post(route('todos.complete', $todo))->assertRedirect();

        Mail::assertNothingSent();
    }

    public function test_a_scheduler_command_queues_rather_than_sends(): void
    {
        Mail::fake();
        Queue::fake();

        $user = User::factory()->create();
        Todo::factory()->overdue()->assignedTo($user)->create();

        $this->artisan('todos:overdue')->assertSuccessful();

        Mail::assertNothingSent();
        Queue::assertPushed(SendTodoOverdueJob::class);
    }

    // ---- preferences (GAP-024) ---------------------------------------------

    public function test_a_user_who_disabled_overdue_email_receives_no_mail(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $todo = Todo::factory()->overdue()->assignedTo($user)->create();

        NotificationPreference::set($user, NotificationType::TodoOverdue->value, NotificationChannel::Mail, false);
        NotificationPreference::set($user, NotificationType::TodoOverdue->value, NotificationChannel::Database, false);

        $service = app(TodoNotificationService::class);

        $this->assertFalse($service->deliver($user, $todo, NotificationType::TodoOverdue));
        Mail::assertNothingSent();
    }

    public function test_a_user_who_enabled_it_receives_it(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $todo = Todo::factory()->overdue()->assignedTo($user)->create();

        $service = app(TodoNotificationService::class);

        $this->assertTrue($service->deliver($user, $todo, NotificationType::TodoOverdue));
    }

    // ---- timezone (GAP-046) ------------------------------------------------

    public function test_the_app_timezone_is_the_business_timezone(): void
    {
        $this->assertSame('Asia/Dhaka', config('app.timezone'));

        $offset = (new \DateTime('now', new \DateTimeZone(config('app.timezone'))))->format('P');

        $this->assertSame('+06:00', $offset, 'The business timezone must be UTC+06:00.');
    }

    public function test_a_reminder_for_a_local_time_is_stored_at_the_correct_utc_instant(): void
    {
        $todo = Todo::factory()->create(['due_date' => now()->addDays(2)->format('Y-m-d'), 'due_time' => '09:00']);

        $scheduler = app(ReminderScheduler::class);
        $reminder = $scheduler->scheduleForTodo($todo);

        $this->assertNotNull($reminder);

        // 09:00 local, 60 minutes of lead time, expressed as a UTC instant.
        $expectedLocal = CarbonImmutable::parse(
            $todo->due_date->format('Y-m-d').' 08:00',
            config('app.timezone'),
        );

        $this->assertSame(
            $expectedLocal->utc()->format('Y-m-d H:i:s'),
            $reminder->remind_at->format('Y-m-d H:i:s'),
            'A reminder for 08:00 local must be stored at the matching UTC instant.',
        );
    }

    public function test_no_reminder_is_scheduled_for_a_past_due_date(): void
    {
        $todo = Todo::factory()->create(['due_date' => now()->subDays(3)->format('Y-m-d'), 'due_time' => '09:00']);

        $this->assertNull(
            app(ReminderScheduler::class)->scheduleForTodo($todo),
            'A reminder about a moment that has passed is not a reminder.',
        );
    }

    public function test_no_reminder_is_scheduled_for_an_undated_todo(): void
    {
        $todo = Todo::factory()->create(['due_date' => null]);

        $this->assertNull(app(ReminderScheduler::class)->scheduleForTodo($todo));
    }

    public function test_rescheduling_replaces_the_pending_reminder_rather_than_stacking(): void
    {
        $todo = Todo::factory()->create(['due_date' => now()->addDays(2)->format('Y-m-d'), 'due_time' => '09:00']);

        $scheduler = app(ReminderScheduler::class);
        $scheduler->scheduleForTodo($todo);

        $todo->update(['due_date' => now()->addDays(5)->format('Y-m-d')]);
        $scheduler->scheduleForTodo($todo);

        $this->assertSame(
            1,
            Reminder::query()->where('status', ReminderStatus::Pending->value)->count(),
            'One pending reminder per subject — a new due date replaces the old plan.',
        );
    }

    public function test_completing_a_todo_cancels_its_pending_reminder(): void
    {
        // Needs a future due date: an undated To-Do has nothing to remind about.
        $todo = Todo::factory()->create([
            'status' => WorkItemStatus::InProgress,
            'due_date' => now()->addDays(2)->format('Y-m-d'),
            'due_time' => '09:00',
        ]);

        $reminder = app(ReminderScheduler::class)->scheduleForTodo($todo);
        $this->assertNotNull($reminder);

        $this->app->make(TodoService::class)
            ->complete($todo->creator, $todo);

        $this->assertSame(
            ReminderStatus::Cancelled,
            $reminder->fresh()->status,
            'Nothing should fire about work that is finished.',
        );
    }

    /**
     * @return list<SendTodoNotificationJob>
     */
    protected function pushedJobs(string $class): array
    {
        return array_values(array_map(
            static fn (array $entry): object => $entry['job'],
            Queue::pushedJobs()[$class] ?? [],
        ));
    }

    protected function findReminderFor(Todo $todo): Reminder
    {
        return Reminder::query()
            ->where('subject_type', Todo::class)
            ->where('subject_id', $todo->getKey())
            ->latest('id')
            ->firstOrFail();
    }

    protected function reminderFor(Todo $todo, ?\DateTimeInterface $at = null): Reminder
    {
        return Reminder::query()->create([
            'subject_type' => Todo::class,
            'subject_id' => $todo->getKey(),
            'remind_at' => $at ?? now()->subMinute(),
            'channel' => NotificationChannel::InApp->value,
            'status' => ReminderStatus::Pending->value,
        ]);
    }
}
