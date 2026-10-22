<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Factories\MeetingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_an_unrelated_user_cannot_print_a_meeting(): void
    {
        $meeting = MeetingFactory::new()->create();

        $this->actingAs($this->plainUser())
            ->get(route('meetings.print', $meeting))
            ->assertForbidden();
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
