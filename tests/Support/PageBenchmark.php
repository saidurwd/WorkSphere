<?php

namespace Tests\Support;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Meetings\Models\MeetingAgenda;
use Modules\Meetings\Models\MeetingParticipant;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationResponsibility;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoChecklistItem;
use Modules\Todos\Models\TodoWatcher;

/**
 * The page catalogue the performance baseline measures.
 *
 * One definition, used by two consumers: the test that enforces budgets and the
 * command that prints the table for humans. A catalogue defined twice drifts, and
 * the drift shows up as "the numbers improved" when in fact the page list changed.
 *
 * VOLUME is stated rather than implied. A performance figure is meaningless without
 * the dataset it was taken on — "the tasks page is 8 queries" says nothing about
 * whether that was one task or ten thousand — so every measurement records the
 * volume it was taken at.
 */
class PageBenchmark
{
    /**
     * The dataset the baseline is measured against.
     *
     * Chosen to be uncomfortable rather than flattering: a few hundred of each
     * entity, enough that an N+1 is unmistakable in the query count and enough
     * that an unindexed filter is visible in the plan. Not "realistic" in the
     * sense of a ten-year-old production system — that is a data-volume decision,
     * not a code one, and it is called out in the report.
     */
    public const VOLUME = [
        'users' => 60,
        'departments' => 6,
        'todos' => 400,
        'tasks' => 300,
        'meetings' => 120,
        'obligations' => 150,
        'action items' => 240,
    ];

    /**
     * @return list<array{label: string, uri: string, kind: string, budget: int, needs: list<string>}>
     */
    public static function pages(): array
    {
        return [
            // Dashboards — the pages that aggregate the most and are hit hardest.
            ['label' => 'dashboard', 'uri' => '/dashboard', 'kind' => 'web', 'budget' => 90, 'needs' => []],
            ['label' => 'tasks dashboard', 'uri' => '/tasks/dashboard', 'kind' => 'web', 'budget' => 30, 'needs' => []],
            ['label' => 'meetings dashboard', 'uri' => '/meetings/dashboard', 'kind' => 'web', 'budget' => 30, 'needs' => []],
            ['label' => 'obligations dashboard', 'uri' => '/obligations/dashboard', 'kind' => 'web', 'budget' => 30, 'needs' => []],

            // Index pages.
            ['label' => 'todos index', 'uri' => '/todos', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'todos inbox', 'uri' => '/todos/inbox', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'todos calendar', 'uri' => '/todos/calendar', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'tasks index', 'uri' => '/tasks', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'meetings index', 'uri' => '/meetings', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'meetings action items', 'uri' => '/meetings/action-items', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'obligations index', 'uri' => '/obligations', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'obligations my tasks', 'uri' => '/obligations/my-tasks', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'projects index', 'uri' => '/projects', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'my work', 'uri' => '/my-work', 'kind' => 'web', 'budget' => 30, 'needs' => []],
            ['label' => 'task transfers', 'uri' => '/task-transfers', 'kind' => 'web', 'budget' => 20, 'needs' => []],

            // Reports — the aggregation-heavy reads.
            ['label' => 'tasks report', 'uri' => '/reports/tasks', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'task workload report', 'uri' => '/reports/task-workload', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'meetings overdue report', 'uri' => '/meetings/reports/overdue', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'todos report', 'uri' => '/todos/reports', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'meetings report', 'uri' => '/meetings/reports', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'obligations report', 'uri' => '/obligations/reports', 'kind' => 'web', 'budget' => 20, 'needs' => []],

            // Detail pages — the N+1 risk, since one parent renders a collection.
            ['label' => 'todo show', 'uri' => '/todos/{todo}', 'kind' => 'web', 'budget' => 25, 'needs' => ['todo']],
            ['label' => 'task show', 'uri' => '/tasks/{task}', 'kind' => 'web', 'budget' => 25, 'needs' => ['task']],
            ['label' => 'todos notification log', 'uri' => '/todos/notification-logs', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'tasks notification log', 'uri' => '/tasks/notification-logs', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'meeting show', 'uri' => '/meetings/{meeting}', 'kind' => 'web', 'budget' => 30, 'needs' => ['meeting']],
            ['label' => 'meeting participants', 'uri' => '/meetings/{meeting}/participants', 'kind' => 'web', 'budget' => 20, 'needs' => ['meeting']],
            ['label' => 'meeting agendas', 'uri' => '/meetings/{meeting}/agendas', 'kind' => 'web', 'budget' => 20, 'needs' => ['meeting']],
            ['label' => 'meeting decisions', 'uri' => '/meetings/{meeting}/decisions', 'kind' => 'web', 'budget' => 20, 'needs' => ['meeting']],
            ['label' => 'meeting attachments', 'uri' => '/meetings/{meeting}/attachments', 'kind' => 'web', 'budget' => 20, 'needs' => ['meeting']],
            ['label' => 'meeting print', 'uri' => '/meetings/{meeting}/print', 'kind' => 'web', 'budget' => 20, 'needs' => ['meeting']],
            ['label' => 'obligation show', 'uri' => '/obligations/{obligation}', 'kind' => 'web', 'budget' => 30, 'needs' => ['obligation']],
            ['label' => 'obligation renewals', 'uri' => '/obligations/renewals', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'obligation vendors', 'uri' => '/obligations/vendors', 'kind' => 'web', 'budget' => 20, 'needs' => []],
            ['label' => 'project show', 'uri' => '/projects/{project}', 'kind' => 'web', 'budget' => 20, 'needs' => ['project']],

            // API.
            ['label' => 'api todos', 'uri' => '/api/v1/todos', 'kind' => 'api', 'budget' => 12, 'needs' => []],
            ['label' => 'api tasks', 'uri' => '/api/v1/tasks', 'kind' => 'api', 'budget' => 12, 'needs' => []],
            ['label' => 'api meetings', 'uri' => '/api/v1/meetings', 'kind' => 'api', 'budget' => 12, 'needs' => []],
            ['label' => 'api obligations', 'uri' => '/api/v1/obligations', 'kind' => 'api', 'budget' => 12, 'needs' => []],
            ['label' => 'api todo show', 'uri' => '/api/v1/todos/{todo}', 'kind' => 'api', 'budget' => 12, 'needs' => ['todo']],
            ['label' => 'api todo comments', 'uri' => '/api/v1/todos/{todo}/comments', 'kind' => 'api', 'budget' => 12, 'needs' => ['todo']],
        ];
    }

