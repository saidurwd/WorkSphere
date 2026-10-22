<?php

namespace Tests\Feature;

use Database\Factories\MeetingFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Modules\Meetings\Events\MeetingCancelled;
use Modules\Meetings\Events\MeetingCompleted;
use Modules\Meetings\Events\MeetingCreated;
use Modules\Meetings\Events\MeetingStarted;
use Modules\Meetings\Events\MeetingUpdated;
use Modules\Meetings\Filament\Resources\Meetings\Pages\CreateMeeting;
use Modules\Meetings\Filament\Resources\Meetings\Pages\EditMeeting;
use Modules\Meetings\Filament\Resources\Meetings\Pages\ListMeetings;
use Modules\Meetings\Filament\Resources\Meetings\Pages\ViewMeeting;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingParticipant;
use Modules\Meetings\Models\MeetingType;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The Filament Meeting resource (workspace panel).
 */
class FilamentMeetingResourceTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('workspace');
    }

    public function test_without_meeting_view_you_see_meetings_you_organise_or_attend(): void
    {
        $user = $this->userWithPermissions(['meeting.create']);
        $organised = MeetingFactory::new()->create(['organizer_id' => $user->id]);
        $attended = MeetingFactory::new()->create();
        MeetingParticipant::factory()->create(['meeting_id' => $attended->id, 'user_id' => $user->id]);
        $stranger = MeetingFactory::new()->create();
        $this->actingAs($user);

        Livewire::test(ListMeetings::class)
            ->assertCanSeeTableRecords([$organised, $attended])
            ->assertCanNotSeeTableRecords([$stranger]);
    }

    public function test_meeting_view_and_super_admin_see_everything(): void
    {
        $all = MeetingFactory::new()->count(2)->create();

        $this->actingAs($this->userWithPermissions(['meeting.view']));
        Livewire::test(ListMeetings::class)->assertCanSeeTableRecords($all);

        $this->actingAs($this->superAdmin());
        Livewire::test(ListMeetings::class)->assertCanSeeTableRecords($all);
    }

    public function test_the_filters_narrow_the_list(): void
    {
        $this->actingAs($this->userWithPermissions(['meeting.view']));
        $soon = MeetingFactory::new()->create(['status' => 'scheduled', 'meeting_date' => now()->addDays(2)]);
        $done = MeetingFactory::new()->create(['status' => 'completed', 'meeting_date' => now()->subDays(20)]);

        Livewire::test(ListMeetings::class)
            ->filterTable('status', 'completed')
            ->assertCanSeeTableRecords([$done])
            ->assertCanNotSeeTableRecords([$soon]);

        Livewire::test(ListMeetings::class)
            ->filterTable('date_range', ['from' => now()->toDateString(), 'until' => now()->addWeek()->toDateString()])
            ->assertCanSeeTableRecords([$soon])
            ->assertCanNotSeeTableRecords([$done]);
    }

    private function validForm(?int $organizerId = null): array
    {
        return [
            'title' => 'Quarterly review',
            'meeting_type_id' => MeetingType::factory()->create()->id,
            'organizer_id' => $organizerId ?? $this->plainUser()->id,
            'meeting_date' => now()->addDays(3)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '10:30',
            'priority' => 'important',
        ];
    }

    public function test_a_meeting_is_created_numbered_with_its_participants_and_announced(): void
    {
        Event::fake([MeetingCreated::class]);

        $creator = $this->userWithPermissions(['meeting.create']);
        $guest = $this->plainUser();
        $this->actingAs($creator);

        Livewire::test(CreateMeeting::class)
            ->fillForm($this->validForm($creator->id) + [
                'participants' => [['user_id' => $guest->id, 'participant_type' => 'guest', 'attendance_status' => 'invited']],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $meeting = Meeting::query()->where('title', 'Quarterly review')->firstOrFail();
        $this->assertNotEmpty($meeting->meeting_no);
        $this->assertSame($creator->id, $meeting->created_by);
        $this->assertTrue($meeting->participants()->where('user_id', $guest->id)->where('participant_type', 'guest')->exists());
        Event::assertDispatched(MeetingCreated::class);
    }

    public function test_the_end_must_follow_the_start_and_creating_needs_permission(): void
    {
        $this->actingAs($this->userWithPermissions(['meeting.create']));

        Livewire::test(CreateMeeting::class)
            ->fillForm([...$this->validForm(), 'start_time' => '11:00', 'end_time' => '10:00', 'title' => null])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required', 'end_time']);

        $this->actingAs($this->plainUser());
        $this->get('/workspace/meetings/create')->assertForbidden();
    }

    public function test_the_organizer_edits_and_it_is_announced_but_a_viewer_cannot(): void
    {
        Event::fake([MeetingUpdated::class]);

        $organizer = $this->userWithPermissions(['meeting.create']);
        $meeting = MeetingFactory::new()->create(['organizer_id' => $organizer->id, 'priority' => 'normal', 'start_time' => '09:00', 'end_time' => '10:00']);

        $this->actingAs($organizer);
        Livewire::test(EditMeeting::class, ['record' => $meeting->getKey()])
            ->fillForm(['title' => 'Renamed meeting'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed meeting', $meeting->fresh()->title);
        Event::assertDispatched(MeetingUpdated::class);

        $this->actingAs($this->userWithPermissions(['meeting.view']));
        $this->get("/workspace/meetings/{$meeting->id}/edit")->assertForbidden();
    }

    public function test_a_stranger_cannot_open_a_meeting_they_have_no_part_in(): void
    {
        $this->actingAs($this->userWithPermissions(['meeting.create']));
        $meeting = MeetingFactory::new()->create();

        $this->get("/workspace/meetings/{$meeting->id}")->assertNotFound();
    }

    public function test_the_lifecycle_runs_start_then_complete_and_announces_each_step(): void
    {
        Event::fake([MeetingStarted::class, MeetingCompleted::class, MeetingCancelled::class]);

        $organizer = $this->userWithPermissions(['meeting.create']);
        $meeting = MeetingFactory::new()->create(['organizer_id' => $organizer->id, 'status' => 'scheduled']);
        $this->actingAs($organizer);

        $page = fn () => Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()]);

        $page()->assertActionHidden('complete')->callAction('start');
        $this->assertSame('in_progress', $meeting->fresh()->status);
        Event::assertDispatched(MeetingStarted::class);

        $page()->assertActionHidden('start')->callAction('complete');
        $this->assertSame('completed', $meeting->fresh()->status);
        Event::assertDispatched(MeetingCompleted::class);

        $page()->assertActionHidden('cancel');
    }

    public function test_cancelling_and_the_lifecycle_belong_to_the_organizer(): void
    {
        Event::fake([MeetingCancelled::class]);

        $meeting = MeetingFactory::new()->create(['status' => 'scheduled']);

        $this->actingAs($this->userWithPermissions(['meeting.view']));
        Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])->assertActionHidden('cancel');

        $this->actingAs($this->userWithPermissions(['meeting.view', 'meeting.edit']));
        Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])->callAction('cancel');
        $this->assertSame('cancelled', $meeting->fresh()->status);
        Event::assertDispatched(MeetingCancelled::class);
    }

    public function test_completed_meetings_cannot_be_deleted_from_the_list(): void
    {
        $organizer = $this->userWithPermissions(['meeting.create']);
        $done = MeetingFactory::new()->create(['organizer_id' => $organizer->id, 'status' => 'completed']);
        $open = MeetingFactory::new()->create(['organizer_id' => $organizer->id, 'status' => 'scheduled']);
        $this->actingAs($organizer);

        Livewire::test(ListMeetings::class)
            ->assertTableActionHidden('delete', $done)
            ->callTableAction('delete', $open);

        $this->assertFalse(Meeting::query()->whereKey($open->id)->exists());
        $this->assertModelExists($done);
    }
}
