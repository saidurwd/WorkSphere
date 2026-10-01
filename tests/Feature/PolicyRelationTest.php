<?php

namespace Tests\Feature;

use Database\Factories\MeetingFactory;
use Database\Factories\TaskFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Meetings\Models\MeetingParticipant;
use Modules\Tasks\Models\Task;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * `TaskPolicy::view()` walks into `meetingActionItems -> meeting -> participants`.
 * A policy referencing a relation that does not exist throws BadMethodCallException,
 * which surfaces as a 500 rather than a 403 — the worst possible outcome for an
 * authorization check. These cases walk every branch of the policy deliberately.
 */
class PolicyRelationTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_viewing_an_unowned_task_with_no_permission_evaluates_every_clause_without_erroring(): void
    {
        $outsider = $this->plainUser();
        $task = TaskFactory::new()->create();

        // All three clauses are false, so the meeting-visibility branch is reached.
        $this->assertFalse($outsider->can('view', $task));
    }

    public function test_a_task_originates_from_a_visible_meeting_is_viewable(): void
    {
        $organizer = $this->plainUser();
        $meeting = MeetingFactory::new()->organisedBy($organizer)->create();

        $actionItem = MeetingActionItem::query()->create([
            'meeting_id' => $meeting->id,
            'action_no' => 1,
            'title' => 'Do the thing',
            'priority' => 'high',
            'status' => 'open',
        ]);

        $task = TaskFactory::new()->create();
        $actionItem->update(['task_id' => $task->id]);

        $this->assertTrue($organizer->can('view', $task->fresh()));
    }

    public function test_a_task_originates_from_a_meeting_the_viewer_cannot_see_stays_hidden(): void
    {
        $organizer = $this->plainUser();
        $outsider = $this->plainUser();

        $meeting = MeetingFactory::new()->organisedBy($organizer)->create();
        $actionItem = MeetingActionItem::query()->create([
            'meeting_id' => $meeting->id,
            'action_no' => 1,
            'title' => 'Private',
            'priority' => 'high',
            'status' => 'open',
        ]);

        $task = TaskFactory::new()->create();
        $actionItem->update(['task_id' => $task->id]);

        $this->assertFalse($outsider->can('view', $task->fresh()));
    }

    public function test_a_meeting_participant_can_see_the_task_the_meeting_produced(): void
    {
        $organizer = $this->plainUser();
        $participant = $this->plainUser();

        $meeting = MeetingFactory::new()->organisedBy($organizer)->create();
        MeetingParticipant::query()->create([
            'meeting_id' => $meeting->id,
            'user_id' => $participant->id,
            'participant_type' => 'member',
        ]);

        $actionItem = MeetingActionItem::query()->create([
            'meeting_id' => $meeting->id,
            'action_no' => 1,
            'title' => 'Shared work',
            'priority' => 'high',
            'status' => 'open',
        ]);

        $task = TaskFactory::new()->create();
        $actionItem->update(['task_id' => $task->id]);

        $this->assertTrue($participant->can('view', $task->fresh()));
    }

    public function test_task_relation_names_used_by_policies_all_exist(): void
    {
        foreach (['user', 'responsibleUser', 'project', 'obligation', 'meetingActionItems', 'taskTransfers', 'remarks'] as $relation) {
            $this->assertTrue(
                Task::query()->getModel()->relationLoaded($relation) || method_exists(Task::class, $relation),
                "Task::{$relation}() is missing but a policy or view depends on it.",
            );
        }
    }
}
