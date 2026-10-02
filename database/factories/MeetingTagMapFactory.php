<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingTag;
use Modules\Meetings\Models\MeetingTagMap;

/**
 * @extends Factory<MeetingTagMap>
 *
 * The LEGACY tag pivot, superseded by `taggables`. (meeting_id, tag_id) is
 * unique, which is what stops a tag being applied to a meeting twice.
 */
class MeetingTagMapFactory extends Factory
{
    protected $model = MeetingTagMap::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'tag_id' => MeetingTag::factory(),
        ];
    }
}
