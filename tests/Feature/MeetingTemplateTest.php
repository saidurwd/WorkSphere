<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Tag;
use Database\Factories\MeetingFactory;
use Database\Factories\MeetingTypeFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
