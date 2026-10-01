<?php

namespace Tests\Feature;

use App\Enums\Visibility;
use App\Models\Department;
use App\Services\MyWorkService;
use Database\Factories\MeetingFactory;
use Database\Factories\ObligationFactory;
use Database\Factories\TaskFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Obligations\Models\ObligationResponsibility;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoWatcher;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * "My Work" — GAP-034.
 *
 * The property under test is that this page is **exactly as restrictive** as the
 * module lists it aggregates. An aggregated view that is laxer than its sources is
 * a way to read records the sources deliberately hide, so the permission cases
 * assert absence of both the row and its count.
 */
class MyWorkTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    public function test_the_page_renders_with_nothing_to_show(): void
    {
        $this->actingAs($this->plainUser())
            ->get(route('my-work'))
            ->assertOk()
            ->assertSee('Nothing waiting on you');
    }

    public function test_it_lists_work_from_every_source(): void
    {
        $user = $this->userWithPermissions([
            'todos.view', 'task.view', 'meeting.view', 'obligation.view',
        ]);

        Todo::factory()->createdBy($user)->create(['title' => 'A To-Do']);
        TaskFactory::new()->ownedBy($user)->create(['title' => 'A Task']);
        ObligationFactory::new()->create(['owner_user_id' => $user->id, 'title' => 'An Obligation']);

        $meeting = MeetingFactory::new()->organisedBy($user)->create();
        MeetingActionItem::query()->create([
            'meeting_id' => $meeting->id,
            'action_no' => 1,
            'title' => 'An action item',
            'priority' => 'high',
            'status' => 'open',
            'assigned_to' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('my-work'));

        $response->assertOk();
        $response->assertSee('A To-Do');
        $response->assertSee('A Task');
        $response->assertSee('An Obligation');
        $response->assertSee('An action item');
    }

    public function test_it_hides_work_the_user_cannot_open(): void
    {
        $user = $this->userWithPermissions(['todos.view']);
        Todo::factory()->create(['title' => 'Somebody elses To-Do']);

        $response = $this->actingAs($user)->get(route('my-work'));

        $response->assertOk();
        $response->assertDontSee('Somebody elses To-Do');
    }

    public function test_a_source_without_its_permission_is_neither_listed_nor_counted(): void
    {
        $user = $this->userWithPermissions(['todos.view']);

        Todo::factory()->createdBy($user)->create(['title' => 'Visible To-Do']);
        TaskFactory::new()->ownedBy($user)->create(['title' => 'Hidden task']);
        ObligationFactory::new()->create(['owner_user_id' => $user->id, 'title' => 'Hidden obligation']);

        $counts = app(MyWorkService::class)->forUser($user)['counts'];

        $this->assertSame(1, $counts['todos']);
        $this->assertSame(0, $counts['tasks'], 'A count must not leak what the filter hides.');
        $this->assertSame(0, $counts['obligations'], 'A count must not leak what the filter hides.');

        $response = $this->actingAs($user)->get(route('my-work'));
        $response->assertSee('Visible To-Do');
        $response->assertDontSee('Hidden task');
        $response->assertDontSee('Hidden obligation');
    }

    public function test_an_obligation_via_an_active_responsibility_is_included(): void
    {
        $user = $this->userWithPermissions(['obligation.view']);
        $owner = $this->plainUser();
        $obligation = ObligationFactory::new()->create([
            'owner_user_id' => $owner->id,
            'title' => 'Shared obligation',
        ]);

        ObligationResponsibility::query()->create([
            'obligation_id' => $obligation->id,
            'user_id' => $user->id,
            'responsibility_type' => 'responsible',
            'active' => true,
        ]);

        $this->actingAs($user)
            ->get(route('my-work'))
            ->assertOk()
            ->assertSee('Shared obligation');
    }

    public function test_an_inactive_responsibility_does_not_grant_visibility(): void
    {
        $user = $this->userWithPermissions(['obligation.view']);
        $owner = $this->plainUser();
        $obligation = ObligationFactory::new()->create([
            'owner_user_id' => $owner->id,
            'title' => 'Lapsed obligation',
        ]);

        ObligationResponsibility::query()->create([
            'obligation_id' => $obligation->id,
            'user_id' => $user->id,
            'responsibility_type' => 'responsible',
            'active' => false,
        ]);

        $this->actingAs($user)
            ->get(route('my-work'))
            ->assertOk()
            ->assertDontSee('Lapsed obligation');
    }

    public function test_a_watched_todo_appears_for_someone_who_owns_nothing(): void
    {
        $user = $this->userWithPermissions(['todos.view']);
        $todo = Todo::factory()->create(['title' => 'Watched To-Do']);

        TodoWatcher::query()->create(['todo_id' => $todo->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('my-work'))
            ->assertOk()
            ->assertSee('Watched To-Do');
    }

    public function test_a_team_todo_appears_only_for_the_same_department(): void
    {
        $department = Department::factory()->create();
        $outsider = $this->userWithPermissions(['todos.view']);

        Todo::factory()->withVisibility(Visibility::Team)->create([
            'title' => 'Department To-Do',
            'department_id' => $department->id,
        ]);

        $this->actingAs($outsider)
            ->get(route('my-work'))
            ->assertOk()
            ->assertDontSee('Department To-Do');
    }

    public function test_the_page_requires_authentication(): void
    {
        $this->get(route('my-work'))->assertRedirect(route('login'));
    }

    public function test_permitted_sources_reflect_the_users_permissions(): void
    {
        $onlyTodos = $this->userWithPermissions(['todos.view']);

        $this->assertSame(['todo'], app(MyWorkService::class)->permittedSources($onlyTodos)['sources']);

        $nothing = $this->plainUser();

        $this->assertSame([], app(MyWorkService::class)->permittedSources($nothing)['sources']);
        $this->assertFalse(app(MyWorkService::class)->permittedSources($nothing)['obligations']);
    }
}