    /**
     * Build the dataset.
     *
     * The permissions are the WIDEST in the application, deliberately: a caller
     * without `todos.view_all` exercises the filtering path, which is a different
     * query and a different cost. The unfiltered path is the expensive one and
     * therefore the one worth bounding.
     */
    public static function seed(): SeededData
    {
        $departments = Department::factory()->count(self::VOLUME['departments'])->create();

        $users = collect();

        foreach (range(1, self::VOLUME['users']) as $index) {
            $user = User::factory()->create();

            $employee = Employee::factory()->create([
                'department_id' => $departments[$index % $departments->count()]->id,
            ]);

            $user->update(['employee_id' => $employee->id]);

            $users->push($user->fresh());
        }

        $viewer = $users->first();

        $projects = Project::factory()->count(12)->create();

        $todos = Todo::factory()->count(self::VOLUME['todos'])->create();

        TodoChecklistItem::factory()->count(6)->create(['todo_id' => $todos->random()->id]);
        TodoWatcher::factory()->count(6)->create(['todo_id' => $todos->random()->id]);

        $tasks = Task::factory()->count(self::VOLUME['tasks'])->create();

        $meetings = Meeting::factory()->count(self::VOLUME['meetings'])->create();

        MeetingParticipant::factory()->count(self::VOLUME['action items'])->create();
        MeetingAgenda::factory()->count(self::VOLUME['action items'])->create();
        MeetingActionItem::factory()->count(self::VOLUME['action items'])->create();

        $obligations = Obligation::factory()->count(self::VOLUME['obligations'])->create();

        ObligationResponsibility::factory()->count(self::VOLUME['action items'])->create();

        return new SeededData($viewer, [
            'todo' => $todos->random(),
            'task' => $tasks->random(),
            'meeting' => $meetings->random(),
            'obligation' => $obligations->random(),
            'project' => $projects->random(),
        ]);
    }
}
