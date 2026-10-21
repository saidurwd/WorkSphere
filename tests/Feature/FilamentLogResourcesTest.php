<?php

namespace Tests\Feature;

use App\Filament\Resources\ActivityLogs\Pages\ListActivityLogs;
use App\Filament\Resources\ActivityLogs\Pages\ViewActivityLog;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Filament\Resources\AuditLogs\Pages\ViewAuditLog;
use App\Filament\Resources\LoginLogs\LoginLogResource;
use App\Filament\Resources\LoginLogs\Pages\ListLoginLogs;
use App\Filament\Resources\LoginLogs\Pages\ViewLoginLog;
use App\Filament\Resources\SecurityEvents\Pages\ListSecurityEvents;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\LoginLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The Filament replacements for the Blade log viewers.
 */
class FilamentLogResourcesTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_each_log_lists_and_shows_its_records(): void
    {
        $this->actingAs($this->superAdmin());

        $activity = ActivityLog::factory()->create();
        $audit = AuditLog::factory()->create();
        $login = LoginLog::factory()->create();

        Livewire::test(ListActivityLogs::class)->assertOk()->assertCanSeeTableRecords([$activity]);
        Livewire::test(ListAuditLogs::class)->assertOk()->assertCanSeeTableRecords([$audit]);
        Livewire::test(ListLoginLogs::class)->assertOk()->assertCanSeeTableRecords([$login]);

        Livewire::test(ViewActivityLog::class, ['record' => $activity->getKey()])->assertOk();
        Livewire::test(ViewAuditLog::class, ['record' => $audit->getKey()])->assertOk();
        Livewire::test(ViewLoginLog::class, ['record' => $login->getKey()])->assertOk();
    }

    public function test_security_events_only_list_failed_and_locked_attempts(): void
    {
        $this->actingAs($this->superAdmin());

        $failed = LoginLog::factory()->create(['event' => LoginLog::FAILED]);
        $locked = LoginLog::factory()->create(['event' => LoginLog::LOCKED]);
        $signedIn = LoginLog::factory()->create(['event' => LoginLog::LOGIN]);

        Livewire::test(ListSecurityEvents::class)
            ->assertCanSeeTableRecords([$failed, $locked])
            ->assertCanNotSeeTableRecords([$signedIn]);
    }

    public function test_the_log_viewers_are_read_only(): void
    {
        $this->actingAs($this->superAdmin());

        $this->assertFalse(LoginLogResource::canCreate());
        $this->assertFalse(LoginLogResource::canEdit(LoginLog::factory()->create()));
        $this->assertFalse(LoginLogResource::canDelete(LoginLog::factory()->create()));
    }

    public function test_a_non_admin_cannot_open_the_logs(): void
    {
        $this->actingAs($this->userWithPermissions(['activity.view']));

        $this->get('/panel/audit-logs')->assertForbidden();
        $this->get('/panel/login-logs')->assertForbidden();
    }
}
