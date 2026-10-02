<?php

namespace Modules\Projects\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;
use Tests\TestCase;

/**
 * Projects.
 *
 * The Projects module is the thinnest in the application: one model, one table
 * (`task_projects`), and a relation to Tasks. That is exactly the profile of a
 * module nobody tests and everybody assumes works.
 */
class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lives_in_the_task_projects_table(): void
    {
        $project = Project::factory()->create();

        $this->assertSame('task_projects', $project->getTable());
        $this->assertDatabaseHas('task_projects', ['id' => $project->id]);
    }

    public function test_it_holds_tasks(): void
    {
        $project = Project::factory()->create();

        Task::factory()->count(2)->create(['project_id' => $project->id]);
        Task::factory()->create();

        $this->assertCount(2, $project->tasks);
    }

    public function test_a_task_in_no_project_is_not_in_any_project(): void
    {
        $orphan = Task::factory()->create(['project_id' => null]);

        $this->assertNull($orphan->project);
    }

    public function test_it_belongs_to_a_user(): void
    {
        $project = Project::factory()->create();

        $this->assertNotNull($project->user);
    }

    public function test_the_table_carries_the_columns_the_model_fills(): void
    {
        $this->assertTrue(
            Schema::hasColumns('task_projects', ['name', 'description', 'user_id']),
        );
    }
}
