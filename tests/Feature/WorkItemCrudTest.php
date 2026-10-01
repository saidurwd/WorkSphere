<?php

namespace Tests\Feature;

use Database\Factories\MeetingFactory;
use Database\Factories\ObligationFactory;
use Database\Factories\TaskFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Meetings\Models\Meeting;
use Modules\Obligations\Models\Obligation;
use Modules\Tasks\Models\Task;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * Happy-path CRUD plus the key failure modes, per the Phase 2 test baseline.
 * These complement the policy tests: those prove who may act, these prove that an
 * authorised actor's request is actually honoured and that invalid input is
 * rejected rather than silently accepted.
 */
class WorkItemCrudTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    // ---- Tasks ------------------------------------------------------------

    public function test_a_task_can_be_created_read_updated_and_deleted(): void
    {
        $user = $this->userWithPermissions(['task.create', 'task.view', 'task.update', 'task.delete']);
        $responsible = $this->plainUser();

        $this->actingAs($user)
            ->post(route('tasks.store'), $this->taskPayload($responsible))
            ->assertRedirect(route('tasks.index'));

        $task = Task::query()->firstOrFail();

        $this->actingAs($user)->get(route('tasks.show', $task))->assertOk();
        $this->actingAs($user)->get(route('tasks.edit', $task))->assertOk();

        $this->actingAs($user)
            ->put(route('tasks.update', $task), $this->taskPayload($responsible, title: 'Renamed task'))
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'Renamed task']);

        $this->actingAs($user)
            ->delete(route('tasks.destroy', $task))
            ->assertRedirect(route('tasks.index'));

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_creating_a_task_stamps_the_completion_time_when_marked_complete(): void
    {
        $user = $this->userWithPermissions(['task.create', 'task.view']);

        $this->actingAs($user)
            ->post(route('tasks.store'), $this->taskPayload($this->plainUser(), status: 'completed'))
            ->assertRedirect(route('tasks.index'));

        $this->assertNotNull(Task::query()->firstOrFail()->completed_at);
    }

    public function test_creating_a_task_without_a_title_is_rejected(): void
    {
        $user = $this->userWithPermissions(['task.create', 'task.view']);

        $this->actingAs($user)
            ->post(route('tasks.store'), $this->taskPayload($this->plainUser(), title: ''))
            ->assertSessionHasErrors('title');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_creating_a_task_with_an_unknown_status_is_rejected(): void
    {
        $user = $this->userWithPermissions(['task.create', 'task.view']);

        $this->actingAs($user)
            ->post(route('tasks.store'), $this->taskPayload($this->plainUser(), status: 'wat'))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_a_remark_can_be_added_to_a_task_the_actor_owns(): void
    {
        $user = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($user)->create();

        $this->actingAs($user)
            ->post(route('tasks.remarks.store', $task), ['remark' => 'Blocked on legal'])
            ->assertRedirect();

        $this->assertDatabaseHas('task_remarks', ['task_id' => $task->id, 'remark' => 'Blocked on legal']);
    }

    public function test_an_empty_remark_is_rejected(): void
    {
        $user = $this->plainUser();
        $task = TaskFactory::new()->ownedBy($user)->create();

        $this->actingAs($user)
            ->post(route('tasks.remarks.store', $task), ['remark' => ''])
            ->assertSessionHasErrors('remark');

        $this->assertDatabaseCount('task_remarks', 0);
    }

    // ---- Meetings ---------------------------------------------------------

    public function test_a_meeting_can_be_created_read_updated_and_deleted(): void
    {
        $organizer = $this->plainUser();
        $meeting = MeetingFactory::new()->organisedBy($organizer)->create();

        $this->actingAs($organizer)->get(route('meetings.show', $meeting))->assertOk();

        $this->actingAs($organizer)
            ->put(route('meetings.update', $meeting), $this->meetingPayload($meeting, title: 'Renamed meeting'))
            ->assertRedirect(route('meetings.show', $meeting));

        $this->assertDatabaseHas('meetings', ['id' => $meeting->id, 'title' => 'Renamed meeting']);

        $this->actingAs($organizer)
            ->delete(route('meetings.destroy', $meeting))
            ->assertRedirect(route('meetings.index'));

        // Asserted through the model rather than the table so this test does not
        // encode whether the delete is soft or hard. See the report: `Meeting`
        // declares `#[SoftDeletes]`, an attribute class that does not exist in this
        // Laravel version, so the delete is currently a hard delete despite the
        // `deleted_at` column the migration defines.
        $this->assertNull(Meeting::query()->find($meeting->id));
    }

    public function test_a_completed_meeting_cannot_be_deleted(): void
    {
        $organizer = $this->plainUser();
        $meeting = MeetingFactory::new()->organisedBy($organizer)->completed()->create();

        $this->actingAs($organizer)
            ->delete(route('meetings.destroy', $meeting))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('meetings', ['id' => $meeting->id]);
    }

    public function test_updating_a_meeting_without_a_title_is_rejected(): void
    {
        $organizer = $this->plainUser();
        $meeting = MeetingFactory::new()->organisedBy($organizer)->create();

        $this->actingAs($organizer)
            ->put(route('meetings.update', $meeting), $this->meetingPayload($meeting, title: ''))
            ->assertSessionHasErrors('title');
    }

    public function test_a_meeting_can_be_started_and_completed_by_its_organizer(): void
    {
        $organizer = $this->plainUser();
        $meeting = MeetingFactory::new()->organisedBy($organizer)->create();

        $this->actingAs($organizer)
            ->post(route('meetings.start', $meeting))
            ->assertRedirect();

        $this->assertDatabaseHas('meetings', ['id' => $meeting->id, 'status' => 'in_progress']);

        $this->actingAs($organizer)
            ->post(route('meetings.complete', $meeting))
            ->assertRedirect();

        $this->assertDatabaseHas('meetings', ['id' => $meeting->id, 'status' => 'completed']);
    }

    // ---- Obligations ------------------------------------------------------

    public function test_an_obligation_can_be_created_read_updated_and_deleted(): void
    {
        $owner = $this->plainUser();
        $obligation = ObligationFactory::new()->create(['owner_user_id' => $owner->id]);

        $this->actingAs($owner)->get(route('obligations.show', $obligation))->assertOk();
        $this->actingAs($owner)->get(route('obligations.edit', $obligation))->assertOk();

        $this->actingAs($owner)
            ->put(route('obligations.update', $obligation), $this->obligationPayload($obligation, title: 'Renamed obligation'))
            ->assertRedirect(route('obligations.show', $obligation));

        $this->assertDatabaseHas('obligations', ['id' => $obligation->id, 'title' => 'Renamed obligation']);

        $this->actingAs($owner)
            ->delete(route('obligations.destroy', $obligation))
            ->assertRedirect(route('obligations.index'));

        $this->assertDatabaseCount('obligations', 0);
    }

    public function test_updating_an_obligation_without_a_title_is_rejected(): void
    {
        $owner = $this->plainUser();
        $obligation = ObligationFactory::new()->create(['owner_user_id' => $owner->id]);

        $this->actingAs($owner)
            ->put(route('obligations.update', $obligation), $this->obligationPayload($obligation, title: ''))
            ->assertSessionHasErrors('title');
    }

    /**
     * @return array<string, mixed>
     */
    private function taskPayload($responsible, string $title = 'Ship the thing', string $status = 'pending'): array
    {
        return [
            'title' => $title,
            'description' => 'A description',
            'priority' => 'high',
            'status' => $status,
            'due_date' => now()->addWeek()->format('Y-m-d'),
            'responsible_user_id' => $responsible->id,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function meetingPayload(Meeting $meeting, string $title = 'Quarterly review'): array
    {
        return [
            'title' => $title,
            'meeting_type_id' => $meeting->meeting_type_id,
            'organizer_id' => $meeting->organizer_id,
            'meeting_date' => now()->addWeek()->format('Y-m-d'),
            'start_time' => '10:00',
            'end_time' => '11:30',
            'priority' => 'normal',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function obligationPayload(Obligation $obligation, string $title = 'Vendor contract'): array
    {
        return [
            'title' => $title,
            'description' => 'desc',
            'obligation_type_id' => $obligation->obligation_type_id,
            'category_id' => $obligation->category_id,
            'company_id' => $obligation->company_id,
            'department_id' => $obligation->department_id,
            'location_id' => $obligation->location_id,
            'vendor_id' => $obligation->vendor_id,
            'owner_user_id' => $obligation->owner_user_id,
            'start_date' => now()->format('Y-m-d'),
            'expiry_date' => now()->addYear()->format('Y-m-d'),
            'renewal_required' => true,
            'auto_renew' => false,
            'priority' => 'medium',
            'risk_level' => 'low',
            'estimated_cost' => 1000,
            'currency' => 'BDT',
            'status' => 'active',
        ];
    }
}
