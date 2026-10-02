<?php

namespace Modules\Obligations\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationResponsibility;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Obligation responsibilities.
 *
 * The `active` flag is the whole point of this table: a discharged
 * responsibility must STOP granting visibility, and both `ObligationPolicy` and
 * the API's list filter read that flag. A test that only ever creates active rows
 * would pass whether or not the flag is honoured anywhere.
 */
class ObligationResponsibilityTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_an_active_responsibility_grants_visibility(): void
    {
        $responsible = $this->plainUser();
        $obligation = Obligation::factory()->create(['owner_user_id' => $this->plainUser()->id]);

        ObligationResponsibility::factory()->create([
            'obligation_id' => $obligation->id,
            'user_id' => $responsible->id,
        ]);

        $this->assertTrue($obligation->fresh()->responsibilities()->where('active', true)->exists());
    }

    public function test_a_discharged_responsibility_stops_granting_visibility(): void
    {
        $former = $this->plainUser();
        $obligation = Obligation::factory()->create(['owner_user_id' => $this->plainUser()->id]);

        ObligationResponsibility::factory()->discharged()->create([
            'obligation_id' => $obligation->id,
            'user_id' => $former->id,
        ]);

        $this->assertFalse(
            $obligation->fresh()->responsibilities()->where('active', true)->exists(),
            'A discharged responsibility is still counted as active.',
        );
    }

    public function test_one_user_can_hold_several_responsibility_types(): void
    {
        $user = $this->plainUser();
        $obligation = Obligation::factory()->create();

        // The unique index is (obligation_id, user_id, responsibility_type), so
        // several types per user is legal and must not be confused with a duplicate.
        ObligationResponsibility::factory()->type('owner')->create([
            'obligation_id' => $obligation->id,
            'user_id' => $user->id,
        ]);

        ObligationResponsibility::factory()->type('backup')->create([
            'obligation_id' => $obligation->id,
            'user_id' => $user->id,
        ]);

        $this->assertSame(2, $obligation->responsibilities()->where('user_id', $user->id)->count());
    }

    public function test_the_responsibility_belongs_to_its_obligation(): void
    {
        $obligation = Obligation::factory()->create();

        $responsibility = ObligationResponsibility::factory()->create([
            'obligation_id' => $obligation->id,
        ]);

        $this->assertSame($obligation->id, $responsibility->obligation->id);
    }

    public function test_the_escalation_level_defaults_to_the_first(): void
    {
        $responsibility = ObligationResponsibility::factory()->create();

        $this->assertSame(1, $responsibility->escalation_level);
    }

    public function test_a_responsibility_goes_with_the_user_it_names(): void
    {
        // Deliberately DIFFERENT from `tasks.user_id`, which Phase 2 softened to
        // nullOnDelete so a task survives losing its owner. A responsibility is not
        // a work item: nobody can hold a duty for a person who no longer exists,
        // so a row naming a deleted user would be a row nobody could ever satisfy.
        // The cascade is therefore correct here, and this test pins it so the two
        // behaviours are not "aligned" by accident later.
        $user = User::factory()->create();
        $obligation = Obligation::factory()->create();

        $responsibility = ObligationResponsibility::factory()->create([
            'obligation_id' => $obligation->id,
            'user_id' => $user->id,
        ]);

        $user->delete();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('obligation_responsibilities', ['id' => $responsibility->id]);
    }

    public function test_an_obligation_takes_its_responsibilities_with_it(): void
    {
        $obligation = Obligation::factory()->create();

        $responsibility = ObligationResponsibility::factory()->create([
            'obligation_id' => $obligation->id,
        ]);

        $obligation->forceDelete();

        $this->assertDatabaseMissing('obligation_responsibilities', ['id' => $responsibility->id]);
    }
}
