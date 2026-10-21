<?php

namespace Tests\Feature;

use App\Enums\WorkItemStatus;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Todos\Filament\Resources\Todos\Pages\CreateTodo;
use Modules\Todos\Filament\Resources\Todos\Pages\EditTodo;
use Modules\Todos\Filament\Resources\Todos\Pages\ListTodos;
use Modules\Todos\Filament\Resources\Todos\Pages\ViewTodo;
use Modules\Todos\Models\Todo;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The Filament pilot for the To-Do module (workspace panel).
 */
class FilamentTodoResourceTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('workspace');
    }

    public function test_a_user_sees_their_own_todos_and_not_a_strangers(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $mine = Todo::factory()->create(['creator_id' => $user->id, 'assignee_id' => $user->id]);
        $theirs = Todo::factory()->create();

        $this->actingAs($user);

        Livewire::test(ListTodos::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_a_todo_can_be_created_through_the_service(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $this->actingAs($user);

        Livewire::test(CreateTodo::class)
            ->fillForm(['title' => 'Write report', 'priority' => 'high'])
            ->call('create')
            ->assertHasNoFormErrors();

        $todo = Todo::query()->where('title', 'Write report')->firstOrFail();
        $this->assertSame($user->id, $todo->creator_id);
        $this->assertSame(WorkItemStatus::Inbox, $todo->status);
    }

    public function test_creating_requires_the_create_permission(): void
    {
        $this->actingAs($this->plainUser());

        $this->get('/workspace/todos/create')->assertForbidden();
    }

    public function test_assigning_to_someone_else_needs_its_own_permission(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $other = $this->plainUser();
        $this->actingAs($user);

        // The field is not offered, so a forged value is dropped rather than saved.
        Livewire::test(CreateTodo::class)
            ->fillForm(['title' => 'Delegated'])
            ->set('data.assignee_id', $other->id)
            ->call('create');

        $this->assertNotSame($other->id, Todo::query()->where('title', 'Delegated')->value('assignee_id'));
    }

    public function test_a_todo_can_be_edited_and_completed(): void
    {
        $user = $this->userWithPermissions(['todos.create', 'todos.update_own', 'todos.complete']);
        $todo = Todo::factory()->create([
            'creator_id' => $user->id,
            'assignee_id' => $user->id,
            'status' => WorkItemStatus::InProgress,
        ]);
        $this->actingAs($user);

        Livewire::test(EditTodo::class, ['record' => $todo->getKey()])
            ->fillForm(['title' => 'Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed', $todo->fresh()->title);

        Livewire::test(ViewTodo::class, ['record' => $todo->getKey()])->assertActionVisible('complete')
            ->mountAction('complete')
            ->callMountedAction()
            ->assertNotified('To-Do completed');

        $this->assertSame(WorkItemStatus::Completed, $todo->fresh()->status);
    }

    public function test_someone_elses_todo_cannot_be_opened(): void
    {
        $this->actingAs($this->userWithPermissions(['todos.create']));
        $theirs = Todo::factory()->create();

        $this->get("/workspace/todos/{$theirs->getKey()}")->assertNotFound();
    }

    public function test_a_move_the_transition_graph_refuses_is_reported_not_swallowed(): void
    {
        $user = $this->userWithPermissions(['todos.create']);
        $todo = Todo::factory()->create([
            'creator_id' => $user->id,
            'assignee_id' => $user->id,
            'status' => WorkItemStatus::Inbox,
        ]);
        $this->actingAs($user);

        Livewire::test(ViewTodo::class, ['record' => $todo->getKey()])
            ->mountAction('complete')
            ->callMountedAction()
            ->assertNotified();

        $this->assertSame(WorkItemStatus::Inbox, $todo->fresh()->status);
    }
}
