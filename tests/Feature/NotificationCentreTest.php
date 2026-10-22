<?php

namespace Tests\Feature;

use App\Enums\NotificationType;
use App\Filament\Workspace\Pages\Notifications;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Modules\Todos\Notifications\TodoNotification;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The notification centre reads Laravel's `notifications` table.
 *
 * The properties that matter are behavioural: "unread" means a row with no
 * `read_at`, the count is a real count, one user's notifications are never
 * another's, and a link is built from a route rather than a stored URL.
 */
class NotificationCentreTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('workspace');
    }

    public function test_the_page_renders_with_no_notifications_at_all(): void
    {
        $this->actingAs($this->plainUser());

        Livewire::test(Notifications::class)->assertOk();
        $this->assertNull(Notifications::getNavigationBadge());
    }

    public function test_a_notification_appears_and_counts_as_unread(): void
    {
        $user = $this->plainUser();
        $one = $this->notify($user, 'Ship the release', 1);
        $this->notify($user, 'Second thing', 2);
        $this->actingAs($user);

        Livewire::test(Notifications::class)
            ->assertCanSeeTableRecords([$one])
            ->assertSee('Ship the release');

        $this->assertSame('2', Notifications::getNavigationBadge());
    }

    public function test_marking_one_as_read_only_affects_that_one(): void
    {
        $user = $this->plainUser();
        $first = $this->notify($user, 'One', 1);
        $second = $this->notify($user, 'Two', 2);
        $this->actingAs($user);

        Livewire::test(Notifications::class)->callTableAction('markRead', $first);

        $this->assertNotNull($first->fresh()->read_at);
        $this->assertNull($second->fresh()->read_at);
        $this->assertSame('1', Notifications::getNavigationBadge());
    }

    public function test_marking_all_as_read_clears_the_count(): void
    {
        $user = $this->plainUser();
        $this->notify($user, 'One', 1);
        $this->notify($user, 'Two', 2);
        $this->actingAs($user);

        Livewire::test(Notifications::class)->callAction('markAllRead');

        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertNull(Notifications::getNavigationBadge());
    }

    public function test_the_unread_filter_separates_read_from_unread(): void
    {
        $user = $this->plainUser();
        $unread = $this->notify($user, 'Unread one', 1);
        $read = $this->notify($user, 'Read one', 2);
        $read->markAsRead();
        $this->actingAs($user);

        Livewire::test(Notifications::class)
            ->filterTable('unread', true)
            ->assertCanSeeTableRecords([$unread])
            ->assertCanNotSeeTableRecords([$read]);
    }

    public function test_a_user_never_sees_or_clears_another_users_notifications(): void
    {
        $owner = $this->plainUser();
        $attacker = $this->plainUser();
        $theirs = $this->notify($owner, 'Private', 1);
        $this->notify($attacker, 'Mine', 2);
        $this->actingAs($attacker);

        Livewire::test(Notifications::class)
            ->assertCanNotSeeTableRecords([$theirs])
            ->assertDontSee('Private')
            ->callAction('markAllRead');

        $this->assertNull($theirs->fresh()->read_at, 'Marking read must only affect the owner.');
    }

    public function test_a_notification_of_another_type_is_not_listed(): void
    {
        $user = $this->plainUser();

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
        $this->actingAs($user);

        Livewire::test(Notifications::class)->assertDontSee('Not ours');
        $this->assertNull(Notifications::getNavigationBadge());
    }

    public function test_an_anonymous_visitor_is_sent_to_sign_in(): void
    {
        $this->get('/notifications')->assertRedirect('/login');
    }

    public function test_a_notification_links_through_a_route_not_a_stored_url(): void
    {
        $user = $this->plainUser();

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
        $this->actingAs($user);

        Livewire::test(Notifications::class)
            ->assertSee('Open redirect attempt')
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
