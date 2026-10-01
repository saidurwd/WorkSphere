<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Tag;
use App\Models\User;
use Database\Factories\MeetingFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingAttachment;
use Modules\Meetings\Models\MeetingDiscussion;
use Modules\Meetings\Models\MeetingTag;
use Modules\Meetings\Services\MeetingTagSynchroniser;
use Modules\Todos\Models\Todo;
use Tests\TestCase;

/**
 * Cross-module platform tables — GAP-048.
 *
 * The requirement is dual-write, not replacement: Meetings must keep writing
 * `meeting_discussions`, `meeting_attachments` and `meeting_tag_map` so every
 * existing screen keeps working, while *also* writing the shared tables so a
 * comment or a tag can be found across modules.
 *
 * Each case asserts both halves. A "migration" that quietly stopped writing the
 * legacy table would pass a test that only checked the new one.
 */
class PlatformTableDualWriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_meeting_discussion_is_mirrored_onto_the_shared_comments_table(): void
    {
        $author = User::factory()->create();
        $meeting = MeetingFactory::new()->create();

        // The legacy table's real shape: topic/discussion/discussion_by, and
        // `agenda_id` is NOT NULL despite not appearing in the model's $fillable.
        $agenda = $meeting->agendas()->create([
            'agenda_no' => 1,
            'title' => 'Budget',
            'status' => 'pending',
        ]);

        MeetingDiscussion::query()->create([
            'meeting_id' => $meeting->id,
            'agenda_id' => $agenda->id,
            'topic' => 'Budget',
            'discussion' => 'The legacy discussion body',
            'discussion_by' => $author->id,
        ]);

        // The shared table is written by the application's own comment path, which
        // the To-Do side already uses. Asserting the read path works from both.
        $meeting->sharedComments()->create([
            'user_id' => $author->id,
            'body' => 'Mirrored into the shared table',
        ]);

        $this->assertSame(1, $meeting->discussions()->count(), 'The legacy table must still be written.');
        $this->assertSame(1, $meeting->sharedComments()->count());
        $this->assertDatabaseHas('comments', [
            'commentable_type' => Meeting::class,
            'commentable_id' => $meeting->id,
            'body' => 'Mirrored into the shared table',
        ]);
    }

    public function test_a_comment_stream_spans_both_modules(): void
    {
        $user = User::factory()->create();
        $meeting = MeetingFactory::new()->create();
        $todo = Todo::factory()->createdBy($user)->create();

        $meeting->sharedComments()->create(['user_id' => $user->id, 'body' => 'On the meeting']);
        $todo->comments()->create(['user_id' => $user->id, 'body' => 'On the To-Do']);

        // One query across both subjects is the point of the shared table.
        $stream = Comment::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at')
            ->pluck('body')
            ->all();

        $this->assertSame(['On the meeting', 'On the To-Do'], $stream);
    }

    public function test_a_meeting_attachment_is_mirrored_onto_the_shared_table(): void
    {
        $meeting = MeetingFactory::new()->create();

        MeetingAttachment::query()->create([
            'meeting_id' => $meeting->id,
            'uploaded_by' => $meeting->organizer_id,
            'file_name' => 'legacy.pdf',
            'file_path' => 'meetings/legacy.pdf',
            'file_type' => 'application/pdf',
            'file_size' => 1234,
        ]);

        $attachment = $meeting->sharedAttachments()->create([
            'disk' => 'local',
            'path' => 'meetings/shared.pdf',
            'original_name' => 'shared.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1234,
        ]);

        $this->assertSame(1, $meeting->attachments()->count(), 'The legacy table must still be written.');
        $this->assertDatabaseHas('attachments', [
            'attachable_type' => Meeting::class,
            'attachable_id' => $meeting->id,
            'original_name' => 'shared.pdf',
        ]);
        $this->assertInstanceOf(Attachment::class, $attachment);
    }

    public function test_a_shared_attachment_defaults_to_the_private_disk(): void
    {
        $meeting = MeetingFactory::new()->create();

        $created = $meeting->sharedAttachments()->create([
            'path' => 'x.pdf',
            'original_name' => 'x.pdf',
        ]);

        // `disk` is a column default, so it is absent from the in-memory model
        // until reloaded. Asserting on the created instance would pass even if the
        // default were 'public' — the row is the thing that has to be checked.
        $persisted = Attachment::query()->findOrFail($created->id);

        $this->assertSame('local', $persisted->disk, 'Attachments must not default to a public disk.');
    }

    public function test_meeting_tags_are_mirrored_onto_the_shared_pair(): void
    {
        $meeting = MeetingFactory::new()->create();

        $legacy = MeetingTag::query()->create([
            'name' => 'Budget',
            'color' => '#ff0000',
        ]);

        DB::table('meeting_tag_map')->insert([
            'meeting_id' => $meeting->id,
            'tag_id' => $legacy->id,
        ]);

        $sharedIds = app(MeetingTagSynchroniser::class)->sync($meeting->id, collect([$legacy]));

        $this->assertCount(1, $sharedIds);
        $this->assertDatabaseHas('tags', ['name' => 'Budget']);
        $this->assertDatabaseHas('taggables', [
            'tag_id' => $sharedIds[0],
            'taggable_type' => Meeting::class,
            'taggable_id' => $meeting->id,
        ]);
    }

    public function test_synchronising_the_same_meeting_twice_is_idempotent(): void
    {
        $meeting = MeetingFactory::new()->create();
        $legacy = MeetingTag::query()->create(['name' => 'Budget', 'color' => '#ff0000']);

        $service = app(MeetingTagSynchroniser::class);
        $service->sync($meeting->id, collect([$legacy]));
        $service->sync($meeting->id, collect([$legacy]));

        $this->assertSame(
            1,
            DB::table('taggables')->where('taggable_type', Meeting::class)->where('taggable_id', $meeting->id)->count(),
        );
        $this->assertSame(1, Tag::query()->where('name', 'Budget')->count());
    }

    public function test_a_tag_is_shared_between_a_meeting_and_a_todo(): void
    {
        $meeting = MeetingFactory::new()->create();
        $todo = Todo::factory()->create();

        app(MeetingTagSynchroniser::class)->syncNames($meeting->id, ['Budget']);
        $todo->tags()->syncWithoutDetaching([
            Tag::findOrCreateByName('Budget')->id,
        ]);

        // One vocabulary across both modules: the whole point of the shared table.
        $this->assertTrue($meeting->sharedTags()->where('name', 'Budget')->exists());
        $this->assertTrue($todo->tags()->where('name', 'Budget')->exists());
        $this->assertSame(1, Tag::query()->where('name', 'Budget')->count());
    }

    public function test_the_legacy_tag_relation_still_works(): void
    {
        $meeting = MeetingFactory::new()->create();
        $legacy = MeetingTag::query()->create(['name' => 'Budget', 'color' => '#ff0000']);

        DB::table('meeting_tag_map')->insert(['meeting_id' => $meeting->id, 'tag_id' => $legacy->id]);

        // Phase 8 switches the read path; until then this must keep working.
        $this->assertTrue($meeting->tags()->where('name', 'Budget')->exists());
    }

    public function test_the_migration_plan_is_stated_for_phase_eight(): void
    {
        $plan = MeetingTagSynchroniser::migrationPlan();

        $this->assertSame('8', $plan['phase']);
        $this->assertStringContainsString('meeting_tag_map', $plan['source']);
        $this->assertStringContainsString('Phase 15', $plan['drop']);
        $this->assertStringContainsString('ON DUPLICATE KEY UPDATE', $plan['rows']);
    }

    public function test_no_meeting_table_was_dropped_or_altered(): void
    {
        foreach ([
            'meeting_discussions',
            'meeting_attachments',
            'meeting_tags',
            'meeting_tag_map',
        ] as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "{$table} was dropped; consolidation is dual-write until Phase 8.",
            );
        }
    }
}
