<?php

namespace Tests\Feature;

use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * TodoService's state machine — TODO-MODULE-SPECIFICATION.md §3.2.
 *
 * The critical property is the negative one: an illegal transition must throw.
 * A transition that quietly no-ops is indistinguishable from a lost write, and
 * the user is left believing a To-Do moved when it did not.
 */
class TodoTransitionTest extends TestCase
{
    use RefreshDatabase;

    private TodoService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(TodoService::class);
    }

    /**
     * Every legal transition in the §3.2 graph.
     *
     * @return array<string, array{string, string}>
     */
    public static function legalTransitionProvider(): array
    {
        return [
            'inbox → planned' => ['inbox', 'planned'],
            'inbox → in_progress' => ['inbox', 'in_progress'],
            'inbox → waiting' => ['inbox', 'waiting'],
            'inbox → archived' => ['inbox', 'archived'],
            'planned → in_progress' => ['planned', 'in_progress'],
            'planned → waiting' => ['planned', 'waiting'],
            'planned → inbox' => ['planned', 'inbox'],
            'in_progress → completed' => ['in_progress', 'completed'],
            'in_progress → waiting' => ['in_progress', 'waiting'],
            'in_progress → planned' => ['in_progress', 'planned'],
            'waiting → in_progress' => ['waiting', 'in_progress'],
            'waiting → planned' => ['waiting', 'planned'],
            'completed → in_progress' => ['completed', 'in_progress'],
        ];
    }

    #[DataProvider('legalTransitionProvider')]
    public function test_a_legal_transition_is_applied(string $from, string $to): void
    {
        $actor = User::factory()->create();
        $todo = $this->todoInStatus($actor, WorkItemStatus::from($from));

        // `waiting` requires a reason (§3.2), so supply one for the legal case.
        $todo->update(['waiting_on' => 'Upstream team']);

        $this->service->transition($actor, $todo, WorkItemStatus::from($to));

        $this->assertSame($to, $todo->fresh()->status?->value);
    }

    /**
     * Every illegal transition in the graph.
     *
     * @return array<string, array{string, string}>
     */
    public static function illegalTransitionProvider(): array
    {
        return [
            'inbox → completed' => ['inbox', 'completed'],
            'planned → completed' => ['planned', 'completed'],
            'waiting → completed' => ['waiting', 'completed'],
            'completed → inbox' => ['completed', 'inbox'],
            'completed → planned' => ['completed', 'planned'],
            'completed → waiting' => ['completed', 'waiting'],
            'in_progress → inbox' => ['in_progress', 'inbox'],
            'archived → in_progress' => ['archived', 'in_progress'],
            'archived → completed' => ['archived', 'completed'],
        ];
    }

    #[DataProvider('illegalTransitionProvider')]
    public function test_an_illegal_transition_throws_and_changes_nothing(string $from, string $to): void
    {
        $actor = User::factory()->create();
        $todo = $this->todoInStatus($actor, WorkItemStatus::from($from));

        try {
            $this->service->transition($actor, $todo, WorkItemStatus::from($to));
            $this->fail("{$from} → {$to} should have been refused.");
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('status', $e->errors());
        }

        $this->assertSame(
            $from,
            $todo->fresh()->status?->value,
            'A refused transition must leave the row untouched.',
        );
    }

    public function test_waiting_requires_waiting_on(): void
    {
        $actor = User::factory()->create();
        $todo = $this->todoInStatus($actor, WorkItemStatus::InProgress);

        $this->expectException(ValidationException::class);

        $this->service->transition($actor, $todo, WorkItemStatus::Waiting);
    }

    public function test_waiting_is_accepted_when_waiting_on_is_supplied(): void
    {
        $actor = User::factory()->create();
        $todo = $this->todoInStatus($actor, WorkItemStatus::InProgress);
        $todo->update(['waiting_on' => 'Legal team']);

        $this->service->transition($actor, $todo, WorkItemStatus::Waiting);

        $this->assertSame('Legal team', $todo->fresh()->waiting_on);
    }

    public function test_leaving_waiting_clears_waiting_on(): void
    {
        $actor = User::factory()->create();
        $todo = $this->todoInStatus($actor, WorkItemStatus::Waiting);
        $todo->update(['waiting_on' => 'Legal team']);

        $this->service->transition($actor, $todo, WorkItemStatus::InProgress);

        $this->assertNull($todo->fresh()->waiting_on);
    }

    public function test_completing_stamps_completed_at_and_completed_by(): void
    {
        $actor = User::factory()->create();
        $todo = $this->todoInStatus($actor, WorkItemStatus::InProgress);

        $this->service->complete($actor, $todo);

        $fresh = $todo->fresh();

        $this->assertSame(WorkItemStatus::Completed, $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertSame($actor->id, $fresh->completed_by);
    }

    public function test_reopening_clears_both_completion_columns(): void
    {
        $actor = User::factory()->create();
        $todo = $this->todoInStatus($actor, WorkItemStatus::Completed);
        $todo->update(['completed_at' => now()->subDay(), 'completed_by' => $actor->id]);

        $this->service->reopen($actor, $todo);

        $fresh = $todo->fresh();

        $this->assertSame(WorkItemStatus::InProgress, $fresh->status);
        $this->assertNull($fresh->completed_at, 'A reopened To-Do cannot still claim a completion time.');
        $this->assertNull($fresh->completed_by, 'A reopened To-Do cannot still claim a completer.');
    }

    public function test_archived_from_records_the_prior_status(): void
    {
        $actor = User::factory()->create();
        $todo = $this->todoInStatus($actor, WorkItemStatus::InProgress);

        $this->service->archive($actor, $todo);

        $this->assertSame('in_progress', $todo->fresh()->archived_from);
    }

    public function test_restoring_returns_the_prior_status_not_a_default(): void
    {
        $actor = User::factory()->create();
        $todo = $this->todoInStatus($actor, WorkItemStatus::Waiting);
        $todo->update(['waiting_on' => 'Finance']);

        $this->service->archive($actor, $todo);
        $this->service->restore($actor, $todo->fresh());

        $fresh = $todo->fresh();

        $this->assertSame(WorkItemStatus::Waiting, $fresh->status, 'Restore must be a true reversal.');
        $this->assertNull($fresh->archived_from);
    }

    public function test_archiving_twice_is_refused(): void
    {
        $actor = User::factory()->create();
        $todo = $this->todoInStatus($actor, WorkItemStatus::InProgress);

        $this->service->archive($actor, $todo);

        $this->expectException(ValidationException::class);

        $this->service->archive($actor, $todo->fresh());
    }

    public function test_restoring_a_non_archived_todo_is_refused(): void
    {
        $actor = User::factory()->create();
        $todo = $this->todoInStatus($actor, WorkItemStatus::InProgress);

        $this->expectException(ValidationException::class);

        $this->service->restore($actor, $todo);
    }

    public function test_update_refuses_to_set_status_directly(): void
    {
        $actor = User::factory()->create();
        $todo = $this->todoInStatus($actor, WorkItemStatus::Inbox);

        // Even if a caller passes status, the service must not let it through:
        // the transition graph is the only path to a new status.
        $this->service->update($actor, $todo, [
            'title' => 'Renamed',
            'status' => WorkItemStatus::Completed->value,
        ]);

        $this->assertSame(WorkItemStatus::Inbox, $todo->fresh()->status);
        $this->assertSame('Renamed', $todo->fresh()->title);
    }

    public function test_create_records_the_actor_as_creator(): void
    {
        $actor = User::factory()->create();

        $todo = $this->service->create($actor, ['title' => 'From the service']);

        $this->assertSame($actor->id, $todo->creator_id);
        $this->assertSame(WorkItemStatus::Inbox, $todo->status);
    }

    public function test_every_mutation_writes_an_activity_row_with_old_and_new_values(): void
    {
        $actor = User::factory()->create();
        $todo = $this->service->create($actor, ['title' => 'Tracked']);

        $this->service->update($actor, $todo, ['title' => 'Tracked and renamed']);

        $update = ActivityLog::query()
            ->where('module_name', 'Todo')
            ->where('action', 'updated')
            ->where('record_id', $todo->id)
            ->firstOrFail();

        $this->assertSame('Tracked', $update->old_value['title']);
        $this->assertSame('Tracked and renamed', $update->new_value['title']);
        $this->assertSame($actor->id, $update->user_id);
    }

    public function test_assign_records_the_previous_holder(): void
    {
        $creator = User::factory()->create();
        $first = User::factory()->create();
        $second = User::factory()->create();

        $todo = $this->service->create($creator, ['title' => 'Transferable', 'assignee_id' => $first->id]);

        $this->service->assign($creator, $todo, $second->id);

        $row = ActivityLog::query()
            ->where('action', 'assigned')
            ->where('record_id', $todo->id)
            ->firstOrFail();

        $this->assertSame($first->id, $row->old_value['assignee_id']);
        $this->assertSame($second->id, $row->new_value['assignee_id']);
        $this->assertSame($second->id, $todo->fresh()->assignee_id);
    }

    public function test_assigning_to_nobody_is_allowed(): void
    {
        $creator = User::factory()->create();
        $todo = $this->service->create($creator, ['title' => 'Unassign me', 'assignee_id' => $creator->id]);

        $this->service->assign($creator, $todo, null);

        $this->assertNull($todo->fresh()->assignee_id);
    }

    public function test_delete_writes_an_activity_row(): void
    {
        $actor = User::factory()->create();
        $todo = $this->service->create($actor, ['title' => 'Doomed']);

        $this->service->destroy($actor, $todo);

        $this->assertNull(Todo::query()->find($todo->id), 'destroy() must soft delete.');

        $this->assertDatabaseHas('activity_logs', [
            'record_id' => $todo->id,
            'action' => 'deleted',
        ]);
    }

    /**
     * §3.1's To-Do vocabulary. WorkItemStatus is shared with Tasks and meeting
     * action items, so the transition graph is only required to be total over
     * the statuses a To-Do can actually hold.
     *
     * @return list<string>
     */
    private static function todoStatuses(): array
    {
        return [
            WorkItemStatus::Inbox->value,
            WorkItemStatus::Planned->value,
            WorkItemStatus::InProgress->value,
            WorkItemStatus::Waiting->value,
            WorkItemStatus::Completed->value,
            WorkItemStatus::Archived->value,
        ];
    }

    public function test_the_transition_graph_is_total_over_the_todo_vocabulary(): void
    {
        // Guards against a status being added to the To-Do vocabulary without a
        // decision about its outgoing moves: an empty list is a dead end.
        foreach (self::todoStatuses() as $value) {
            $targets = TodoService::allowedTransitionsFrom(WorkItemStatus::from($value));

            $this->assertNotEmpty($targets, "{$value} has no outgoing transitions, so it is a dead end.");

            foreach ($targets as $target) {
                $this->assertContains(
                    $target,
                    WorkItemStatus::values(),
                    "{$value} → {$target} names a status that does not exist.",
                );
            }
        }
    }

    public function test_every_transition_target_is_itself_a_reachable_todo_status(): void
    {
        foreach (self::todoStatuses() as $value) {
            foreach (TodoService::allowedTransitionsFrom(WorkItemStatus::from($value)) as $target) {
                $this->assertContains(
                    $target,
                    self::todoStatuses(),
                    "{$value} → {$target} leaves the To-Do vocabulary of §3.1.",
                );
            }
        }
    }

    public function test_archived_can_only_leave_for_inbox_or_planned(): void
    {
        $this->assertSame(
            ['inbox', 'planned'],
            TodoService::allowedTransitionsFrom(WorkItemStatus::Archived),
        );
    }

    private function todoInStatus(User $actor, WorkItemStatus $status): Todo
    {
        return Todo::query()->create([
            'title' => 'Fixture',
            'creator_id' => $actor->id,
            'assignee_id' => $actor->id,
            'status' => $status,
            'visibility' => Visibility::Personal,
        ]);
    }
}
