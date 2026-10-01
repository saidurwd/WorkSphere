<?php

namespace Tests\Feature;

use Database\Factories\ObligationFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Obligations\Models\ObligationResponsibility;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Obligations had no object-level authorization at all before Phase 2. These
 * cases pin the index() scoping rule — owner or active responsibility holder —
 * to a single record.
 */
class ObligationPolicyTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_an_unrelated_user_cannot_view_an_obligation(): void
    {
        $obligation = ObligationFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->get(route('obligations.show', $obligation))
            ->assertForbidden();
    }

    public function test_an_unrelated_user_cannot_edit_an_obligation(): void
    {
        $obligation = ObligationFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->get(route('obligations.edit', $obligation))
            ->assertForbidden();
    }

    public function test_an_unrelated_user_cannot_update_an_obligation(): void
    {
        $obligation = ObligationFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->put(route('obligations.update', $obligation), $this->validUpdatePayload())
            ->assertForbidden();

        $this->assertDatabaseMissing('obligations', ['id' => $obligation->id, 'title' => 'Hijacked']);
    }

    public function test_an_unrelated_user_cannot_delete_an_obligation(): void
    {
        $obligation = ObligationFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->delete(route('obligations.destroy', $obligation))
            ->assertForbidden();

        $this->assertDatabaseHas('obligations', ['id' => $obligation->id]);
    }

    public function test_an_active_responsibility_holder_may_view_but_not_edit(): void
    {
        $owner = $this->plainUser();
        $responsible = $this->plainUser();
        $obligation = ObligationFactory::new()->create(['owner_user_id' => $owner->id]);

        ObligationResponsibility::query()->create([
            'obligation_id' => $obligation->id,
            'user_id' => $responsible->id,
            'responsibility_type' => 'responsible',
            'active' => true,
        ]);

        $this->actingAs($responsible)->get(route('obligations.show', $obligation))->assertOk();
        $this->actingAs($responsible)->get(route('obligations.edit', $obligation))->assertForbidden();
    }

    public function test_an_inactive_responsibility_holder_may_not_view(): void
    {
        $owner = $this->plainUser();
        $responsible = $this->plainUser();
        $obligation = ObligationFactory::new()->create(['owner_user_id' => $owner->id]);

        ObligationResponsibility::query()->create([
            'obligation_id' => $obligation->id,
            'user_id' => $responsible->id,
            'responsibility_type' => 'responsible',
            'active' => false,
        ]);

        $this->actingAs($responsible)
            ->get(route('obligations.show', $obligation))
            ->assertForbidden();
    }

    public function test_the_owner_may_edit_and_delete(): void
    {
        $owner = $this->plainUser();
        $obligation = ObligationFactory::new()->create(['owner_user_id' => $owner->id]);

        $this->actingAs($owner)->get(route('obligations.edit', $obligation))->assertOk();

        $this->actingAs($owner)
            ->put(route('obligations.update', $obligation), $this->validUpdatePayload('Renamed'))
            ->assertRedirect(route('obligations.show', $obligation));

        $this->assertDatabaseHas('obligations', ['id' => $obligation->id, 'title' => 'Renamed']);
    }

    public function test_a_user_with_obligation_view_sees_an_obligation_they_do_not_own(): void
    {
        $viewer = $this->userWithPermissions(['obligation.view']);
        $obligation = ObligationFactory::new()->create();

        $this->actingAs($viewer)->get(route('obligations.show', $obligation))->assertOk();
    }

    public function test_obligation_view_permission_does_not_grant_update(): void
    {
        $viewer = $this->userWithPermissions(['obligation.view']);
        $obligation = ObligationFactory::new()->create();

        $this->actingAs($viewer)->get(route('obligations.edit', $obligation))->assertForbidden();
    }

    public function test_a_super_admin_may_act_on_any_obligation(): void
    {
        $obligation = ObligationFactory::new()->create();

        $this->actingAs($this->superAdmin())->get(route('obligations.show', $obligation))->assertOk();
    }

    public function test_the_index_only_lists_obligations_the_user_may_see(): void
    {
        $user = $this->plainUser();
        $mine = ObligationFactory::new()->create([
            'owner_user_id' => $user->id,
            'title' => 'My Obligation',
        ]);
        ObligationFactory::new()->create(['title' => 'Their Obligation']);

        $response = $this->actingAs($user)->get(route('obligations.index'));

        $response->assertOk();
        $response->assertSee('My Obligation');
        $response->assertDontSee('Their Obligation');
    }

    public function test_an_anonymous_visitor_cannot_reach_an_obligation(): void
    {
        $obligation = ObligationFactory::new()->create();

        $this->get(route('obligations.show', $obligation))->assertRedirect(route('login'));
    }

    public function test_a_user_without_obligation_create_cannot_create_one(): void
    {
        $this->actingAs($this->plainUser())
            ->post(route('obligations.store'), $this->validUpdatePayload())
            ->assertForbidden();

        $this->assertDatabaseCount('obligations', 0);
    }

    public function test_a_user_with_obligation_create_can_create_one(): void
    {
        $user = $this->userWithPermissions(['obligation.create']);

        $this->actingAs($user)
            ->post(route('obligations.store'), $this->validUpdatePayload('Legitimate'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('obligations', ['title' => 'Legitimate']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validUpdatePayload(string $title = 'Hijacked'): array
    {
        $obligation = ObligationFactory::new()->make();

        return [
            'title' => $title,
            'description' => 'desc',
            'obligation_type_id' => $obligation->obligation_type_id,
            'category_id' => $obligation->category_id,
            'company_id' => $obligation->company_id,
            'department_id' => $obligation->department_id,
            'location_id' => $obligation->location_id,
            'vendor_id' => $obligation->vendor_id,
            'owner_user_id' => $obligation->owner_user_id,
            'start_date' => now()->format('Y-m-d'),
            'expiry_date' => now()->addYear()->format('Y-m-d'),
            'renewal_required' => true,
            'auto_renew' => false,
            'priority' => 'medium',
            'risk_level' => 'low',
            'estimated_cost' => 1000,
            'currency' => 'BDT',
            'status' => 'active',
        ];
    }
}
