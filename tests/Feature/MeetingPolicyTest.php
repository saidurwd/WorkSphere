<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Factories\MeetingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Meetings\Models\MeetingParticipant;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Meetings had no object-level authorization at all before Phase 2: the index
 * filtered by organizer or participant, but show/edit/update/destroy checked
 * nothing. These cases pin the same rule to a single record.
 */
class MeetingPolicyTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_an_unrelated_user_cannot_view_a_meeting(): void
    {
        $meeting = MeetingFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->get(route('meetings.show', $meeting))
            ->assertForbidden();
    }

    public function test_an_unrelated_user_cannot_edit_a_meeting(): void
    {
        $meeting = MeetingFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->get(route('meetings.edit', $meeting))
            ->assertForbidden();
    }

    public function test_an_unrelated_user_cannot_update_a_meeting(): void
    {
        $meeting = MeetingFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->put(route('meetings.update', $meeting), $this->validUpdatePayload())
            ->assertForbidden();

        $this->assertDatabaseMissing('meetings', ['id' => $meeting->id, 'title' => 'Hijacked']);
    }

    public function test_an_unrelated_user_cannot_delete_a_meeting(): void
    {
        $meeting = MeetingFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->delete(route('meetings.destroy', $meeting))
            ->assertForbidden();

        $this->assertDatabaseHas('meetings', ['id' => $meeting->id]);
    }

    public function test_an_unrelated_user_cannot_print_a_meeting(): void
    {
        $meeting = MeetingFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->get(route('meetings.print', $meeting))
            ->assertForbidden();
    }

    public function test_an_unrelated_user_cannot_transition_a_meeting(): void
    {
        $meeting = MeetingFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->post(route('meetings.start', $meeting))
            ->assertForbidden();

        $this->assertDatabaseHas('meetings', ['id' => $meeting->id, 'status' => 'scheduled']);
    }

    public function test_a_participant_may_view_but_not_edit(): void
    {
        $organizer = $this->plainUser();
        $participant = $this->plainUser();
        $meeting = MeetingFactory::new()->organisedBy($organizer)->create();

        MeetingParticipant::query()->create([
            'meeting_id' => $meeting->id,
            'user_id' => $participant->id,
            'participant_type' => 'member',
            'attendance_status' => 'invited',
        ]);

        $this->actingAs($participant)->get(route('meetings.show', $meeting))->assertOk();
        $this->actingAs($participant)->get(route('meetings.edit', $meeting))->assertForbidden();
        $this->actingAs($participant)->post(route('meetings.cancel', $meeting))->assertForbidden();
    }

    public function test_the_organizer_may_edit_and_delete(): void
    {
        $organizer = $this->plainUser();
        $meeting = MeetingFactory::new()->organisedBy($organizer)->create();

        $this->actingAs($organizer)->get(route('meetings.edit', $meeting))->assertOk();

        $this->actingAs($organizer)
            ->put(route('meetings.update', $meeting), $this->validUpdatePayload('Renamed'))
            ->assertRedirect(route('meetings.show', $meeting));

        $this->assertDatabaseHas('meetings', ['id' => $meeting->id, 'title' => 'Renamed']);
    }

    public function test_a_user_with_meeting_view_sees_a_meeting_they_do_not_organise(): void
    {
        $viewer = $this->userWithPermissions(['meeting.view']);
        $meeting = MeetingFactory::new()->create();

        $this->actingAs($viewer)->get(route('meetings.show', $meeting))->assertOk();
    }

    public function test_meeting_view_permission_does_not_grant_edit(): void
    {
        $viewer = $this->userWithPermissions(['meeting.view']);
        $meeting = MeetingFactory::new()->create();

        $this->actingAs($viewer)->get(route('meetings.edit', $meeting))->assertForbidden();
    }

    public function test_a_super_admin_may_act_on_any_meeting(): void
    {
        $meeting = MeetingFactory::new()->create();

        $this->actingAs($this->superAdmin())->get(route('meetings.show', $meeting))->assertOk();
    }

    public function test_a_completed_meeting_cannot_be_deleted_even_by_its_organizer(): void
    {
        $organizer = $this->plainUser();
        $meeting = MeetingFactory::new()->organisedBy($organizer)->completed()->create();

        $this->actingAs($organizer)
            ->delete(route('meetings.destroy', $meeting))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('meetings', ['id' => $meeting->id]);
    }

    public function test_the_index_only_lists_meetings_the_user_may_see(): void
    {
        $user = $this->plainUser();
        $mine = MeetingFactory::new()->organisedBy($user)->create(['title' => 'My Meeting']);
        MeetingFactory::new()->create(['title' => 'Their Meeting']);

        $response = $this->actingAs($user)->get(route('meetings.index'));

        $response->assertOk();
        $response->assertSee('My Meeting');
        $response->assertDontSee('Their Meeting');
    }

    public function test_an_anonymous_visitor_cannot_reach_a_meeting(): void
    {
        $meeting = MeetingFactory::new()->create();

        $this->get(route('meetings.show', $meeting))->assertRedirect(route('login'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validUpdatePayload(string $title = 'Hijacked'): array
    {
        return [
            'title' => $title,
            'meeting_type_id' => MeetingFactory::new()->create()->meeting_type_id,
            'organizer_id' => User::factory()->create()->id,
            'meeting_date' => now()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'priority' => 'normal',
        ];
    }
}
