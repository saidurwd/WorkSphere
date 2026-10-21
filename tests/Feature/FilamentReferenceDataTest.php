<?php

namespace Tests\Feature;

use App\Filament\Resources\Companies\Pages\CreateCompany;
use App\Filament\Resources\Departments\Pages\ListDepartments;
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

        $this->get('/panel/employees')->assertForbidden();
        $this->get('/panel/companies/create')->assertForbidden();
    }
}
