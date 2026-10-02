<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Meetings\Models\MeetingTemplate;
use Modules\Meetings\Models\MeetingTemplateAgenda;

/**
 * @extends Factory<MeetingTemplateAgenda>
 *
 * `sort_order` is the presentation order inside the template and is set to match
 * the row count, so a template built with three agendas schedules them in the
 * order a reader expects rather than in an arbitrary one.
 */
class MeetingTemplateAgendaFactory extends Factory
{
    protected $model = MeetingTemplateAgenda::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'template_id' => MeetingTemplate::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->sentence(),
            'sort_order' => 1,
        ];
    }
}
