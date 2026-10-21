<?php

namespace Tests\Feature;

use App\Filament\Resources\Companies\Pages\CreateCompany;
use App\Filament\Resources\Departments\Pages\CreateDepartment;
use App\Filament\Resources\Departments\Pages\EditDepartment;
use App\Filament\Resources\Departments\Pages\ListDepartments;
use App\Filament\Resources\Employees\Pages\CreateEmployee;
use App\Filament\Resources\Employees\Pages\EditEmployee;
use App\Filament\Resources\Employees\Pages\ListEmployees;
use App\Filament\Resources\Locations\Pages\ListLocations;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The Filament replacement for the Blade reference-data screens.
 */
class FilamentReferenceDataTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private function manager(): void
    {
        $this->actingAs($this->superAdmin(['user.manage']));
    }

    public function test_every_reference_table_lists_its_records(): void
    {
        $this->manager();

        $employee = Employee::factory()->create();
        $department = Department::factory()->create();
        $location = Location::factory()->create();

        Livewire::test(ListEmployees::class)->assertCanSeeTableRecords([$employee]);
        Livewire::test(ListDepartments::class)->assertCanSeeTableRecords([$department]);
        Livewire::test(ListLocations::class)->assertCanSeeTableRecords([$location]);
    }

    public function test_a_company_can_be_created_and_its_code_must_be_unique(): void
    {
        $this->manager();

        Company::factory()->create(['company_code' => 'ACME']);

        Livewire::test(CreateCompany::class)
            ->fillForm(['company_code' => 'ACME', 'company_name' => 'Dupe', 'status' => 'active'])
            ->call('create')
            ->assertHasFormErrors(['company_code' => 'unique']);

        Livewire::test(CreateCompany::class)
            ->fillForm(['company_code' => 'NEW', 'company_name' => 'New Co', 'status' => 'active'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('companies', ['company_code' => 'NEW']);
    }

    public function test_an_employee_can_be_edited(): void
    {
        $this->manager();

        $employee = Employee::factory()->create();

        Livewire::test(EditEmployee::class, ['record' => $employee->getKey()])
            ->fillForm(['designation' => 'Lead'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Lead', $employee->fresh()->designation);
    }

    public function test_removal_is_a_deactivation_not_a_delete(): void
    {
        $this->manager();

        $employee = Employee::factory()->create(['status' => 'active']);

        Livewire::test(ListEmployees::class)->callTableAction('deactivate', $employee);

        $this->assertSame('inactive', $employee->fresh()->status);
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_a_user_without_user_manage_is_denied(): void
    {
        $this->actingAs($this->userWithPermissions(['role.manage']));

        $this->get('/admin/employees')->assertForbidden();
        $this->get('/admin/companies/create')->assertForbidden();
    }

    public function test_an_employee_can_be_created_with_a_department_and_location(): void
    {
        $this->manager();

        $department = Department::factory()->create();
        $location = Location::factory()->create();

        Livewire::test(CreateEmployee::class)
            ->fillForm([
                'employee_code' => 'EMP-900',
                'employee_name' => 'Test Person',
                'email' => 'test.person@example.com',
                'department_id' => $department->id,
                'location_id' => $location->id,
                'joining_date' => '2026-01-01',
                'status' => 'active',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $employee = Employee::query()->where('employee_code', 'EMP-900')->firstOrFail();

        $this->assertSame($department->id, $employee->department_id);
        $this->assertSame($location->id, $employee->location_id);
    }

    public function test_a_duplicate_code_is_rejected(): void
    {
        $this->manager();

        Department::factory()->create(['department_code' => 'DUP']);

        Livewire::test(CreateDepartment::class)
            ->fillForm(['department_code' => 'DUP', 'department_name' => 'Another', 'status' => 'active'])
            ->call('create')
            ->assertHasFormErrors(['department_code' => 'unique']);
    }

    public function test_editing_a_record_does_not_fail_against_its_own_unique_value(): void
    {
        $this->manager();

        $department = Department::factory()->create(['department_code' => 'SAME']);

        // The unique rule must ignore the row being edited, or saving without
        // changing the code fails against itself.
        Livewire::test(EditDepartment::class, ['record' => $department->getKey()])
            ->fillForm(['department_name' => 'Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed', $department->fresh()->department_name);
    }

    public function test_reference_data_requires_authentication(): void
    {
        $this->get('/admin/employees')->assertRedirect('/admin/login');
    }
}
