<?php

namespace Tests\Feature;

use Database\Factories\ObligationFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Obligations\Filament\Resources\NotificationLogs\Pages\ListObligationNotificationLogs;
use Modules\Obligations\Filament\Resources\Vendors\Pages\CreateVendor;
use Modules\Obligations\Filament\Resources\Vendors\Pages\EditVendor;
use Modules\Obligations\Filament\Resources\Vendors\Pages\ListVendors;
use Modules\Obligations\Models\NotificationLog;
use Modules\Obligations\Models\Vendor;
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
}
