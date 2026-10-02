<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingAttachment;

/**
 * @extends Factory<MeetingAttachment>
 *
 * This is the MODULE table that Phase 8 replaced with the shared `attachments`.
 * The factory stays because the dual-write still populates it, and a factory for
 * the legacy path is what proves the dual write is not dead.
 *
 * `file_path` is under a random directory for the same reason as
 * `AttachmentFactory`: two attachments in one test must not share a file.
 */
class MeetingAttachmentFactory extends Factory
{
    protected $model = MeetingAttachment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word().'.'.fake()->randomElement(['pdf', 'png', 'docx']);

        return [
            'meeting_id' => Meeting::factory(),
            'discussion_id' => null,
            'decision_id' => null,
            'action_item_id' => null,
            'file_name' => $name,
            'file_path' => 'meetings/'.Str::random(20).'/'.$name,
            'file_type' => fake()->randomElement(['application/pdf', 'image/png']),
            'file_size' => fake()->numberBetween(1024, 5_000_000),
            'uploaded_by' => User::factory(),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
