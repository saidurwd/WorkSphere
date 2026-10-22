<?php

namespace Tests\Feature;

use Database\Factories\MeetingFactory;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Modules\Meetings\Events\ActionItemAssigned;
use Modules\Meetings\Events\ActionItemCompleted;
use Modules\Meetings\Events\ActionItemCreated;
use Modules\Meetings\Events\MeetingCancelled;
use Modules\Meetings\Events\MeetingCompleted;
use Modules\Meetings\Events\MeetingCreated;
use Modules\Meetings\Events\MeetingStarted;
use Modules\Meetings\Events\MeetingUpdated;
use Modules\Meetings\Events\MinutesApproved;
use Modules\Meetings\Events\MinutesReturned;
use Modules\Meetings\Events\MinutesSubmitted;
use Modules\Meetings\Filament\Resources\Meetings\Pages\CreateMeeting;
use Modules\Meetings\Filament\Resources\Meetings\Pages\EditMeeting;
use Modules\Meetings\Filament\Resources\Meetings\Pages\ListMeetings;
use Modules\Meetings\Filament\Resources\Meetings\Pages\ViewMeeting;
use Modules\Meetings\Filament\Resources\Meetings\RelationManagers\ActionItemsRelationManager;
use Modules\Meetings\Filament\Resources\Meetings\RelationManagers\AgendasRelationManager;
use Modules\Meetings\Filament\Resources\Meetings\RelationManagers\AttachmentsRelationManager;
use Modules\Meetings\Filament\Resources\Meetings\RelationManagers\DecisionsRelationManager;
use Modules\Meetings\Filament\Resources\Meetings\RelationManagers\ParticipantsRelationManager;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Meetings\Models\MeetingAttachment;
use Modules\Meetings\Models\MeetingParticipant;
use Modules\Meetings\Models\MeetingType;
use Modules\Meetings\Services\MeetingActionTaskService;
use Modules\Meetings\Services\MeetingApprovalService;
use Modules\Meetings\Services\MeetingRecurrenceService;
use Modules\Tasks\Events\TaskCompleted;
use Modules\Tasks\Events\TaskUpdated;
use Modules\Tasks\Models\Task;
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

    private function manager(string $class, Meeting $meeting): Testable
    {
        return Livewire::test($class, ['ownerRecord' => $meeting, 'pageClass' => ViewMeeting::class]);
    }

    private function organised(): array
    {
        $organizer = $this->userWithPermissions(['meeting.create']);

        return [$organizer, MeetingFactory::new()->create(['organizer_id' => $organizer->id])];
    }

    public function test_agenda_participants_and_decisions_can_be_added_and_removed_by_the_organizer(): void
    {
        [$organizer, $meeting] = $this->organised();
        $guest = $this->plainUser();
        $this->actingAs($organizer);

        $this->manager(AgendasRelationManager::class, $meeting)
            ->callAction(TestAction::make('create')->table(), ['agenda_no' => 1, 'title' => 'Budget', 'status' => 'pending']);
        $this->assertTrue($meeting->agendas()->where('title', 'Budget')->exists());

        $this->manager(ParticipantsRelationManager::class, $meeting)
            ->callAction(TestAction::make('create')->table(), ['user_id' => $guest->id, 'participant_type' => 'member', 'attendance_status' => 'invited']);
        $participant = $meeting->participants()->where('user_id', $guest->id)->firstOrFail();
        $this->assertNotNull($participant->invited_at);

        $this->manager(DecisionsRelationManager::class, $meeting)
            ->callAction(TestAction::make('create')->table(), ['decision_no' => 1, 'decision_title' => 'Approve budget', 'decision_type' => 'approved', 'decision_status' => 'active']);
        $decision = $meeting->decisions()->firstOrFail();
        $this->assertSame($organizer->id, $decision->created_by);

        $this->manager(ParticipantsRelationManager::class, $meeting)
            ->callAction(TestAction::make('delete')->table($participant));
        $this->assertModelMissing($participant);
    }

    public function test_a_participant_cannot_change_the_meetings_agenda_participants_or_decisions(): void
    {
        $participantUser = $this->userWithPermissions(['meeting.create']);
        $meeting = MeetingFactory::new()->create();
        MeetingParticipant::factory()->create(['meeting_id' => $meeting->id, 'user_id' => $participantUser->id]);
        $this->actingAs($participantUser);

        foreach ([AgendasRelationManager::class, ParticipantsRelationManager::class, DecisionsRelationManager::class, AttachmentsRelationManager::class, ActionItemsRelationManager::class] as $class) {
            $this->manager($class, $meeting)->assertActionHidden(TestAction::make('create')->table());
        }
    }

    public function test_a_valid_decision_needs_a_title_and_type(): void
    {
        [$organizer, $meeting] = $this->organised();
        $this->actingAs($organizer);

        $this->manager(DecisionsRelationManager::class, $meeting)
            ->callAction(TestAction::make('create')->table(), ['decision_no' => 1, 'decision_title' => '', 'decision_type' => null])
            ->assertHasFormErrors(['decision_title' => 'required', 'decision_type' => 'required']);
    }

    public function test_an_attachment_is_stored_and_mirrored_to_the_shared_table(): void
    {
        Storage::fake('public');

        [$organizer, $meeting] = $this->organised();
        $this->actingAs($organizer);

        $this->manager(AttachmentsRelationManager::class, $meeting)
            ->callAction(TestAction::make('create')->table(), ['file' => UploadedFile::fake()->create('pack.pdf', 15, 'application/pdf')]);

        $attachment = MeetingAttachment::query()->where('meeting_id', $meeting->id)->firstOrFail();
        $this->assertSame($organizer->id, $attachment->uploaded_by);
        $this->assertTrue(Storage::disk('public')->exists($attachment->file_path));
        $this->assertDatabaseHas('attachments', ['attachable_id' => $meeting->id, 'original_name' => $attachment->file_name]);
    }

    public function test_action_items_fire_created_assigned_and_completed_events(): void
    {
        Event::fake([ActionItemCreated::class, ActionItemAssigned::class, ActionItemCompleted::class]);

        [$organizer, $meeting] = $this->organised();
        $assignee = $this->plainUser();
        $this->actingAs($organizer);

        $this->manager(ActionItemsRelationManager::class, $meeting)
            ->callAction(TestAction::make('create')->table(), ['action_no' => 1, 'title' => 'Send minutes', 'priority' => 'high', 'status' => 'open']);
        Event::assertDispatched(ActionItemCreated::class);

        $item = MeetingActionItem::query()->where('meeting_id', $meeting->id)->firstOrFail();

        $this->manager(ActionItemsRelationManager::class, $meeting)
            ->callAction(TestAction::make('edit')->table($item), ['assigned_to' => $assignee->id, 'status' => 'completed']);

        Event::assertDispatched(ActionItemAssigned::class);
        Event::assertDispatched(ActionItemCompleted::class);
    }

    public function test_the_assignee_may_update_their_own_item_but_not_delete_or_link_tasks(): void
    {
        $assignee = $this->userWithPermissions(['meeting.view']);
        $meeting = MeetingFactory::new()->create();
        $item = MeetingActionItem::factory()->create(['meeting_id' => $meeting->id, 'assigned_to' => $assignee->id, 'task_id' => null]);
        $this->actingAs($assignee);

        $this->manager(ActionItemsRelationManager::class, $meeting)
            ->assertTableActionVisible('edit', $item)
            ->assertTableActionHidden('delete', $item)
            ->assertTableActionHidden('createTask', $item)
            ->assertTableActionHidden('linkTask', $item);
    }

    public function test_a_task_can_be_created_linked_and_unlinked_by_someone_who_may_assign(): void
    {
        [$organizer, $meeting] = $this->organised();
        $item = MeetingActionItem::factory()->create(['meeting_id' => $meeting->id, 'task_id' => null, 'priority' => 'high', 'title' => 'Fix the lift']);
        $this->actingAs($organizer);

        $this->manager(ActionItemsRelationManager::class, $meeting)
            ->callAction(TestAction::make('createTask')->table($item), ['title' => 'Fix the lift', 'priority' => 'high']);

        $task = Task::query()->where('title', 'Fix the lift')->firstOrFail();
        $this->assertSame($task->id, $item->fresh()->task_id);
        $this->assertSame($organizer->id, $task->user_id);

        $this->manager(ActionItemsRelationManager::class, $meeting)
            ->callAction(TestAction::make('unlinkTask')->table($item->fresh()));
        $this->assertNull($item->fresh()->task_id);

        $mine = Task::factory()->create(['user_id' => $organizer->id, 'responsible_user_id' => $organizer->id, 'priority' => 'medium']);
        $this->manager(ActionItemsRelationManager::class, $meeting)
            ->callAction(TestAction::make('linkTask')->table($item->fresh()), ['task_id' => $mine->id]);
        $this->assertSame($mine->id, $item->fresh()->task_id);
    }

    public function test_linking_a_task_the_user_cannot_see_is_refused(): void
    {
        [$organizer, $meeting] = $this->organised();
        $item = MeetingActionItem::factory()->create(['meeting_id' => $meeting->id, 'task_id' => null]);
        $foreign = Task::factory()->create(['priority' => 'medium']);
        $this->actingAs($organizer);

        $this->manager(ActionItemsRelationManager::class, $meeting)
            ->callAction(TestAction::make('linkTask')->table($item), ['task_id' => $foreign->id])
            ->assertHasFormErrors(['task_id']);

        $this->assertNull($item->fresh()->task_id);
    }

    public function test_minutes_move_through_prepare_submit_approve_publish_with_the_right_people(): void
    {
        $organizer = $this->userWithPermissions(['meeting.create']);
        $meeting = MeetingFactory::new()->create(['organizer_id' => $organizer->id, 'minutes_status' => 'draft']);
        $approver = $this->userWithPermissions(['meeting.view', 'meeting.approve_minutes', 'meeting.publish_minutes']);

        $this->actingAs($organizer);
        $page = fn () => Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()]);

        $page()->assertActionHidden('minutes_submit')->callAction('minutes_prepare');
        $this->assertSame('prepared', $meeting->fresh()->minutes_status);
        $this->assertSame($organizer->id, $meeting->fresh()->minutes_prepared_by);

        $page()->callAction('minutes_submit');
        $this->assertSame('submitted', $meeting->fresh()->minutes_status);

        // The organizer prepared them; they cannot also approve them.
        $page()->assertActionHidden('minutes_approve')->assertActionHidden('minutes_return');

        $this->actingAs($approver);
        $page()->callAction('minutes_approve');
        $this->assertSame('approved', $meeting->fresh()->minutes_status);
        $this->assertSame($approver->id, $meeting->fresh()->approved_by);

        $page()->callAction('minutes_publish');
        $this->assertSame('published', $meeting->fresh()->minutes_status);
        $this->assertNotNull($meeting->fresh()->published_at);
    }

    public function test_an_approver_can_return_submitted_minutes_with_a_reason(): void
    {
        $meeting = MeetingFactory::new()->create(['minutes_status' => 'submitted']);
        $this->actingAs($this->userWithPermissions(['meeting.view', 'meeting.approve_minutes']));

        Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])
            ->callAction('minutes_return', ['comments' => ''])
            ->assertHasActionErrors(['comments' => 'required']);

        Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])
            ->callAction('minutes_return', ['comments' => 'Missing the decisions section']);

        $this->assertSame('prepared', $meeting->fresh()->minutes_status);
    }

    public function test_named_approvers_must_all_approve_before_the_minutes_are_approved(): void
    {
        Event::fake([MinutesSubmitted::class, MinutesApproved::class]);

        $organizer = $this->userWithPermissions(['meeting.create']);
        $meeting = MeetingFactory::new()->create(['organizer_id' => $organizer->id, 'minutes_status' => 'prepared']);
        $first = $this->userWithPermissions(['meeting.view', 'meeting.approve_minutes']);
        $second = $this->userWithPermissions(['meeting.view', 'meeting.approve_minutes']);
        $outsider = $this->userWithPermissions(['meeting.view', 'meeting.approve_minutes']);

        $this->actingAs($organizer);
        Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])
            ->callAction('minutes_submit', ['approvers' => [$first->id, $second->id]]);

        Event::assertDispatched(MinutesSubmitted::class);
        $this->assertSame(2, $meeting->minutesApprovals()->where('status', 'pending')->count());

        // Someone not named cannot decide.
        $this->actingAs($outsider);
        Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])
            ->assertActionHidden('minutes_approve');

        $this->actingAs($first);
        Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])
            ->callAction('minutes_approve', ['comments' => 'Fine by me']);

        $this->assertSame('under_review', $meeting->fresh()->minutes_status);
        Event::assertNotDispatched(MinutesApproved::class);

        $this->actingAs($second);
        Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])
            ->callAction('minutes_approve');

        $this->assertSame('approved', $meeting->fresh()->minutes_status);
        Event::assertDispatched(MinutesApproved::class);
    }

    public function test_a_named_approver_returning_the_minutes_keeps_the_reason_and_withdraws_the_others(): void
    {
        Event::fake([MinutesReturned::class]);

        $meeting = MeetingFactory::new()->create(['minutes_status' => 'prepared']);
        $first = $this->userWithPermissions(['meeting.view', 'meeting.approve_minutes', 'meeting.create']);
        $second = $this->userWithPermissions(['meeting.view', 'meeting.approve_minutes']);

        app(MeetingApprovalService::class)->submitForApproval($meeting, [$first->id, $second->id]);

        $this->actingAs($first);
        Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])
            ->callAction('minutes_return', ['comments' => 'Decisions are missing']);

        $this->assertSame('prepared', $meeting->fresh()->minutes_status);
        Event::assertDispatched(MinutesReturned::class, fn ($event) => $event->comments === 'Decisions are missing');

        $returned = $meeting->minutesApprovals()->where('status', 'returned')->first();
        $this->assertSame('Decisions are missing', $returned->comments);
        $this->assertSame($first->id, $returned->approver_id);
        $this->assertSame(0, $meeting->minutesApprovals()->where('status', 'pending')->count());
    }

    public function test_a_meeting_can_be_set_to_repeat_and_the_next_one_is_created_on_the_next_date(): void
    {
        Event::fake([MeetingCreated::class]);

        $organizer = $this->userWithPermissions(['meeting.view', 'meeting.create', 'meeting.update']);
        $meeting = MeetingFactory::new()->create([
            'organizer_id' => $organizer->id,
            'meeting_date' => now()->addDays(2)->toDateString(),
            'status' => 'scheduled',
        ]);
        MeetingParticipant::query()->create(['meeting_id' => $meeting->id, 'user_id' => $organizer->id, 'participant_type' => 'member']);

        $this->actingAs($organizer);

        Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])
            ->assertActionHidden('create_next')
            ->callAction('repeat', ['recurrence_type' => 'weekly', 'recurrence_interval' => 1]);

        $rule = $meeting->recurrence()->where('is_active', true)->sole();
        $this->assertSame($meeting->meeting_date->toDateString(), $rule->start_date->toDateString());
        $this->assertSame($meeting->meeting_date->copy()->addWeek()->toDateString(), $rule->next_occurrence->toDateString());

        Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])
            ->assertActionVisible('create_next')
            ->callAction('create_next');

        $next = Meeting::query()->where('id', '!=', $meeting->id)->sole();
        $this->assertSame($meeting->meeting_date->copy()->addWeek()->toDateString(), $next->meeting_date->toDateString());
        $this->assertNotSame($meeting->meeting_no, $next->meeting_no);
        $this->assertNotNull($next->meeting_no);
        $this->assertSame('draft', $next->minutes_status);
        $this->assertSame(1, $next->participants()->count());
        Event::assertDispatched(MeetingCreated::class);

        // The same date is never created twice.
        $this->assertNull(app(MeetingRecurrenceService::class)->createNextMeeting($meeting->fresh()));
        $this->assertSame(2, Meeting::query()->count());
    }

    public function test_stopping_the_repetition_or_running_out_of_occurrences_ends_the_series(): void
    {
        $organizer = $this->userWithPermissions(['meeting.view', 'meeting.create', 'meeting.update']);
        $meeting = MeetingFactory::new()->create([
            'organizer_id' => $organizer->id,
            'meeting_date' => now()->addDays(2)->toDateString(),
            'status' => 'scheduled',
        ]);
        $service = app(MeetingRecurrenceService::class);

        // A rule capped at two meetings: the original plus one.
        $service->setRecurrence($meeting, ['recurrence_type' => 'daily', 'occurrences' => 2]);
        $second = $service->createNextMeeting($meeting->fresh());
        $this->assertNotNull($second);
        $this->assertNull($service->createNextMeeting($second->fresh()), 'The series has run out.');

        $this->actingAs($organizer);
        $service->setRecurrence($meeting, ['recurrence_type' => 'weekly']);

        Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])->callAction('stop_repeating');

        $this->assertFalse($meeting->recurrence()->where('is_active', true)->exists());
        $this->assertNull($service->nextDateAfter($meeting->fresh()));
    }

    public function test_repeating_needs_permission_to_update_the_meeting(): void
    {
        $meeting = MeetingFactory::new()->create(['status' => 'scheduled']);
        $this->actingAs($this->userWithPermissions(['meeting.view']));

        Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])
            ->assertActionHidden('repeat')
            ->assertActionHidden('create_next');
    }

    public function test_a_participant_may_view_but_not_edit_and_a_super_admin_may_do_both(): void
    {
        $attendee = $this->userWithPermissions(['meeting.create']);
        $meeting = MeetingFactory::new()->create(['priority' => 'normal', 'start_time' => '09:00', 'end_time' => '10:00']);
        MeetingParticipant::factory()->create(['meeting_id' => $meeting->id, 'user_id' => $attendee->id]);

        $this->actingAs($attendee);
        $this->get("/workspace/meetings/{$meeting->id}")->assertOk();
        $this->get("/workspace/meetings/{$meeting->id}/edit")->assertForbidden();
        Livewire::test(ViewMeeting::class, ['record' => $meeting->getKey()])
            ->assertActionHidden('start')
            ->assertActionHidden('cancel');

        $this->actingAs($this->superAdmin());
        Livewire::test(EditMeeting::class, ['record' => $meeting->getKey()])
            ->fillForm(['title' => 'Admin edit'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('Admin edit', $meeting->fresh()->title);
    }

    public function test_meeting_view_alone_does_not_grant_edit_or_delete(): void
    {
        $meeting = MeetingFactory::new()->create();
        $this->actingAs($this->userWithPermissions(['meeting.view']));

        $this->get("/workspace/meetings/{$meeting->id}")->assertOk();
        $this->get("/workspace/meetings/{$meeting->id}/edit")->assertForbidden();
        Livewire::test(ListMeetings::class)->assertTableActionHidden('delete', $meeting);
    }

    public function test_an_anonymous_visitor_is_sent_to_sign_in_and_a_stranger_cannot_print(): void
    {
        $meeting = MeetingFactory::new()->create();

        $this->get("/workspace/meetings/{$meeting->id}")->assertRedirect('/workspace/login');

        $this->actingAs($this->userWithPermissions(['meeting.create']));
        $this->get(route('meetings.print', $meeting))->assertForbidden();

        $this->actingAs($this->userWithPermissions(['meeting.view']));
        $this->get(route('meetings.print', $meeting))->assertOk();
    }

    public function test_linking_a_finished_task_marks_the_action_item_completed(): void
    {
        [$organizer, $meeting] = $this->organised();
        $item = MeetingActionItem::factory()->create(['meeting_id' => $meeting->id, 'task_id' => null, 'status' => 'open']);
        $done = Task::factory()->create([
            'user_id' => $organizer->id, 'responsible_user_id' => $organizer->id,
            'status' => 'completed', 'completed_at' => now(), 'priority' => 'medium',
        ]);
        $this->actingAs($organizer);

        $this->manager(ActionItemsRelationManager::class, $meeting)
            ->callAction(TestAction::make('linkTask')->table($item), ['task_id' => $done->id]);

        $this->assertSame('completed', $item->fresh()->status);
        $this->assertNotNull($item->fresh()->completed_at);
    }

    public function test_completing_the_linked_task_completes_the_action_item_and_announces_it_once(): void
    {
        Event::fake([ActionItemCompleted::class]);

        [, $meeting] = $this->organised();
        $task = Task::factory()->create(['status' => 'in_progress', 'priority' => 'medium']);
        $item = MeetingActionItem::factory()->create(['meeting_id' => $meeting->id, 'task_id' => $task->id, 'status' => 'in_progress']);

        $task->update(['status' => 'completed', 'completed_at' => now()]);
        event(new TaskCompleted($task->fresh()));
        event(new TaskUpdated($task->fresh()));

        $this->assertSame('completed', $item->fresh()->status);
        Event::assertDispatchedTimes(ActionItemCompleted::class, 1);
    }

    public function test_a_deferred_task_leaves_the_action_item_as_it_was(): void
    {
        [, $meeting] = $this->organised();
        $task = Task::factory()->create(['status' => 'on_hold', 'priority' => 'medium']);
        $item = MeetingActionItem::factory()->create(['meeting_id' => $meeting->id, 'task_id' => $task->id, 'status' => 'in_progress']);

        event(new TaskUpdated($task->fresh()));

        $this->assertSame('in_progress', $item->fresh()->status);
    }

    public function test_action_item_priorities_map_onto_task_priorities(): void
    {
        $service = app(MeetingActionTaskService::class);
        $map = new \ReflectionMethod($service, 'mapPriority');

        foreach (['critical' => 'high', 'urgent' => 'high', 'high' => 'high', 'medium' => 'medium', 'normal' => 'medium', 'low' => 'low', null => 'medium'] as $from => $to) {
            $this->assertSame($to, $map->invoke($service, $from === '' ? null : $from), "{$from} should map to {$to}");
        }
    }
}
