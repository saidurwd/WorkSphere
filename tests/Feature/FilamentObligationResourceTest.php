<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Department;
use Database\Factories\ObligationFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Obligations\Filament\Resources\Obligations\Pages\CreateObligation;
use Modules\Obligations\Filament\Resources\Obligations\Pages\EditObligation;
use Modules\Obligations\Filament\Resources\Obligations\Pages\ListObligations;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationCategory;
use Modules\Obligations\Models\ObligationResponsibility;
use Modules\Obligations\Models\ObligationType;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The Filament Obligation resource (workspace panel).
 */
class FilamentObligationResourceTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('workspace');
    }

    public function test_without_obligation_view_you_see_what_you_own_or_are_responsible_for(): void
    {
        $user = $this->userWithPermissions(['obligation.create']);
        $owned = ObligationFactory::new()->create(['owner_user_id' => $user->id]);
        $responsible = ObligationFactory::new()->create();
        ObligationResponsibility::factory()->create(['obligation_id' => $responsible->id, 'user_id' => $user->id, 'active' => true]);
        $lapsed = ObligationFactory::new()->create();
        ObligationResponsibility::factory()->create(['obligation_id' => $lapsed->id, 'user_id' => $user->id, 'active' => false]);
        $stranger = ObligationFactory::new()->create();
        $this->actingAs($user);

        Livewire::test(ListObligations::class)
            ->assertCanSeeTableRecords([$owned, $responsible])
            ->assertCanNotSeeTableRecords([$lapsed, $stranger]);
    }

    public function test_obligation_view_and_super_admin_see_everything(): void
    {
        $all = ObligationFactory::new()->count(2)->create();

        $this->actingAs($this->userWithPermissions(['obligation.view']));
        Livewire::test(ListObligations::class)->assertCanSeeTableRecords($all);

        $this->actingAs($this->superAdmin());
        Livewire::test(ListObligations::class)->assertCanSeeTableRecords($all);
    }

    public function test_the_filters_narrow_the_list(): void
    {
        $this->actingAs($this->userWithPermissions(['obligation.view']));
        $high = ObligationFactory::new()->create(['priority' => 'high', 'status' => 'active', 'expiry_date' => now()->addDays(3)]);
        $low = ObligationFactory::new()->create(['priority' => 'low', 'status' => 'archived', 'expiry_date' => now()->addDays(200)]);
        $lapsed = ObligationFactory::new()->create(['priority' => 'low', 'status' => 'active', 'expiry_date' => now()->subDays(5)]);

        Livewire::test(ListObligations::class)
            ->filterTable('priority', 'high')
            ->assertCanSeeTableRecords([$high])
            ->assertCanNotSeeTableRecords([$low, $lapsed]);

        Livewire::test(ListObligations::class)
            ->filterTable('expiry', ['period' => '7_days'])
            ->assertCanSeeTableRecords([$high])
            ->assertCanNotSeeTableRecords([$low, $lapsed]);

        Livewire::test(ListObligations::class)
            ->filterTable('expiry', ['period' => 'expired'])
            ->assertCanSeeTableRecords([$lapsed])
            ->assertCanNotSeeTableRecords([$high, $low]);
    }

    private function validForm(): array
    {
        $user = $this->plainUser();

        return [
            'title' => 'Fire certificate',
            'obligation_type_id' => ObligationType::factory()->create(['active' => true])->id,
            'category_id' => ObligationCategory::factory()->create(['active' => true])->id,
            'company_id' => Company::factory()->create(['status' => 'active'])->id,
            'department_id' => Department::factory()->create(['status' => 'active'])->id,
            'owner_user_id' => $user->id,
            'start_date' => now()->toDateString(),
            'expiry_date' => now()->addYear()->toDateString(),
            'renewal_required' => true,
            'auto_renew' => false,
            'priority' => 'high',
            'risk_level' => 'medium',
            'status' => 'active',
        ];
    }

    public function test_an_obligation_is_created_numbered_and_logged(): void
    {
        $creator = $this->userWithPermissions(['obligation.create']);
        $this->actingAs($creator);

        Livewire::test(CreateObligation::class)
            ->fillForm($this->validForm())
            ->call('create')
            ->assertHasNoFormErrors();

        $obligation = Obligation::query()->where('title', 'Fire certificate')->firstOrFail();
        $this->assertStringStartsWith('OBS-', $obligation->obligation_no);
        $this->assertSame($creator->id, $obligation->created_by);
        $this->assertDatabaseHas('obligation_activity_logs', ['obligation_id' => $obligation->id, 'action' => 'CREATED']);
    }

    public function test_the_expiry_cannot_precede_the_start_and_a_title_is_required(): void
    {
        $this->actingAs($this->userWithPermissions(['obligation.create']));

        Livewire::test(CreateObligation::class)
            ->fillForm([...$this->validForm(), 'title' => null, 'start_date' => now()->toDateString(), 'expiry_date' => now()->subDay()->toDateString()])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required', 'expiry_date']);
    }

    public function test_creating_needs_the_create_permission(): void
    {
        $this->actingAs($this->plainUser());

        $this->get('/workspace/obligations/create')->assertForbidden();
    }

    public function test_the_owner_edits_and_it_is_logged_but_a_viewer_cannot(): void
    {
        $owner = $this->userWithPermissions(['obligation.create']);
        $obligation = ObligationFactory::new()->create(['owner_user_id' => $owner->id]);

        $this->actingAs($owner);
        Livewire::test(EditObligation::class, ['record' => $obligation->getKey()])
            ->fillForm(['title' => 'Renamed obligation'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed obligation', $obligation->fresh()->title);
        $this->assertDatabaseHas('obligation_activity_logs', ['obligation_id' => $obligation->id, 'action' => 'UPDATED']);

        $this->actingAs($this->userWithPermissions(['obligation.view']));
        $this->get("/workspace/obligations/{$obligation->id}/edit")->assertForbidden();
    }

    public function test_a_stranger_cannot_open_an_obligation_they_have_no_stake_in(): void
    {
        $this->actingAs($this->userWithPermissions(['obligation.create']));
        $obligation = ObligationFactory::new()->create();

        $this->get("/workspace/obligations/{$obligation->id}")->assertNotFound();
    }

    public function test_deleting_is_for_the_owner_or_obligation_delete(): void
    {
        $owner = $this->userWithPermissions(['obligation.create']);
        $obligation = ObligationFactory::new()->create(['owner_user_id' => $owner->id]);
        $this->actingAs($owner);

        Livewire::test(ListObligations::class)->callTableAction('delete', $obligation);
        $this->assertModelMissing($obligation);
    }
}
