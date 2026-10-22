<?php

namespace Tests\Feature;

use App\Filament\Auth\Login;
use App\Models\LoginLog;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Every sign-in outcome leaves a row in `login_logs`, and repeated failures are
 * throttled. Both live on the one sign-in page, App\Filament\Auth\Login.
 */
class LoginFailureLoggingTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('workspace');

        RateLimiter::clear('nobody@example.com|127.0.0.1');
    }

    private function attempt(string $email, string $password)
    {
        return Livewire::test(Login::class)
            ->fillForm(['email' => $email, 'password' => $password])
            ->call('authenticate');
    }

    public function test_a_failed_login_writes_a_login_log_row_with_request_context(): void
    {
        User::factory()->create(['email' => 'nobody@example.com']);

        $this->attempt('nobody@example.com', 'definitely-not-the-password');

        $log = LoginLog::query()->where('email', 'nobody@example.com')->firstOrFail();

        $this->assertSame(LoginLog::FAILED, $log->event);
        $this->assertNotNull($log->ip_address);
        $this->assertNotNull($log->attempted_at);
        $this->assertGuest();
    }

    public function test_repeated_failures_are_throttled_and_recorded_as_locked(): void
    {
        User::factory()->create(['email' => 'nobody@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->attempt('nobody@example.com', 'wrong-password');
        }

        $this->assertSame(5, LoginLog::query()->where('email', 'nobody@example.com')->count(), 'Each rejected attempt leaves exactly one row.');

        // The sixth is refused before the password is even checked, and says so.
        $this->attempt('nobody@example.com', 'wrong-password')->assertNotified();

        $this->assertDatabaseHas('login_logs', ['email' => 'nobody@example.com', 'event' => LoginLog::LOCKED]);
        $this->assertGuest();
    }

    public function test_the_correct_password_stays_locked_out_once_the_limit_is_reached(): void
    {
        User::factory()->create(['email' => 'nobody@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->attempt('nobody@example.com', 'wrong-password');
        }

        $this->attempt('nobody@example.com', 'password');

        $this->assertGuest();
    }

    public function test_one_persons_lockout_does_not_lock_out_another(): void
    {
        User::factory()->create(['email' => 'alice@example.com']);
        User::factory()->create(['email' => 'bob@example.com']);

        for ($i = 0; $i < 6; $i++) {
            $this->attempt('alice@example.com', 'wrong');
        }

        // Bob's own attempt is judged on its own: a wrong password is a failure,
        // not a lockout.
        $this->attempt('bob@example.com', 'wrong');

        $this->assertDatabaseHas('login_logs', ['email' => 'bob@example.com', 'event' => LoginLog::FAILED]);
        $this->assertDatabaseMissing('login_logs', ['email' => 'bob@example.com', 'event' => LoginLog::LOCKED]);
    }

    public function test_a_correct_password_signs_in_and_logs_it_without_a_failure(): void
    {
        $user = $this->superAdmin();
        $user->update(['email' => 'someone@example.com']);

        $this->attempt('someone@example.com', 'password')->assertHasNoFormErrors();

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertDatabaseHas('login_logs', ['email' => 'someone@example.com', 'event' => LoginLog::LOGIN]);
        $this->assertDatabaseMissing('login_logs', ['event' => LoginLog::FAILED]);
    }

    public function test_an_inactive_account_cannot_sign_in(): void
    {
        $user = $this->superAdmin();
        $user->update(['email' => 'blocked@example.com', 'status' => 'inactive']);

        $this->attempt('blocked@example.com', 'password');

        $this->assertGuest();
        $this->assertDatabaseHas('login_logs', ['email' => 'blocked@example.com', 'event' => LoginLog::FAILED]);
    }

    public function test_signing_out_is_logged(): void
    {
        $user = $this->superAdmin();

        $this->actingAs($user)->post(route('logout'))->assertRedirect();

        $this->assertDatabaseHas('login_logs', ['user_id' => $user->id, 'event' => LoginLog::LOGOUT]);
    }

    public function test_the_old_addresses_lead_to_the_one_sign_in_page(): void
    {
        $this->get('/login')->assertRedirect('/workspace/login');
        $this->get('/')->assertRedirect('/workspace');
        $this->get('/workspace/login')->assertOk();
        $this->get('/admin/login')->assertOk();
    }

    public function test_password_reset_is_offered_from_the_sign_in_page(): void
    {
        $this->get('/workspace/login')->assertOk()->assertSee('Forgot password?');
        $this->get('/workspace/password-reset/request')->assertOk();
    }
}
