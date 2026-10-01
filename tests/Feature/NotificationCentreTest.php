<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Todos\Notifications\TodoNotification;
use Tests\TestCase;

/**
 * GAP-021: the navbar reads `notifications` instead of three per-module
 * delivery-log tables.
 *
 * The properties that actually matter are behavioural: "unread" means a row with
 * no `read_at` (the old merge could never express that), the count is a real
 * count rather than "how many rows are in the last 10 of each of three logs",
 * and one user's notifications are never another's.
 */
class NotificationCentreTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_notifications_index_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('notifications.index'))->assertOk();
    }

    public function test_a_notification_appears_in_the_index(): void
    {
        $user = User::factory()->create();

        $this->notify($user, 'Ship the release', 1);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Ship the release');
    }

    public function test_the_unread_count_reflects_rows_without_read_at(): void
    {
        $user = User::factory()->create();

        $this->notify($user, 'One', 1);
        $this->notify($user, 'Two', 2);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('2 unread');
    }

    public function test_a_read_notification_drops_out_of_the_unread_count(): void
    {
        $user = User::factory()->create();

        $first = $this->notify($user, 'One', 1);
        $this->notify($user, 'Two', 2);

        $first->markAsRead();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('1 unread');
    }

    public function test_marking_one_as_read_only_affects_that_one(): void
    {
        $user = User::factory()->create();

        $first = $this->notify($user, 'One', 1);
        $second = $this->notify($user, 'Two', 2);

        $this->actingAs($user)
            ->post(route('notifications.read', $first->id))
            ->assertRedirect();

        $this->assertNotNull($first->fresh()->read_at);
        $this->assertNull($second->fresh()->read_at);
    }

    public function test_marking_all_as_read_clears_the_count(): void
    {
        $user = User::factory()->create();

        $this->notify($user, 'One', 1);
        $this->notify($user, 'Two', 2);

        $this->actingAs($user)->post(route('notifications.read-all'))->assertRedirect();

        $this->assertSame(
            0,
            $user->unreadNotifications()->count(),
        );
    }

    public function test_a_user_cannot_read_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $theirs = $this->notify($owner, 'Private', 1);

        // Scoped by notifiable_id, so a guessed UUID is not enough.
        $this->actingAs($attacker)
            ->post(route('notifications.read', $theirs->id))
            ->assertRedirect();

        $this->assertNull(
            $theirs->fresh()->read_at,
            'Marking a notification read must only affect its owner.',
        );
    }

    public function test_a_notification_of_another_type_is_not_listed(): void
    {
        $user = User::factory()->create();

        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => 'SomeOtherModule\\OtherNotification',
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['title' => 'Not ours']),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee('Not ours');
    }

    public function test_an_anonymous_visitor_is_redirected(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    public function test_the_navbar_reads_only_the_notifications_table(): void
    {
        $source = (string) file_get_contents(base_path('resources/views/components/navbar.blade.php'));

        // The old implementation fanned out across three log tables on every
        // page render. Leaving that in place would undo the whole change.
        foreach ([
            'Obligations\\Models\\NotificationLog',
            'Tasks\\Models\\TaskNotificationLog',
            'Meetings\\Models\\MeetingNotificationLog',
        ] as $legacy) {
            $this->assertStringNotContainsString($legacy, $source);
        }

        $this->assertStringContainsString('unreadNotifications', $source);
    }

    public function test_the_navbar_renders_with_no_notifications_at_all(): void
    {
        $user = User::factory()->create();

        // "Must render identically for users with no notifications."
        $this->actingAs($user)->get(route('todos.index'))->assertOk();
    }

    public function test_the_navbar_shows_an_unread_badge(): void
    {
        $user = User::factory()->create();
        $this->notify($user, 'Heads up', 1);

        $this->actingAs($user)
            ->get(route('todos.index'))
            ->assertOk()
            ->assertSee('Heads up');
    }

    public function test_a_notification_links_through_a_route_not_a_stored_url(): void
    {
        $user = User::factory()->create();

        // A URL stored in the payload is attacker-controllable; the presenter must
        // build the link from a route instead.
        DB::table('notifications')->insert([
            'id' => (string) Str::uuid(),
            'type' => TodoNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'title' => 'Open redirect attempt',
                'url' => 'https://evil.example.com',
                'type' => NotificationType::TodoAssigned->value,
            ]),
            'read_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee('https://evil.example.com');
    }

    protected function notify(User $user, string $title, int $todoId): DatabaseNotification
    {
        return $user->notifications()->create([
            // Laravel generates the UUID during Notification::send(); inserting
            // directly has to supply it, or the NOT NULL primary key fails.
            'id' => (string) Str::uuid(),
            'type' => TodoNotification::class,
            'data' => [
                'todo_id' => $todoId,
                'title' => $title,
                'type' => NotificationType::TodoAssigned->value,
            ],
        ]);
    }
}
