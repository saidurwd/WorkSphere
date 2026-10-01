<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Tag;
use Database\Factories\MeetingFactory;
use Database\Factories\MeetingTypeFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingAttachment;
use Modules\Meetings\Models\MeetingTag;
use Modules\Meetings\Models\MeetingTemplate;
use Modules\Meetings\Models\MeetingTemplateAgenda;
use Modules\Meetings\Services\MeetingAttachmentSynchroniser;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Meeting templates — GAP-028.
 *
 * `meeting_templates` and `meeting_template_agendas` had models, relations and
 * two tables, and no route anywhere. These cases cover the CRUD and, more
 * importantly, that a meeting scheduled from a template is traceable back to it.
 */
class MeetingTemplateTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private function activeTemplate(): MeetingTemplate
    {
        $template = MeetingTemplate::query()->create([
            'name' => 'Weekly stand-up',
            'meeting_type_id' => MeetingTypeFactory::new()->create()->id,
            'default_priority' => 'normal',
            'is_active' => true,
        ]);

        MeetingTemplateAgenda::query()->create([
            'template_id' => $template->id,
            'title' => 'Blockers',
            'sort_order' => 0,
        ]);

        MeetingTemplateAgenda::query()->create([
            'template_id' => $template->id,
            'title' => 'Progress',
            'sort_order' => 1,
        ]);

        return $template->fresh();
    }

    public function test_a_template_can_be_created_with_an_agenda(): void
    {
        $user = $this->userWithPermissions(['meeting.manage_templates']);

        $this->actingAs($user)
            ->post(route('meetings.templates.store'), [
                'name' => 'Sprint review',
                'meeting_type_id' => MeetingTypeFactory::new()->create()->id,
                'default_priority' => 'important',
                'is_active' => 1,
                'agenda' => [0 => 'Demo', 1 => 'Retro', 2 => '   '],
            ])
            ->assertRedirect();

        $template = MeetingTemplate::query()->where('name', 'Sprint review')->firstOrFail();

        $this->assertSame(2, $template->agendaItems()->count(), 'Blank agenda rows are skipped.');

        $this->assertSame(
            ['Demo', 'Retro'],
            $template->agendaItems()->orderBy('sort_order')->pluck('title')->all(),
        );
    }

    public function test_a_template_needs_a_name_and_a_type(): void
    {
        $user = $this->userWithPermissions(['meeting.manage_templates']);

        $this->actingAs($user)
            ->post(route('meetings.templates.store'), ['name' => '', 'default_priority' => 'normal'])
            ->assertSessionHasErrors(['name', 'meeting_type_id']);
    }

    public function test_a_template_needs_a_valid_priority(): void
    {
        $user = $this->userWithPermissions(['meeting.manage_templates']);

        $this->actingAs($user)
            ->post(route('meetings.templates.store'), [
                'name' => 'Bad priority',
                'meeting_type_id' => MeetingTypeFactory::new()->create()->id,
                'default_priority' => 'whenever',
            ])
            ->assertSessionHasErrors('default_priority');
    }

    public function test_a_meeting_can_be_scheduled_from_a_template(): void
    {
        $user = $this->userWithPermissions(['meeting.manage_templates']);
        $template = $this->activeTemplate();

        $this->actingAs($user)
            ->post(route('meetings.templates.schedule', $template), [
                'title' => 'Sprint 42 stand-up',
                'meeting_date' => '2026-10-05',
                'start_time' => '10:00',
                'end_time' => '11:00',
            ])
            ->assertRedirect();

        $meeting = Meeting::query()->where('title', 'Sprint 42 stand-up')->firstOrFail();

        $this->assertSame(
            $template->id,
            $meeting->template_id,
            'A template-built meeting must be traceable to its template.',
        );
        $this->assertSame(2, $meeting->agendas()->count(), 'The standing agenda is copied.');
        $this->assertSame('normal', $meeting->priority);
    }

    public function test_a_scheduled_meeting_gets_a_meeting_number(): void
    {
        $user = $this->userWithPermissions(['meeting.manage_templates']);
        $template = $this->activeTemplate();

        $this->actingAs($user)->post(route('meetings.templates.schedule', $template), [
            'title' => 'Numbered',
            'meeting_date' => '2026-10-05',
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $meeting = Meeting::query()->where('title', 'Numbered')->firstOrFail();

        $this->assertNotNull($meeting->meeting_no);
        $this->assertStringStartsWith('MTG-2026-', $meeting->meeting_no);
    }

    public function test_an_inactive_template_cannot_be_scheduled(): void
    {
        $user = $this->userWithPermissions(['meeting.manage_templates']);
        $template = $this->activeTemplate();
        $template->update(['is_active' => false]);

        $this->actingAs($user)
            ->post(route('meetings.templates.schedule', $template->fresh()), [
                'title' => 'Should not exist',
                'meeting_date' => '2026-10-05',
                'start_time' => '10:00',
                'end_time' => '11:00',
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, Meeting::query()->count());
    }

    public function test_the_end_time_must_follow_the_start(): void
    {
        $user = $this->userWithPermissions(['meeting.manage_templates']);
        $template = $this->activeTemplate();

        $this->actingAs($user)
            ->post(route('meetings.templates.schedule', $template), [
                'title' => 'Backwards',
                'meeting_date' => '2026-10-05',
                'start_time' => '11:00',
                'end_time' => '10:00',
            ])
            ->assertSessionHasErrors('end_time');
    }

    public function test_deleting_a_template_does_not_delete_the_meetings_it_produced(): void
    {
        $user = $this->userWithPermissions(['meeting.manage_templates']);
        $template = $this->activeTemplate();

        $this->actingAs($user)->post(route('meetings.templates.schedule', $template), [
            'title' => 'Survivor',
            'meeting_date' => '2026-10-05',
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $template->delete();

        $this->assertSame(1, Meeting::query()->count(), 'A meeting outlives the shape it was created from.');
        $this->assertNull(
            Meeting::query()->first()->template_id,
            'The pointer is cleared rather than the meeting cascading.',
        );
    }

    public function test_a_stranger_cannot_create_a_template(): void
    {
        $this->actingAs($this->plainUser())
            ->post(route('meetings.templates.store'), [
                'name' => 'Not mine',
                'meeting_type_id' => MeetingTypeFactory::new()->create()->id,
                'default_priority' => 'normal',
            ])
            ->assertForbidden();
    }

    public function test_the_template_screens_render(): void
    {
        $user = $this->userWithPermissions(['meeting.manage_templates']);
        $template = $this->activeTemplate();

        $this->actingAs($user)->get(route('meetings.templates.index'))->assertOk();
        $this->actingAs($user)->get(route('meetings.templates.create'))->assertOk();
        $this->actingAs($user)->get(route('meetings.templates.show', $template))->assertOk();
        $this->actingAs($user)->get(route('meetings.templates.edit', $template))->assertOk();
    }

    public function test_templates_require_authentication(): void
    {
        $this->get(route('meetings.templates.index'))->assertRedirect(route('login'));
    }

    // ---- Attachment cascade tightening -------------------------------------

    public function test_deleting_a_meeting_keeps_its_attachments(): void
    {
        $organizer = $this->plainUser();
        $meeting = MeetingFactory::new()->organisedBy($organizer)->create();

        $attachment = MeetingAttachment::query()->create([
            'meeting_id' => $meeting->id,
            'uploaded_by' => $organizer->id,
            'file_name' => 'minutes.pdf',
            'file_path' => 'meeting-attachments/minutes.pdf',
        ]);

        $meeting->delete();

        // An attachment is evidence. Deleting a meeting must not destroy the
        // document that recorded what happened in it.
        $this->assertNotNull(MeetingAttachment::query()->find($attachment->id));
        $this->assertNull(
            MeetingAttachment::query()->find($attachment->id)->meeting_id,
            'The pointer back to the parent is cleared.',
        );
    }

    public function test_an_attachment_dual_writes_onto_the_shared_table(): void
    {
        Storage::fake('public');

        $organizer = $this->userWithPermissions(['meeting.manage_templates']);
        $meeting = MeetingFactory::new()->organisedBy($organizer)->create();

        $file = UploadedFile::fake()->create('minutes.pdf', 128, 'application/pdf');

        $this->actingAs($organizer)
            ->post(route('meetings.attachments.store', $meeting), [
                'file' => $file,
                'description' => 'Signed minutes',
            ])
            ->assertRedirect();

        // Both tables written.
        $this->assertSame(1, MeetingAttachment::query()->where('meeting_id', $meeting->id)->count());
        $this->assertSame(1, Attachment::query()
            ->where('attachable_type', Meeting::class)
            ->where('attachable_id', $meeting->id)
            ->count());
    }

    public function test_the_shared_attachment_records_the_disk_actually_used(): void
    {
        Storage::fake('public');

        $organizer = $this->userWithPermissions(['meeting.manage_templates']);
        $meeting = MeetingFactory::new()->organisedBy($organizer)->create();

        $legacy = MeetingAttachment::query()->create([
            'meeting_id' => $meeting->id,
            'uploaded_by' => $organizer->id,
            'file_name' => 'report.pdf',
            'file_path' => 'meeting-attachments/report.pdf',
            'file_size' => 10,
        ]);

        app(MeetingAttachmentSynchroniser::class)->mirror($meeting, $legacy, 'public');

        $shared = Attachment::query()
            ->where('attachable_type', Meeting::class)
            ->where('attachable_id', $meeting->id)
            ->firstOrFail();

        // The row must not claim the private default for a file on the public disk.
        $this->assertSame('public', $shared->disk);
    }

    public function test_the_attachment_backfill_is_idempotent(): void
    {
        $organizer = $this->plainUser();
        $meeting = MeetingFactory::new()->organisedBy($organizer)->create();

        foreach (range(1, 3) as $index) {
            MeetingAttachment::query()->create([
                'meeting_id' => $meeting->id,
                'uploaded_by' => $organizer->id,
                'file_name' => "doc-{$index}.pdf",
                'file_path' => "meeting-attachments/doc-{$index}.pdf",
            ]);
        }

        $service = app(MeetingAttachmentSynchroniser::class);

        $this->assertSame(3, $service->backfill());
        $this->assertSame(0, $service->backfill(), 'A second backfill must mirror nothing.');
        $this->assertSame(3, Attachment::query()->count());
    }

    public function test_the_backfill_command_runs_and_reports(): void
    {
        $organizer = $this->plainUser();
        $meeting = MeetingFactory::new()->organisedBy($organizer)->create();

        $tag = MeetingTag::query()->create(['name' => 'Budget']);
        DB::table('meeting_tag_map')->insert(['meeting_id' => $meeting->id, 'tag_id' => $tag->id]);

        foreach (range(1, 2) as $index) {
            MeetingAttachment::query()->create([
                'meeting_id' => $meeting->id,
                'uploaded_by' => $organizer->id,
                'file_name' => "x-{$index}.pdf",
                'file_path' => "meeting-attachments/x-{$index}.pdf",
            ]);
        }

        $this->artisan('meetings:backfill-platform-tables')
            ->expectsOutputToContain('Tags mirrored: 1')
            ->expectsOutputToContain('Attachments mirrored: 2')
            ->assertSuccessful();

        // Re-running must be a no-op, which is what makes it resumable.
        $this->artisan('meetings:backfill-platform-tables')
            ->expectsOutputToContain('Tags mirrored: 1')
            ->assertSuccessful();

        $this->assertSame(1, Tag::query()->where('name', 'Budget')->count());
    }
}
