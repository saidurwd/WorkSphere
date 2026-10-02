<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Todos\Models\Todo;

/**
 * @extends Factory<ActivityLog>
 *
 * `subject_type` + `subject_id` are the columns consumers read from; `module_name`
 * + `record_id` are the deprecated pair retained during the migration window.
 * The factory fills the polymorphic pair only, which is the shape new code writes.
 */
class ActivityLogFactory extends Factory
{
    protected $model = ActivityLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject_type' => (new Todo)->getMorphClass(),
            'subject_id' => Todo::factory(),
            'action' => fake()->randomElement(['created', 'updated', 'completed', 'commented']),
            'old_value' => null,
            'new_value' => null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => 'Factory',
        ];
    }

    /**
     * A trail entry for a specific subject.
     *
     * @param  array<string, mixed>|null  $old
     * @param  array<string, mixed>|null  $new
     */
    public function forSubject(object $subject, string $action, ?array $old = null, ?array $new = null): static
    {
        return $this->state(fn (): array => [
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'old_value' => $old,
            'new_value' => $new,
        ]);
    }
}
