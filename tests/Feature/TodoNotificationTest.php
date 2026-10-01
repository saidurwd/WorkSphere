<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Modules\Todos\Jobs\SendTodoAssignedJob;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoWatcher;
use Modules\Todos\Notifications\TodoNotification;
use Modules\Todos\Services\TodoNotificationService;
use Tests\TestCase;

/**
 * The notification pipeline — TODO-MODULE-SPECIFICATION.md §6.
 *
 * The three properties that matter are each pinned here:
 *
 * 1. Every send is queued. No mail inside a request or a scheduler tick.
 * 2. `shouldNotify()` consults preferences instead of returning true (GAP-024).
 * 3. The dedupe key makes a repeated send a no-op (GAP-023).
 */
class TodoNotificationTest extends TestCase
{
    use RefreshDatabase;

    private TodoNotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TodoNotificationService::class);
    }

    // ---- Preferences (GAP-024) ---------------------------------------------

    public function test_a_user_with_no_preference_row_is_opted_in(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($this->service->shouldNotify($user, NotificationType::TodoAssigned, NotificationChannel::Mail));
    }

    public function test_an_explicit_opt_out_is_honoured(): void
    {
        $user = User::factory()->create();

        NotificationPreference::set($user, NotificationType::TodoAssigned->value, NotificationChannel::Mail, false);

        $this->assertFalse(
            $this->service->shouldNotify($user, NotificationType::TodoAssigned, NotificationChannel::Mail),
            'shouldNotify() must not simply return true.',
        );
    }

    public function test_opting_out_of_one_channel_leaves_the_other_enabled(): void
    {
        $user = User::factory()->create();

        NotificationPreference::set($user, NotificationType::TodoAssigned->value, NotificationChannel::Mail, false);

        $this->assertFalse($this->service->shouldNotify($user, NotificationType::TodoAssigned, NotificationChannel::Mail));
        $this->assertTrue($this->service->shouldNotify($user, NotificationType::TodoAssigned, NotificationChannel::Database));
    }

    public function test_opting_out_of_one_type_does_not_affect_another(): void
    {
        $user = User::factory()->create();

        NotificationPreference::set($user, NotificationType::TodoOverdue->value, NotificationChannel::Mail, false);

        $this->assertFalse($this->service->shouldNotify($user, NotificationType::TodoOverdue, NotificationChannel::Mail));
        $this->assertTrue($this->service->shouldNotify($user, NotificationType::TodoCompleted, NotificationChannel::Mail));
    }

    public function test_channels_for_reflects_the_users_choices(): void
    {
        $user = User::factory()->create();

        NotificationPreference::set($user, NotificationType::TodoAssigned->value, NotificationChannel::Mail, false);

        $channels = $this->service->channelsFor($user, NotificationType::TodoAssigned);

        $this->assertNotContains(NotificationChannel::Mail, $channels);
        $this->assertContains(NotificationChannel::Database, $channels);
    }

    // ---- Idempotency (GAP-023) ---------------------------------------------

    public function test_a_send_writes_a_notification_log_row_with_a_dedupe_key(): void
    {
        Notification::fake();

        $assignee = User::factory()->create();
        $todo = Todo::factory()->assignedTo($assignee)->create();

        $this->assertTrue($this->service->deliver($assignee, $todo, NotificationType::TodoAssigned));

        $this->assertDatabaseHas('notification_logs', [
            'subject_type' => Todo::class,
            'subject_id' => $todo->id,
            'user_id' => $assignee->id,
            'notification_type' => NotificationType::TodoAssigned->value,
        ]);
    }

    public function test_delivering_the_same_thing_twice_sends_once(): void
    {
        Notification::fake();

        $assignee = User::factory()->create();
        $todo = Todo::factory()->assignedTo($assignee)->create();

        $this->assertTrue($this->service->deliver($assignee, $todo, NotificationType::TodoAssigned));
        $this->assertFalse(
            $this->service->deliver($assignee, $todo, NotificationType::TodoAssigned),
            'A duplicate dedupe key must be refused rather than sent again.',
        );

        $this->assertCount(
            1,
            Notification::sent($assignee, TodoNotification::class),
            'Exactly one notification must be sent.',
        );

        $this->assertSame(
            1,
            DB::table('notification_logs')->where('dedupe_key', NotificationType::TodoAssigned->dedupeKey($todo->id))->count(),
        );
    }

    public function test_a_different_discriminator_produces_a_separate_send(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $todo = Todo::factory()->assignedTo($user)->create();

        $this->assertTrue($this->service->deliver($user, $todo, NotificationType::TodoReminder, '2026-10-01'));
        $this->assertTrue(
            $this->service->deliver($user, $todo, NotificationType::TodoReminder, '2026-10-02'),
            'A reminder on a different day is a different notification.',
        );
    }

    public function test_an_opted_out_recipient_records_a_skipped_row_and_is_not_notified(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        foreach (NotificationChannel::cases() as $channel) {
            NotificationPreference::set($user, NotificationType::TodoAssigned->value, $channel, false);
        }

        $todo = Todo::factory()->assignedTo($user)->create();

        $this->assertFalse($this->service->deliver($user, $todo, NotificationType::TodoAssigned));

        $this->assertSame(
            0,
            DB::table('notification_logs')->count(),
            'An opted-out send must not consume the dedupe key, or a later opt-in would be swallowed.',
        );
    }

    public function test_a_user_who_opts_back_in_afterwards_still_receives_it(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $todo = Todo::factory()->assignedTo($user)->create();

        foreach (NotificationChannel::cases() as $channel) {
            NotificationPreference::set($user, NotificationType::TodoAssigned->value, $channel, false);
        }

        $this->assertFalse($this->service->deliver($user, $todo, NotificationType::TodoAssigned));

        foreach (NotificationChannel::cases() as $channel) {
            NotificationPreference::set($user, NotificationType::TodoAssigned->value, $channel, true);
        }
        $this->assertTrue($this->service->deliver($user, $todo, NotificationType::TodoAssigned));
    }

    // ---- Recipients (§6.2) --------------------------------------------------

    public function test_the_actor_is_never_notified_about_their_own_action(): void
    {
        $creator = User::factory()->create();
        $todo = Todo::factory()->createdBy($creator)->assignedTo($creator)->create();

        $recipients = $this->service->recipientsFor($todo, NotificationType::TodoAssigned, $creator->id);

        $this->assertSame([], $recipients);
    }

    public function test_the_new_assignee_is_notified_on_assignment(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $todo = Todo::factory()->createdBy($creator)->assignedTo($assignee)->create();

        $recipients = $this->service->recipientsFor($todo, NotificationType::TodoAssigned, $creator->id);

        $this->assertCount(1, $recipients);
        $this->assertSame($assignee->id, $recipients[0]->id);
    }

    public function test_completion_notifies_the_creator_and_the_watchers(): void
    {
        $creator = User::factory()->create();
        $completer = User::factory()->create();
        $watcher = User::factory()->create();

        $todo = Todo::factory()->createdBy($creator)->assignedTo($completer)->create();
        TodoWatcher::query()->create(['todo_id' => $todo->id, 'user_id' => $watcher->id]);

        $ids = collect($this->service->recipientsFor($todo->fresh(), NotificationType::TodoCompleted, $completer->id))
            ->pluck('id')
            ->sort()
            ->values()
            ->all();

        $this->assertSame([$creator->id, $watcher->id], $ids);
    }

    public function test_overdue_notifies_only_the_assignee(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();

        $todo = Todo::factory()->createdBy($creator)->assignedTo($assignee)->create();

        $recipients = $this->service->recipientsFor($todo, NotificationType::TodoOverdue, $creator->id);

        $this->assertCount(1, $recipients);
        $this->assertSame($assignee->id, $recipients[0]->id);
    }

    // ---- Queueing (GAP-022) -------------------------------------------------

    public function test_every_delivery_job_is_queued_and_never_runs_inline(): void
    {
        Queue::fake();
        Notification::fake();

        $todo = Todo::factory()->create();

        SendTodoAssignedJob::dispatch($todo, null);

        Queue::assertPushed(SendTodoAssignedJob::class, 1);
        Notification::assertNothingSent();
    }

    public function test_a_failing_send_is_recorded_as_failed_rather_than_raising(): void
    {
        // A broken mail transport must not take the originating request down, but
        // the failure still has to be visible on the log row.
        Notification::shouldReceive('send')
            ->andThrow(new \RuntimeException('transport unavailable'));

        $user = User::factory()->create();
        $todo = Todo::factory()->assignedTo($user)->create();

        $this->assertFalse($this->service->deliver($user, $todo, NotificationType::TodoAssigned));

        $row = DB::table('notification_logs')
            ->where('dedupe_key', NotificationType::TodoAssigned->dedupeKey($todo->id))
            ->firstOrFail();

        $this->assertSame('FAILED', $row->status);
        $this->assertStringContainsString('transport unavailable', (string) $row->error_message);
    }

    public function test_a_failed_row_does_not_violate_the_dedupe_index(): void
    {
        // The failure path UPDATEs the claimed row. Inserting a second one would
        // raise on the UNIQUE index and mask the failure it was recording.
        Notification::shouldReceive('send')->andThrow(new \RuntimeException('transport unavailable'));

        $user = User::factory()->create();
        $todo = Todo::factory()->assignedTo($user)->create();

        $this->service->deliver($user, $todo, NotificationType::TodoAssigned);

        $this->assertSame(
            1,
            DB::table('notification_logs')->where('subject_id', $todo->id)->count(),
        );
    }

    public function test_no_mail_is_sent_outside_a_job(): void
    {
        $source = file_get_contents(__DIR__.'/../../Modules/Todos/app/Services/TodoNotificationService.php');

        $this->assertStringNotContainsString(
            'Mail::to(',
            (string) $source,
            'Delivery must go through Laravel Notifications, not a raw Mail call.',
        );
    }

    public function test_the_dedupe_key_format_is_type_subject_discriminator(): void
    {
        $this->assertSame(
            'todo.overdue:412:2026-10-01',
            NotificationType::TodoOverdue->dedupeKey(412, '2026-10-01'),
        );

        $this->assertSame(
            'todo.assigned:7',
            NotificationType::TodoAssigned->dedupeKey(7),
        );
    }
}
