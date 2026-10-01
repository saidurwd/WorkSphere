<?php

namespace Tests\Feature;

use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * A failed sign-in must leave a row in `login_logs`. The failure path resolves
 * App\Models\LoginLog and App\Services\LoginLogService, so a missing import
 * there would surface as a fatal error on the exact path an attacker triggers.
 */
class LoginFailureLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::clear('nobody@example.com|127.0.0.1');
    }

    public function test_a_failed_login_writes_a_login_log_row(): void
    {
        User::factory()->create(['email' => 'nobody@example.com']);

        $this->post(route('login'), [
            'email' => 'nobody@example.com',
            'password' => 'definitely-not-the-password',
        ]);

        $this->assertDatabaseHas('login_logs', [
            'email' => 'nobody@example.com',
            'event' => LoginLog::FAILED,
        ]);
    }

    public function test_a_failed_login_does_not_authenticate_the_user(): void
    {
        User::factory()->create(['email' => 'nobody@example.com']);

        $this->post(route('login'), [
            'email' => 'nobody@example.com',
            'password' => 'definitely-not-the-password',
        ]);

        $this->assertGuest();
    }

    public function test_the_failed_login_row_records_request_context(): void
    {
        User::factory()->create(['email' => 'nobody@example.com']);

        $this->post(route('login'), [
            'email' => 'nobody@example.com',
            'password' => 'definitely-not-the-password',
        ]);

        $log = LoginLog::query()->where('email', 'nobody@example.com')->firstOrFail();

        $this->assertNotNull($log->ip_address);
        $this->assertNotNull($log->attempted_at);
    }

    public function test_repeated_failures_are_throttled_and_recorded_as_locked(): void
    {
        User::factory()->create(['email' => 'nobody@example.com']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), [
                'email' => 'nobody@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $this->assertSame(
            5,
            LoginLog::query()->where('email', 'nobody@example.com')->count(),
            'Each rejected attempt should leave exactly one row.',
        );

        $this->post(route('login'), [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertDatabaseHas('login_logs', [
            'email' => 'nobody@example.com',
            'event' => LoginLog::LOCKED,
        ]);
    }

    public function test_a_correct_password_authenticates_and_does_not_log_a_failure(): void
    {
        $user = User::factory()->create(['email' => 'someone@example.com']);

        $this->post(route('login'), [
            'email' => 'someone@example.com',
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseMissing('login_logs', ['event' => LoginLog::FAILED]);
    }
}
