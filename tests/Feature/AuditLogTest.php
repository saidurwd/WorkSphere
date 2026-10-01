<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use LogicException;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The audit trail was inert before Phase 2: `tyro_audit_logs` had a read-only
 * admin screen and zero write call sites, so the page was permanently empty.
 */
class AuditLogTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_creating_a_role_writes_an_audit_row(): void
    {
        $this->actingAs($this->plainUser());

        Role::query()->create(['name' => 'Auditor', 'slug' => 'auditor']);

        $log = AuditLog::query()->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('created', $log->event);
        $this->assertSame(Role::class, $log->auditable_type);
    }

    public function test_updating_a_role_records_the_old_and_new_values(): void
    {
        $this->actingAs($this->plainUser());

        $role = Role::query()->create(['name' => 'Auditor', 'slug' => 'auditor', 'description' => 'Before']);

        $this->assertSame(1, AuditLog::query()->where('auditable_type', Role::class)->count());

        $role->update(['description' => 'After']);

        $log = AuditLog::query()->where('auditable_type', Role::class)->latest('id')->first();

        $this->assertSame('updated', $log->event);
        $this->assertArrayHasKey('description', $log->old_values);
        $this->assertSame('Before', $log->old_values['description']);
        $this->assertSame('After', $log->new_values['description']);
    }

    public function test_the_audit_row_records_the_acting_user(): void
    {
        $actor = $this->plainUser();
        $this->actingAs($actor);

        Role::query()->create(['name' => 'Auditor', 'slug' => 'auditor']);

        $log = AuditLog::query()->where('auditable_type', Role::class)->first();

        $this->assertSame($actor->id, $log->user_id);
    }

    public function test_audited_attribute_changes_only_capture_what_changed(): void
    {
        $this->actingAs($this->plainUser());

        $role = Role::query()->create(['name' => 'Auditor', 'slug' => 'auditor', 'description' => 'Before']);
        $role->update(['description' => 'After']);

        $log = AuditLog::query()->where('event', 'updated')->first();

        // `updated_at` also moves whenever a save lands in a new second, so the
        // assertion is about the business columns, not the bookkeeping ones.
        $businessKeys = array_values(array_diff(
            array_keys($log->new_values),
            ['updated_at', 'created_at'],
        ));

        $this->assertSame(['description'], $businessKeys);
    }

    public function test_deleting_a_role_writes_an_audit_row(): void
    {
        $this->actingAs($this->plainUser());

        $role = Role::query()->create(['name' => 'Auditor', 'slug' => 'auditor']);
        $role->delete();

        $this->assertSame('deleted', AuditLog::query()->latest('id')->first()->event);
    }

    public function test_an_audit_row_cannot_be_deleted(): void
    {
        $this->actingAs($this->plainUser());
        Role::query()->create(['name' => 'Auditor', 'slug' => 'auditor']);

        $log = AuditLog::query()->where('auditable_type', Role::class)->first();

        $this->expectException(LogicException::class);

        $log->delete();
    }

    public function test_an_audit_row_cannot_be_updated(): void
    {
        $this->actingAs($this->plainUser());
        Role::query()->create(['name' => 'Auditor', 'slug' => 'auditor']);

        $log = AuditLog::query()->where('auditable_type', Role::class)->first();

        $this->expectException(LogicException::class);

        $log->update(['event' => 'tampered']);
    }

    public function test_a_bulk_delete_is_refused_before_any_row_is_removed(): void
    {
        $this->actingAs($this->plainUser());
        Role::query()->create(['name' => 'Auditor', 'slug' => 'auditor']);

        $this->assertGreaterThan(0, AuditLog::query()->count());

        $this->expectException(LogicException::class);

        AuditLog::query()->delete();
    }

    public function test_a_bulk_delete_by_id_is_refused(): void
    {
        $this->actingAs($this->plainUser());
        Role::query()->create(['name' => 'Auditor', 'slug' => 'auditor']);

        $this->expectException(LogicException::class);

        AuditLog::destroy([AuditLog::query()->value('id')]);
    }

    public function test_an_audit_row_has_no_updated_at_column(): void
    {
        $this->actingAs($this->plainUser());
        Role::query()->create(['name' => 'Auditor', 'slug' => 'auditor']);

        $log = AuditLog::query()->where('auditable_type', Role::class)->first();

        $this->assertFalse($log->usesTimestamps());
        $this->assertArrayNotHasKey('updated_at', $log->getAttributes());
    }

    public function test_a_password_is_never_written_to_the_audit_trail(): void
    {
        $this->actingAs($this->plainUser());

        User::factory()->create(['name' => 'Auditor']);

        $log = AuditLog::query()->where('auditable_type', User::class)->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('password', $log->new_values);
        $this->assertArrayNotHasKey('remember_token', $log->new_values);
    }

    public function test_the_audit_row_carries_request_context(): void
    {
        $actor = $this->plainUser();
        $this->actingAs($actor);

        Role::query()->create(['name' => 'Auditor', 'slug' => 'auditor']);

        $log = AuditLog::query()->where('auditable_type', Role::class)->first();

        $this->assertNotNull($log->ip_address);
        $this->assertSame(Auth::id(), $log->user_id);
    }

    public function test_the_audit_screen_renders_the_written_rows(): void
    {
        $this->actingAs($this->plainUser());
        Role::query()->create(['name' => 'Auditor', 'slug' => 'auditor']);

        $this->actingAs($this->superAdmin())
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee('created');
    }
}
