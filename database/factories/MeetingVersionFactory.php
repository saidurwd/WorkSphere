<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingVersion;

/**
 * @extends Factory<MeetingVersion>
 *
 * A snapshot row. `version_no` is the revision number within a meeting and
 * defaults to 1; `snapshot_data` holds a real serialised structure rather than an
 * empty array, because a test that restores from this row would restore nothing
 * and pass without exercising the restore path.
 */
class MeetingVersionFactory extends Factory
{
    protected $model = MeetingVersion::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'meeting_id' => Meeting::factory(),
            'version_no' => 1,
            'snapshot_data' => [
                'title' => fake()->sentence(4),
                'status' => 'scheduled',
                'captured_at' => now()->toIso8601String(),
            ],
            'change_summary' => fake()->sentence(8),
            'created_by' => User::factory(),
        ];
    }

    public function version(int $number): static
    {
        return $this->state(fn (): array => ['version_no' => $number]);
    }
}
