<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Modules\Projects\Filament\Resources\Projects\Pages\CreateProject;
use Modules\Projects\Filament\Resources\Projects\Pages\EditProject;
use Modules\Projects\Filament\Resources\Projects\Pages\ListProjects;
use Modules\Projects\Filament\Resources\Projects\Pages\ViewProject;
use Modules\Projects\Filament\Resources\Projects\RelationManagers\TasksRelationManager;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The Filament Project resource (workspace panel).
 */
class FilamentProjectResourceTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('workspace');
    }

    public function test_without_project_view_you_see_only_your_own_projects(): void
    {
        $user = $this->userWithPermissions(['project.create']);
        $mine = Project::factory()->create(['user_id' => $user->id]);
        $theirs = Project::factory()->create();
        $this->actingAs($user);

        Livewire::test(ListProjects::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_project_view_and_super_admin_see_every_project(): void
    {
        $projects = Project::factory()->count(2)->create();

        $this->actingAs($this->userWithPermissions(['project.view']));
        Livewire::test(ListProjects::class)->assertCanSeeTableRecords($projects);

        $this->actingAs($this->superAdmin());
        Livewire::test(ListProjects::class)->assertCanSeeTableRecords($projects);
    }

    public function test_the_list_searches_name_and_description(): void
    {
        $user = $this->userWithPermissions(['project.view']);
        $match = Project::factory()->create(['name' => 'Apollo', 'description' => 'moon']);
        $other = Project::factory()->create(['name' => 'Gemini', 'description' => 'twins']);
        $this->actingAs($user);

        Livewire::test(ListProjects::class)
            ->searchTable('moon')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_a_project_is_created_for_the_signed_in_user(): void
    {
        $user = $this->userWithPermissions(['project.create']);
        $this->actingAs($user);

        Livewire::test(CreateProject::class)
            ->fillForm(['name' => 'Launch', 'description' => 'Q4'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame($user->id, Project::query()->where('name', 'Launch')->value('user_id'));
    }

    public function test_creating_needs_permission_and_a_name(): void
    {
        $this->actingAs($this->plainUser());
        $this->get('/projects/create')->assertForbidden();

        $this->actingAs($this->userWithPermissions(['project.create']));
        Livewire::test(CreateProject::class)
            ->fillForm(['name' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_the_owner_edits_but_a_viewer_cannot(): void
    {
        $owner = $this->userWithPermissions(['project.create']);
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner);
        Livewire::test(EditProject::class, ['record' => $project->getKey()])
            ->fillForm(['name' => 'Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('Renamed', $project->fresh()->name);

        $this->actingAs($this->userWithPermissions(['project.view']));
        $this->get("/projects/{$project->id}/edit")->assertForbidden();
    }

    public function test_only_project_delete_or_super_admin_can_delete(): void
    {
        $owner = $this->userWithPermissions(['project.create', 'project.update']);
        $project = Project::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($owner);
        Livewire::test(ListProjects::class)->assertTableActionHidden('delete', $project);

        $this->actingAs($this->userWithPermissions(['project.view', 'project.delete']));
        Livewire::test(ListProjects::class)->callTableAction('delete', $project);
        $this->assertModelMissing($project);
    }

    public function test_a_stranger_cannot_open_someone_elses_project(): void
    {
        $this->actingAs($this->userWithPermissions(['project.create']));
        $project = Project::factory()->create();

        $this->get("/projects/{$project->id}")->assertNotFound();
    }

    public function test_the_project_page_lists_its_tasks(): void
    {
        $user = $this->userWithPermissions(['project.view']);
        $project = Project::factory()->create();
        $inside = Task::factory()->create(['project_id' => $project->id, 'priority' => 'medium']);
        $outside = Task::factory()->create(['priority' => 'medium']);
        $this->actingAs($user);

        Livewire::test(TasksRelationManager::class, ['ownerRecord' => $project, 'pageClass' => ViewProject::class])
            ->assertCanSeeTableRecords([$inside])
            ->assertCanNotSeeTableRecords([$outside]);
    }
}
