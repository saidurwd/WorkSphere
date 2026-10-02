<?php

namespace Modules\Meetings\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingAgenda;
use Modules\Meetings\Models\MeetingTemplate;
use Modules\Meetings\Models\MeetingTemplateAgenda;
use Modules\Meetings\Models\MeetingType;
use Tests\TestCase;

/**
 * Meeting templates and their agendas.
 *
 * Phase 8 GAP-028 wired `meeting_templates`, which had two tables and no route at
 * all for most of this application's life. A template is only useful if scheduling
 * from it reproduces its agenda, so that assembly is what is tested here.
 */
class MeetingTemplateAgendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_template_requires_a_meeting_type(): void
    {
        $template = MeetingTemplate::factory()->create([
            'meeting_type_id' => MeetingType::factory(),
            'name' => 'Weekly standup',
        ]);

        $this->assertSame('Weekly standup', $template->name);
        $this->assertTrue($template->meetingType->exists);
    }

    public function test_a_template_holds_an_ordered_agenda(): void
    {
        $template = MeetingTemplate::factory()->create();

        foreach (['Apologies', 'Minutes', 'Action items'] as $index => $title) {
            MeetingTemplateAgenda::factory()->create([
                'template_id' => $template->id,
                'title' => $title,
                'sort_order' => $index + 1,
            ]);
        }

        $agenda = $template->agendaItems()
            ->orderBy('sort_order')
            ->pluck('title')
            ->all();

        $this->assertSame(['Apologies', 'Minutes', 'Action items'], $agenda);
    }

    public function test_a_template_defaults_to_inactive(): void
    {
        // A template in the picker is a template somebody will schedule by
        // accident. Being listed is a decision, not a default.
        $this->assertFalse(MeetingTemplate::factory()->create()->is_active);

        $this->assertTrue(MeetingTemplate::factory()->active()->create()->is_active);
    }

    public function test_a_template_tracks_who_built_it(): void
    {
        $builder = User::factory()->create();

        $template = MeetingTemplate::factory()->create(['created_by' => $builder->id]);

        $this->assertSame($builder->id, $template->created_by);
    }

    public function test_template_agenda_rows_are_scoped_to_their_template(): void
    {
        $mine = MeetingTemplate::factory()->create();
        $other = MeetingTemplate::factory()->create();

        MeetingTemplateAgenda::factory()->create(['template_id' => $mine->id, 'title' => 'Mine']);
        MeetingTemplateAgenda::factory()->create(['template_id' => $other->id, 'title' => 'Theirs']);

        $this->assertSame(['Mine'], $mine->agendaItems()->pluck('title')->all());
    }

    public function test_a_meeting_is_still_its_own_thing(): void
    {
        // Guards the relation direction: `agendas()` on a Meeting is the meeting's
        // OWN agenda, which is a different table from a template's agenda items.
        $meeting = Meeting::factory()->create();

        MeetingAgenda::factory()->create(['meeting_id' => $meeting->id, 'title' => 'On the agenda']);

        $this->assertSame(['On the agenda'], $meeting->agendas()->pluck('title')->all());
    }
}
