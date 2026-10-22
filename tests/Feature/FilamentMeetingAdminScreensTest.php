<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Factories\MeetingFactory;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Meetings\Filament\Pages\MeetingCalendar;
use Modules\Meetings\Filament\Pages\MeetingOverview;
use Modules\Meetings\Filament\Pages\MeetingReports;
use Modules\Meetings\Filament\Resources\ActionItems\Pages\ListMeetingActionItems;
use Modules\Meetings\Filament\Resources\Decisions\Pages\ListMeetingDecisions;
use Modules\Meetings\Filament\Resources\NotificationLogs\Pages\ListMeetingNotificationLogs;
use Modules\Meetings\Filament\Resources\Tags\Pages\CreateMeetingTag;
use Modules\Meetings\Filament\Resources\Tags\Pages\ListMeetingTags;
use Modules\Meetings\Filament\Resources\Templates\Pages\CreateMeetingTemplate;
use Modules\Meetings\Filament\Resources\Templates\Pages\EditMeetingTemplate;
use Modules\Meetings\Filament\Resources\Templates\Pages\ListMeetingTemplates;
use Modules\Meetings\Filament\Resources\Templates\Pages\ViewMeetingTemplate;
use Modules\Meetings\Filament\Resources\Types\Pages\CreateMeetingType;
use Modules\Meetings\Filament\Resources\Types\Pages\EditMeetingType;
use Modules\Meetings\Filament\Resources\Types\Pages\ListMeetingTypes;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Meetings\Models\MeetingDecision;
use Modules\Meetings\Models\MeetingNotificationLog;
use Modules\Meetings\Models\MeetingTag;
use Modules\Meetings\Models\MeetingTemplate;
use Modules\Meetings\Models\MeetingType;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Meeting types, tags, templates and the notification log in the workspace panel.
 */
class FilamentMeetingAdminScreensTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('workspace');
    }

    public function test_types_are_managed_with_manage_types_and_codes_are_unique(): void
    {
        $this->actingAs($this->userWithPermissions(['meeting.manage_types']));

        Livewire::test(CreateMeetingType::class)
            ->fillForm(['name' => 'Board', 'code' => 'BOARD', 'is_active' => true, 'sort_order' => 1])
            ->call('create')
            ->assertHasNoFormErrors();

        $type = MeetingType::query()->where('code', 'BOARD')->firstOrFail();
        $this->assertNotNull($type->created_by);

        Livewire::test(CreateMeetingType::class)
            ->fillForm(['name' => 'Other', 'code' => 'BOARD'])
            ->call('create')
            ->assertHasFormErrors(['code' => 'unique']);

        Livewire::test(EditMeetingType::class, ['record' => $type->getKey()])
            ->fillForm(['name' => 'Board of Directors'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('Board of Directors', $type->fresh()->name);

        Livewire::test(ListMeetingTypes::class)->callTableAction('delete', $type);
        $this->assertModelMissing($type);
    }

    public function test_tags_are_managed_with_manage_tags_and_names_are_unique(): void
    {
        $this->actingAs($this->userWithPermissions(['meeting.manage_tags']));
        MeetingTag::factory()->create(['name' => 'Finance']);

        Livewire::test(CreateMeetingTag::class)
            ->fillForm(['name' => 'Finance'])
            ->call('create')
            ->assertHasFormErrors(['name' => 'unique']);

        Livewire::test(CreateMeetingTag::class)
            ->fillForm(['name' => 'Legal', 'is_active' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('meeting_tags', ['name' => 'Legal']);
        Livewire::test(ListMeetingTags::class)->assertOk();
    }

    public function test_type_and_tag_changes_are_closed_to_everyone_else(): void
    {
        $this->actingAs($this->userWithPermissions(['meeting.view']));

        $this->get('/meeting-types')->assertOk();
        $this->get('/meeting-types/create')->assertForbidden();
        $this->get('/meeting-tags/create')->assertForbidden();

        $this->actingAs($this->plainUser());
        $this->get('/meeting-types')->assertForbidden();
    }

    private function templateForm(): array
    {
        return [
            'name' => 'Weekly stand-up',
            'meeting_type_id' => MeetingType::factory()->create()->id,
            'default_duration' => 30,
            'default_priority' => 'normal',
            'is_active' => true,
            'agendaItems' => [['title' => 'Blockers'], ['title' => 'Plans']],
        ];
    }

    public function test_a_template_is_created_with_its_agenda_in_order(): void
    {
        $this->actingAs($this->userWithPermissions(['meeting.manage_templates']));

        Livewire::test(CreateMeetingTemplate::class)
            ->fillForm($this->templateForm())
            ->call('create')
            ->assertHasNoFormErrors();

        $template = MeetingTemplate::query()->where('name', 'Weekly stand-up')->firstOrFail();
        $this->assertSame(['Blockers', 'Plans'], $template->agendaItems()->orderBy('sort_order')->pluck('title')->all());
        $this->assertDatabaseHas('activity_logs', ['action' => 'created']);
    }

    public function test_a_template_needs_a_name_and_a_type(): void
    {
        $this->actingAs($this->userWithPermissions(['meeting.manage_templates']));

        Livewire::test(CreateMeetingTemplate::class)
            ->fillForm(['name' => '', 'meeting_type_id' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'meeting_type_id' => 'required']);
    }

    public function test_editing_a_template_changes_its_agenda_items(): void
    {
        $this->actingAs($this->userWithPermissions(['meeting.manage_templates']));
        $template = MeetingTemplate::factory()->create();
        $template->agendaItems()->create(['title' => 'Old item', 'sort_order' => 0]);

        Livewire::test(EditMeetingTemplate::class, ['record' => $template->getKey()])
            ->fillForm(['agendaItems' => [['title' => 'New item']]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['New item'], $template->agendaItems()->pluck('title')->all());
    }

    public function test_a_meeting_is_scheduled_from_a_template_with_a_number_and_the_agenda(): void
    {
        $user = $this->userWithPermissions(['meeting.manage_templates']);
        $this->actingAs($user);
        $template = MeetingTemplate::factory()->create(['is_active' => true, 'default_duration' => 45]);
        $template->agendaItems()->create(['title' => 'Item one', 'sort_order' => 0]);
        $template->agendaItems()->create(['title' => 'Item two', 'sort_order' => 1]);

        Livewire::test(ViewMeetingTemplate::class, ['record' => $template->getKey()])
            ->callAction('schedule', [
                'title' => 'Sprint planning',
                'meeting_date' => now()->addDays(2)->toDateString(),
                'start_time' => '10:00',
                'end_time' => '11:00',
            ])
            ->assertHasNoActionErrors();

        $meeting = Meeting::query()->where('title', 'Sprint planning')->firstOrFail();
        $this->assertNotEmpty($meeting->meeting_no);
        $this->assertSame($template->id, $meeting->template_id);
        $this->assertSame($user->id, $meeting->organizer_id);
        $this->assertSame(['Item one', 'Item two'], $meeting->agendas()->orderBy('sort_order')->pluck('title')->all());
        $this->assertSame(45, (int) $meeting->agendas()->first()->estimated_minutes);
    }

    public function test_an_inactive_template_cannot_be_scheduled_and_the_end_must_follow_the_start(): void
    {
        $this->actingAs($this->userWithPermissions(['meeting.manage_templates']));
        $inactive = MeetingTemplate::factory()->create(['is_active' => false]);
        $active = MeetingTemplate::factory()->create(['is_active' => true]);

        Livewire::test(ViewMeetingTemplate::class, ['record' => $inactive->getKey()])->assertActionHidden('schedule');

        Livewire::test(ViewMeetingTemplate::class, ['record' => $active->getKey()])
            ->callAction('schedule', ['title' => 'Bad times', 'meeting_date' => now()->addDay()->toDateString(), 'start_time' => '11:00', 'end_time' => '10:00'])
            ->assertHasActionErrors(['end_time']);

        $this->assertDatabaseCount('meetings', 0);
    }

    public function test_deleting_a_template_keeps_the_meetings_it_produced(): void
    {
        $this->actingAs($this->userWithPermissions(['meeting.manage_templates']));
        $template = MeetingTemplate::factory()->create(['is_active' => true]);

        Livewire::test(ViewMeetingTemplate::class, ['record' => $template->getKey()])
            ->callAction('schedule', ['title' => 'Kept', 'meeting_date' => now()->addDay()->toDateString(), 'start_time' => '09:00', 'end_time' => '10:00']);

        Livewire::test(ListMeetingTemplates::class)->callTableAction('delete', $template);

        $this->assertModelMissing($template);
        $this->assertTrue(Meeting::query()->where('title', 'Kept')->exists());
    }

    public function test_templates_need_manage_templates_even_to_read(): void
    {
        MeetingTemplate::factory()->create();

        $this->actingAs($this->userWithPermissions(['meeting.view', 'meeting.create']));
        $this->get('/meeting-templates')->assertForbidden();
        $this->get('/meeting-templates/create')->assertForbidden();

        $this->actingAs($this->userWithPermissions(['meeting.manage_templates']));
        $this->get('/meeting-templates')->assertOk();
    }

    public function test_the_notification_log_needs_its_permission_and_only_a_super_admin_deletes(): void
    {
        $failed = [MeetingNotificationLog::factory()->create(['status' => 'failed']), MeetingNotificationLog::factory()->create(['status' => 'failed'])];
        $sent = MeetingNotificationLog::factory()->create(['status' => 'sent']);

        $this->actingAs($this->userWithPermissions(['meeting.view']));
        $this->get('/meeting-notification-logs')->assertForbidden();

        $this->actingAs($this->userWithPermissions(['meeting.view_notification_logs']));
        Livewire::test(ListMeetingNotificationLogs::class)
            ->assertCanSeeTableRecords([$sent])
            ->assertTableActionHidden('remove', $sent);

        $this->actingAs($this->superAdmin(['meeting.view_notification_logs']));
        Livewire::test(ListMeetingNotificationLogs::class)->filterTable('status', 'failed')->callAction('deleteFiltered');

        foreach ($failed as $row) {
            $this->assertModelMissing($row);
        }
        $this->assertModelExists($sent);

        Livewire::test(ListMeetingNotificationLogs::class)->callTableAction('remove', $sent);
        $this->assertModelMissing($sent);
    }

    private function mine(): array
    {
        $user = $this->userWithPermissions(['meeting.create']);

        return [$user, MeetingFactory::new()->create(['organizer_id' => $user->id]), MeetingFactory::new()->create()];
    }

    public function test_the_action_item_and_decision_registers_cover_only_visible_meetings(): void
    {
        [$user, $mine, $theirs] = $this->mine();
        $myItem = MeetingActionItem::factory()->create(['meeting_id' => $mine->id, 'assigned_to' => null]);
        $assignedToMe = MeetingActionItem::factory()->create(['meeting_id' => $theirs->id, 'assigned_to' => $user->id]);
        $theirItem = MeetingActionItem::factory()->create(['meeting_id' => $theirs->id, 'assigned_to' => null]);
        $myDecision = MeetingDecision::factory()->create(['meeting_id' => $mine->id]);
        $theirDecision = MeetingDecision::factory()->create(['meeting_id' => $theirs->id]);
        $this->actingAs($user);

        Livewire::test(ListMeetingActionItems::class)
            ->assertCanSeeTableRecords([$myItem, $assignedToMe])
            ->assertCanNotSeeTableRecords([$theirItem]);

        Livewire::test(ListMeetingDecisions::class)
            ->assertCanSeeTableRecords([$myDecision])
            ->assertCanNotSeeTableRecords([$theirDecision]);

        $this->actingAs($this->userWithPermissions(['meeting.view_all_actions']));
        Livewire::test(ListMeetingActionItems::class)->assertCanSeeTableRecords([$myItem, $theirItem]);
    }

    public function test_the_overdue_filter_finds_late_open_items(): void
    {
        [$user, $mine] = $this->mine();
        $late = MeetingActionItem::factory()->create(['meeting_id' => $mine->id, 'status' => 'open', 'due_date' => now()->subDays(3)]);
        $done = MeetingActionItem::factory()->create(['meeting_id' => $mine->id, 'status' => 'completed', 'due_date' => now()->subDays(3)]);
        $this->actingAs($user);

        Livewire::test(ListMeetingActionItems::class)
            ->filterTable('overdue')
            ->assertCanSeeTableRecords([$late])
            ->assertCanNotSeeTableRecords([$done]);
    }

    public function test_the_overview_counts_only_what_the_viewer_may_see(): void
    {
        [$user, $mine, $theirs] = $this->mine();
        MeetingActionItem::factory()->create(['meeting_id' => $mine->id, 'status' => 'open', 'assigned_to' => null]);
        MeetingActionItem::factory()->count(4)->create(['meeting_id' => $theirs->id, 'status' => 'open', 'assigned_to' => null]);
        $this->actingAs($user);

        $page = Livewire::test(MeetingOverview::class)->assertOk();

        $this->assertSame(1, $page->viewData('stats')['Open actions']);
    }

    public function test_reports_need_their_permission_and_are_scoped(): void
    {
        [$user, $mine, $theirs] = $this->mine();
        $someone = $this->plainUser();
        MeetingActionItem::factory()->create(['meeting_id' => $mine->id, 'assigned_to' => $someone->id, 'status' => 'open']);
        $stranger = User::factory()->create(['name' => 'Hidden Assignee']);
        MeetingActionItem::factory()->create(['meeting_id' => $theirs->id, 'assigned_to' => $stranger->id, 'status' => 'open']);

        $this->actingAs($user);
        $this->get('/meeting-reports')->assertForbidden();

        $viewer = $this->userWithPermissions(['meeting.view_reports', 'meeting.create']);
        $mineToo = MeetingFactory::new()->create(['organizer_id' => $viewer->id]);
        MeetingActionItem::factory()->create(['meeting_id' => $mineToo->id, 'assigned_to' => $someone->id, 'status' => 'open']);
        $this->actingAs($viewer);

        Livewire::test(MeetingReports::class)
            ->assertSee($someone->name)
            ->assertDontSee('Hidden Assignee');
    }

    public function test_the_calendar_shows_visible_meetings_and_due_actions_only(): void
    {
        $user = $this->userWithPermissions(['meeting.create']);
        $date = now()->startOfMonth()->addDays(8);
        $mine = MeetingFactory::new()->create(['organizer_id' => $user->id, 'title' => 'Board review', 'meeting_date' => $date]);
        MeetingFactory::new()->create(['title' => 'Closed door session', 'meeting_date' => $date]);
        MeetingActionItem::factory()->create(['meeting_id' => $mine->id, 'title' => 'Circulate notes', 'due_date' => $date, 'assigned_to' => null]);
        $other = MeetingFactory::new()->create();
        MeetingActionItem::factory()->create(['meeting_id' => $other->id, 'title' => 'Hidden follow-up', 'due_date' => $date, 'assigned_to' => null]);
        $this->actingAs($user);

        Livewire::test(MeetingCalendar::class)
            ->assertSee('Board review')
            ->assertSee('Circulate notes')
            ->assertDontSee('Closed door session')
            ->assertDontSee('Hidden follow-up');
    }
}
