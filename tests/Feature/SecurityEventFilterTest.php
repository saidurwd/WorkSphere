<?php

namespace Tests\Feature;

use App\Filament\Resources\SecurityEvents\Pages\ListSecurityEvents;
use App\Models\LoginLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The security-event listing and its event filter.
 *
 * The Blade controller built the distinct event list into `$events` and then
 * overwrote it with the paginator, so the filter dropdown iterated log rows
 * instead of event names. The Filament filter takes its options from the distinct
 * event column, which these tests pin.
 */
class SecurityEventFilterTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_the_listing_renders_the_matching_rows(): void
    {
        $this->actingAs($this->superAdmin());

        $failed = LoginLog::query()->create(['email' => 'failed@example.com', 'event' => LoginLog::FAILED, 'attempted_at' => now()]);

        Livewire::test(ListSecurityEvents::class)
            ->assertCanSeeTableRecords([$failed])
            ->assertSee('failed@example.com');
    }

    public function test_the_page_does_not_error_when_there_are_no_events(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ListSecurityEvents::class)->assertOk();
    }

    public function test_the_event_filter_narrows_the_listing(): void
    {
        $this->actingAs($this->superAdmin());

        $failed = LoginLog::query()->create(['email' => 'a@example.com', 'event' => LoginLog::FAILED, 'attempted_at' => now()]);
        $locked = LoginLog::query()->create(['email' => 'b@example.com', 'event' => LoginLog::LOCKED, 'attempted_at' => now()]);

        Livewire::test(ListSecurityEvents::class)
            ->filterTable('event', LoginLog::FAILED)
            ->assertCanSeeTableRecords([$failed])
            ->assertCanNotSeeTableRecords([$locked]);
    }
}
