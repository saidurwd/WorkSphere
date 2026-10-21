<?php

namespace Tests\Feature;

use Database\Factories\ObligationFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Obligations\Filament\Pages\ObligationCalendar;
use Modules\Obligations\Filament\Pages\ObligationOverview;
use Modules\Obligations\Filament\Pages\ObligationReports;
use Modules\Obligations\Filament\Resources\NotificationLogs\Pages\ListObligationNotificationLogs;
use Modules\Obligations\Filament\Resources\Vendors\Pages\CreateVendor;
use Modules\Obligations\Filament\Resources\Vendors\Pages\EditVendor;
use Modules\Obligations\Filament\Resources\Vendors\Pages\ListVendors;
use Modules\Obligations\Models\NotificationLog;
use Modules\Obligations\Models\Vendor;
use Modules\Tasks\Models\Task;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Obligation vendors and the obligation notification log in the workspace panel.
 */
class FilamentObligationAdminScreensTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('workspace');
    }

    public function test_vendors_are_managed_with_the_manage_settings_permission(): void
    {
        $this->actingAs($this->userWithPermissions(['obligation.manage_settings']));

        Livewire::test(CreateVendor::class)
            ->fillForm(['vendor_name' => 'Acme Fire Safety', 'email' => 'sales@acme.test', 'status' => 'active'])
            ->call('create')
            ->assertHasNoFormErrors();

        $vendor = Vendor::query()->where('vendor_name', 'Acme Fire Safety')->firstOrFail();

        Livewire::test(EditVendor::class, ['record' => $vendor->getKey()])
            ->fillForm(['phone' => '0123'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('0123', $vendor->fresh()->phone);

        Livewire::test(ListVendors::class)->callTableAction('delete', $vendor);
        $this->assertModelMissing($vendor);
    }

    public function test_vendor_validation(): void
    {
        $this->actingAs($this->userWithPermissions(['obligation.manage_settings']));

        Livewire::test(CreateVendor::class)
            ->fillForm(['vendor_name' => null, 'email' => 'not-an-email', 'website' => 'nope', 'status' => 'active'])
            ->call('create')
            ->assertHasFormErrors(['vendor_name' => 'required', 'email' => 'email', 'website' => 'url']);
    }

    public function test_vendor_contact_details_are_not_open_to_every_signed_in_user(): void
    {
        Vendor::factory()->create();

        $this->actingAs($this->plainUser());
        $this->get('/workspace/vendors')->assertForbidden();

        $this->actingAs($this->userWithPermissions(['obligation.view']));
        $this->get('/workspace/vendors')->assertOk();
        $this->get('/workspace/vendors/create')->assertForbidden();
    }

    private function log(array $attributes = []): NotificationLog
    {
        $log = (new NotificationLog)->forceFill($attributes + [
            'obligation_id' => ObligationFactory::new()->create()->id,
            'channel' => 'mail',
            'notification_type' => 'expiry',
            'status' => 'sent',
            'subject' => 'Your licence expires',
        ]);
        $log->save();

        return $log;
    }

    public function test_the_log_needs_its_permission_and_lists_only_obligation_rows(): void
    {
        $mine = $this->log();
        $todoRow = (new NotificationLog)->forceFill([
            'channel' => 'mail', 'notification_type' => 'todo.assigned', 'status' => 'sent',
            'subject' => 'A To-Do row', 'subject_type' => 'Modules\\Todos\\Models\\Todo', 'subject_id' => 1,
        ]);
        $todoRow->save();

        $this->actingAs($this->userWithPermissions(['obligation.view']));
        $this->get('/workspace/obligation-notifications')->assertForbidden();

        $this->actingAs($this->userWithPermissions(['obligation.view_notification_logs']));
        Livewire::test(ListObligationNotificationLogs::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$todoRow])
            ->assertTableActionHidden('remove', $mine);
    }

    public function test_only_a_super_admin_deletes_and_delete_all_shown_respects_the_filter(): void
    {
        $failed = [$this->log(['status' => 'failed']), $this->log(['status' => 'failed'])];
        $sent = $this->log(['status' => 'sent']);
        $this->actingAs($this->superAdmin(['obligation.view_notification_logs']));

        Livewire::test(ListObligationNotificationLogs::class)
            ->filterTable('status', 'failed')
            ->callAction('deleteFiltered');

        foreach ($failed as $row) {
            $this->assertModelMissing($row);
        }
        $this->assertModelExists($sent);

        Livewire::test(ListObligationNotificationLogs::class)->callTableAction('remove', $sent);
        $this->assertModelMissing($sent);
    }

    public function test_the_overview_counts_only_visible_obligations_across_every_breakdown(): void
    {
        $user = $this->userWithPermissions(['obligation.create']);
        ObligationFactory::new()->count(2)->create(['owner_user_id' => $user->id, 'status' => 'active', 'priority' => 'high']);
        ObligationFactory::new()->count(5)->create(['status' => 'active', 'priority' => 'low']);
        $this->actingAs($user);

        $page = Livewire::test(ObligationOverview::class)->assertOk();

        $this->assertSame(2, $page->viewData('stats')['Active']);
        // The breakdown by priority must not include the five obligations that are
        // not theirs.
        $this->assertSame(['High' => 2], collect($page->viewData('priorityBars'))->pluck('value', 'label')->all());
        $this->assertSame(2, collect($page->viewData('typeBars'))->sum('value'));
    }

    public function test_the_overdue_task_count_is_limited_to_visible_obligations(): void
    {
        $user = $this->userWithPermissions(['obligation.create']);
        $mine = ObligationFactory::new()->create(['owner_user_id' => $user->id]);
        $theirs = ObligationFactory::new()->create();
        foreach ([$mine, $theirs] as $obligation) {
            Task::factory()->create(['obligation_id' => $obligation->id, 'status' => 'pending', 'due_date' => now()->subDays(3), 'priority' => 'medium']);
        }
        $this->actingAs($user);

        $this->assertSame(1, Livewire::test(ObligationOverview::class)->viewData('stats')['Overdue tasks']);
    }

    public function test_the_risk_register_does_not_escape_the_visibility_filter(): void
    {
        $user = $this->userWithPermissions(['obligation.create', 'obligation.view_reports']);
        $mine = ObligationFactory::new()->create(['owner_user_id' => $user->id, 'risk_level' => 'critical', 'title' => 'My critical one']);
        ObligationFactory::new()->create(['risk_level' => 'critical', 'title' => 'Someone elses critical']);
        ObligationFactory::new()->create(['risk_level' => 'low', 'status' => 'active', 'expiry_date' => now()->subMonth(), 'title' => 'Someone elses lapsed']);
        $this->actingAs($user);

        Livewire::test(ObligationReports::class)
            ->assertSee('My critical one')
            ->assertDontSee('Someone elses critical')
            ->assertDontSee('Someone elses lapsed');
    }

    public function test_reports_need_the_reports_permission(): void
    {
        $this->actingAs($this->userWithPermissions(['obligation.view']));

        $this->get('/workspace/obligation-reports')->assertForbidden();
    }

    public function test_the_calendar_shows_visible_expiries_and_linked_tasks(): void
    {
        $user = $this->userWithPermissions(['obligation.create']);
        $date = now()->startOfMonth()->addDays(10);
        $mine = ObligationFactory::new()->create(['owner_user_id' => $user->id, 'title' => 'Boiler certificate', 'expiry_date' => $date]);
        ObligationFactory::new()->create(['title' => 'Hidden certificate', 'expiry_date' => $date]);
        Task::factory()->create(['obligation_id' => $mine->id, 'title' => 'Book the inspector', 'due_date' => $date, 'priority' => 'medium']);
        $this->actingAs($user);

        Livewire::test(ObligationCalendar::class)
            ->assertSee('Boiler certificate')
            ->assertSee('Book the inspector')
            ->assertDontSee('Hidden certificate')
            ->call('shift', 1)
            ->assertDontSee('Boiler certificate');
    }
}
