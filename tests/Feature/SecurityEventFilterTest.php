<?php

namespace Tests\Feature;

use App\Models\LoginLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * SecurityEventController built the distinct event list into `$events` and then
 * overwrote it with the paginator, so the filter dropdown iterated log rows
 * instead of event names. The two variables are now separate.
 */
class SecurityEventFilterTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_the_event_dropdown_lists_event_names_not_log_rows(): void
    {
        $this->actingAs($this->superAdmin());

        LoginLog::query()->create([
            'email' => 'failed@example.com',
            'event' => LoginLog::FAILED,
            'attempted_at' => now(),
        ]);

        $response = $this->get(route('admin.security-events.index'));

        $response->assertOk();
        $response->assertSee('<option value="failed"', false);
        $response->assertDontSee('<option value="failed@example.com"', false);
    }

    public function test_the_listing_renders_the_matching_rows(): void
    {
        $this->actingAs($this->superAdmin());

        LoginLog::query()->create([
            'email' => 'failed@example.com',
            'event' => LoginLog::FAILED,
            'attempted_at' => now(),
        ]);

        $this->get(route('admin.security-events.index'))
            ->assertOk()
            ->assertSee('failed@example.com');
    }

    public function test_the_page_does_not_error_when_there_are_no_events(): void
    {
        $this->actingAs($this->superAdmin());

        $this->get(route('admin.security-events.index'))->assertOk();
    }

    public function test_the_event_filter_narrows_the_listing(): void
    {
        $this->actingAs($this->superAdmin());

        LoginLog::query()->create(['email' => 'a@example.com', 'event' => LoginLog::FAILED, 'attempted_at' => now()]);
        LoginLog::query()->create(['email' => 'b@example.com', 'event' => LoginLog::LOGIN, 'attempted_at' => now()]);

        $response = $this->get(route('admin.security-events.index', ['event' => LoginLog::FAILED]));

        $response->assertOk();
        $response->assertSee('a@example.com');
        $response->assertDontSee('b@example.com');
    }
}
