<?php

namespace Tests\Feature;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Todos\Models\Todo;
use Tests\TestCase;

/**
 * The factory is the fixture every later phase builds on, so its guarantees are
 * pinned here: a default row is valid, a title-only row is valid, and each state
 * helper produces what its name claims.
 */
class TodoFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_default_todo_passes_validation(): void
    {
        $todo = Todo::factory()->create();

        $this->assertDatabaseHas('todos', ['id' => $todo->id, 'title' => $todo->title]);
    }

    public function test_a_default_todo_uses_the_schema_defaults_for_the_vocabulary_columns(): void
    {
        $todo = Todo::factory()->create();

        $this->assertSame(WorkItemStatus::Inbox, $todo->status);
        $this->assertSame(Visibility::Personal, $todo->visibility);
        $this->assertSame(Priority::Medium, $todo->priority);
    }

    public function test_the_quick_capture_shape_is_title_only(): void
    {
        // This is what the index page posts: a title and nothing else, so every
        // other column must fall back to the schema default rather than fail.
        $todo = Todo::factory()->titleOnly('Call the vendor')->create();

        $this->assertSame('Call the vendor', $todo->title);
        $this->assertNull($todo->description);
        $this->assertNull($todo->assignee_id);
        $this->assertNull($todo->department_id);
        $this->assertNull($todo->due_date);
        $this->assertNull($todo->completed_at);
    }

    public function test_a_title_only_todo_survives_the_validation_the_form_request_will_apply(): void
    {
        $todo = Todo::factory()->titleOnly('Quick capture')->create();

        // Mirrors the rules StoreTodoRequest will apply in Phase 4. A factory
        // fixture that the controller would reject is worse than no fixture.
        $validator = validator(
            ['title' => $todo->title],
            [
                'title' => ['required', 'string', 'max:255'],
                'status' => ['nullable'],
                'priority' => ['nullable'],
                'visibility' => ['nullable'],
                'assignee_id' => ['nullable', 'exists:users,id'],
            ],
        );

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->all()));
    }

    public function test_creator_id_is_always_populated(): void
    {
        $todo = Todo::factory()->create();

        $this->assertNotNull($todo->creator_id);
        $this->assertInstanceOf(User::class, $todo->creator);
    }

    public function test_a_todo_without_a_creator_is_rejected_by_the_database(): void
    {
        $this->expectException(QueryException::class);

        DB::table('todos')->insert(['title' => 'No author', 'creator_id' => null]);
    }

    public function test_deleting_the_creator_is_restricted_while_deleting_the_assignee_nulls_the_column(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();

        $todo = Todo::factory()->createdBy($creator)->assignedTo($assignee)->create();

        $assignee->delete();

        $this->assertNull($todo->fresh()->assignee_id, 'Removing the assignee must not delete the To-Do.');

        // creator_id is restrictOnDelete, so the delete is refused rather than
        // silently orphaning history.
        $this->expectException(QueryException::class);
        $creator->delete();
    }

    public function test_state_helpers_produce_what_they_claim(): void
    {
        $user = User::factory()->create();

        $this->assertSame($user->id, Todo::factory()->assignedTo($user)->create()->assignee_id);
        $this->assertSame(WorkItemStatus::Waiting, Todo::factory()->inStatus(WorkItemStatus::Waiting)->create()->status);
        $this->assertSame(Visibility::Team, Todo::factory()->withVisibility(Visibility::Team)->create()->visibility);

        $completed = Todo::factory()->completed()->create();
        $this->assertSame(WorkItemStatus::Completed, $completed->status);
        $this->assertNotNull($completed->completed_at);

        $overdue = Todo::factory()->overdue()->create();
        $this->assertTrue($overdue->due_date->isPast());
        $this->assertNull($overdue->completed_at);
    }

    public function test_a_team_todo_can_carry_a_department(): void
    {
        $department = Department::factory()->create();

        $todo = Todo::factory()->withVisibility(Visibility::Team)->create([
            'department_id' => $department->id,
        ]);

        $this->assertSame($department->id, $todo->department_id);
        $this->assertInstanceOf(Department::class, $todo->department);
    }

    public function test_the_status_column_reads_back_as_an_enum(): void
    {
        $todo = Todo::factory()->inStatus(WorkItemStatus::InProgress)->create();

        $this->assertInstanceOf(WorkItemStatus::class, $todo->fresh()->status);
        $this->assertSame('in_progress', DB::table('todos')->where('id', $todo->id)->value('status'));
    }

    public function test_the_recurrence_rule_column_reads_back_as_an_array(): void
    {
        $rule = [
            'frequency' => 'monthly',
            'interval' => 1,
            'by_month_day' => 15,
            'start_date' => '2026-10-01',
        ];

        $todo = Todo::factory()->create(['recurrence_rule' => $rule]);

        $this->assertSame($rule, $todo->fresh()->recurrence_rule);
    }

    public function test_a_todo_soft_deletes_and_can_be_restored(): void
    {
        $todo = Todo::factory()->create();

        $todo->delete();

        $this->assertNull(Todo::query()->find($todo->id));
        $this->assertNotNull(Todo::withTrashed()->find($todo->id));

        $todo->restore();

        $this->assertNotNull(Todo::query()->find($todo->id));
    }
}
