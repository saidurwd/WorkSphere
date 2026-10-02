<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Obligations\Models\Obligation;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskRemark;
use Modules\Tasks\Models\TaskTransfer;
use Modules\Tasks\Models\TaskWatcher;
use Modules\Tasks\Models\TimeEntry;

/**
 * Projects and the work on them, at demo volume.
 *
 * Sized against `config('seed.php').volumes`. The point of the volume is that
 * pagination, the workload report, the status and priority filters and the
 * dashboards have enough rows to be worth looking at: at twenty tasks every
 * screen is a single page and nothing filters anything.
 *
 * A task is not a row on its own. Each one is related to a project, a creator
 * and a responsible person, and a realistic share of them carry a parent, a
 * subtask, watchers, logged time, remarks, a comment thread, a transfer between
 * two people and the notification that went with it. Those relations are the
 * point — they are what the task detail screen renders, and an empty
 * `task_watchers` table makes that screen look broken rather than new.
 *
 * Every value is derived from {@see DeterministicSequence}, so the same seed
 * produces the same database.
 */
class TaskSeeder extends Seeder
{
    use WithoutModelEvents;

    private DeterministicSequence $random;

    /**
     * @var list<string>
     */
    private const PROJECTS = [
        ['Customer Portal Revamp', 'Rebuild the self-service portal on the new design system, including account management, invoicing and a support inbox.'],
        ['Mobile Application Launch', 'Ship the iOS and Android client for the Worksphere platform, with offline sync and biometric sign-in.'],
        ['Payment Gateway Migration', 'Move domestic card processing to the new gateway and retire the legacy acquirer integration.'],
        ['Core API Platform', 'Extract the monolith\'s endpoints into a versioned public API with per-consumer rate limits.'],
        ['Analytics Data Warehouse', 'Land operational data in a warehouse with a governed dimensional model and a self-service BI layer.'],
        ['ISO 27001 Certification', 'Close the gap assessment findings and evidence the ISMS controls for the annual surveillance audit.'],
        ['Office Estate Relocation', 'Plan and execute the move to the Gulshan office, including network, power and access control.'],
        ['Recruitment Platform Rollout', 'Replace the ad-hoc hiring spreadsheets with the applicant tracking system across every department.'],
        ['Fleet Telematics Pilot', 'Pilot vehicle tracking and route optimisation for the delivery fleet before a wider rollout.'],
        ['Warehouse Stock Accuracy', 'Cycle-count programme and barcode scanning to bring stock record accuracy above 98%.'],
        ['Vendor Consolidation', 'Consolidate overlapping supplier contracts and renegotiate the top twenty by spend.'],
        ['Identity and Access Review', 'Quarterly access recertification across every system, with automated joiner-mover-leaver workflow.'],
        ['Customer Portal Performance', 'Bring the p95 page response under 400 ms by removing the N+1 queries on the account screens.'],
        ['Knowledge Base Overhaul', 'Restructure the internal knowledge base around tasks users actually search for.'],
        ['Billing Automation', 'Automate invoice generation, dunning and revenue recognition for recurring service lines.'],
        ['Legacy Data Migration', 'Extract, clean and load eleven years of transactional history into the new schema.'],
        ['Security Penetration Test', 'Commission an independent penetration test and remediate every high and critical finding.'],
        ['Procure-to-Pay Automation', 'Route purchase requisitions through approval policy and generate purchase orders automatically.'],
        ['Field Service Scheduling', 'Schedule technicians by geography and skill, with customer notifications on dispatch.'],
        ['Product Analytics Rollout', 'Instrument the product surface and give each team a funnel dashboard for their own area.'],
        ['Cloud Cost Optimisation', 'Right-size instances, reserve capacity and add budgets so overspend is visible before the invoice.'],
        ['Localisation for Bangladesh', 'Localise date, currency and language handling, and add Bengali language support.'],
        ['Business Continuity Plan', 'Document recovery procedures per service and run a tabletop exercise with each owning team.'],
        ['Contract Lifecycle Automation', 'Track obligations, renewal dates, notice periods and spend across every vendor contract.'],
        ['Accessibility Remediation', 'Bring the platform to WCAG 2.1 AA, starting with the navigation, forms and data tables.'],
    ];

    /**
     * Task titles, written to read like real work rather than like filler.
     *
     * @var list<string>
     */
    private const TITLES = [
        'Draft the technical design document',
        'Review and merge the pending changes',
        'Fix the failing end-to-end suite',
        'Write unit coverage for the service layer',
        'Update the API reference for this release',
        'Migrate the remaining records off the legacy table',
        'Instrument the endpoint and set an alert threshold',
        'Prepare the release notes for the rollout',
        'Run the quarterly access recertification',
        'Onboard the new joiners and revoke leaver access',
        'Reconcile the monthly ledger against the bank statement',
        'Prepare the budget variance commentary',
        'Collect the outstanding vendor invoices',
        'Tender evaluation and scoring for the renewal',
        'Review the supplier contract for renewal notice periods',
        'Renew the certificate and verify the chain',
        'Schedule the annual safety inspection',
        'Close out the findings from the last audit',
        'Verify the backup restoration procedure',
        'Update the runbook with the new escalation path',
        'Roll the change out to the staging environment',
        'Smoke test the release candidate',
        'Roll back the failed deployment and diagnose',
        'Add the missing foreign key constraints',
        'Rebuild the reporting view after the schema change',
        'Profile the slow query and add the missing index',
        'Remove the N+1 from the account screen',
        'Split the oversized controller into services',
        'Cover the notification dispatch with tests',
        'Document the escalation matrix for support',
        'Answer the vendor security questionnaire',
        'Complete the data processing agreement review',
        'Publish the revised retention schedule',
        'Train the department on the updated procedure',
        'Set up the workspace for the new team',
        'Complete the probation review paperwork',
        'Schedule the performance review conversations',
        'Publish the internal newsletter',
        'Update the onboarding checklist',
        'Prepare the monthly operations report',
        'Reconcile the stock counts for the warehouse',
        'Plan the delivery route for the week',
        'Fault-find the intermittent network drop',
        'Replace the failing backup battery',
        'Complete the health and safety walkthrough',
        'Chase the pending approval on the purchase order',
        'Finalise the scope of work with the client',
        'Present the progress update to the steering group',
        'Capture the lessons learned from the incident',
        'Update the risk register for this phase',
        'Validate the data against the source system',
        'Agree the acceptance criteria with the business',
        'Set up the staging data refresh',
        'Resolve the outstanding support escalations',
        'Clean up the dormant feature accounts',
        'Export the audit evidence pack',
    ];

    /**
     * @var list<string>
     */
    private const REMARKS = [
        'Blocked on the change request — waiting for approval from the steering group.',
        'Vendor confirmed the parts are in stock; shipping expected this week.',
        'Root cause was a stale cache entry. Fix deployed and verified in production.',
        'Agreed with the client to move the delivery date by one week.',
        'Needs a second reviewer before it can go to the business for approval.',
        'Split into smaller tasks so the work can be picked up in parallel.',
        'Tested against the staging copy; two minor defects logged for the next patch.',
        'Waiting on the signed acceptance paperwork.',
        'Cost came in under the quoted estimate. Purchase order updated.',
        'Escalated to the department head — third occurrence of the same fault.',
        'Completed ahead of schedule; the follow-up was moved into the next sprint.',
        'Reassigned to the platform team, who have the context on the service.',
    ];

    /**
     * @var list<string>
     */
    private const COMMENTS = [
        'Can we confirm the rollback plan before this goes out?',
        'The staging environment does not have the seed data — I have added it now.',
        'I have pushed a fix for the failing assertion. It was asserting on the timezone.',
        'Agreed. Leaving this open until the client signs off.',
        'This needs to go out before the month-end close.',
        'Reviewed — one small comment about the error handling, otherwise fine.',
        'I have reproduced this locally. It only happens on the second request.',
        'Moving this to the next sprint so it does not block the release.',
        'The vendor has confirmed this in writing, attaching the response to the ticket.',
        'Good catch. That would have broken the report for the whole region.',
        'Deployed to production at 14:20, no errors since.',
        'Closing this one — superseded by the new approach agreed yesterday.',
    ];

    public function run(): void
    {
        $this->random = new DeterministicSequence;

        $users = User::query()->with('employee')->where('status', 'active')->get();

        if ($users->count() < 2) {
            $this->command?->warn('  Tasks: skipped, fewer than two active accounts exist.');

            return;
        }

        $projects = $this->seedProjects($users);

        if ($projects->isEmpty()) {
            return;
        }

        $obligations = Obligation::query()->pluck('id')->all();

        $tasks = $this->seedTasks($users, $projects, $obligations);

        $this->seedTaskRelations($users, $tasks);

        $this->command?->info(sprintf(
            '  Tasks: %d across %d projects (time entries %d, watchers %d, comments %d)',
            Task::query()->count(),
            $projects->count(),
            TimeEntry::query()->count(),
            TaskWatcher::query()->count(),
            Comment::query()->where('commentable_type', (new Task)->getMorphClass())->count(),
        ));
    }

    /**
     * @param  Collection<int, User>  $users
     * @return Collection<int, Project>
     */
    private function seedProjects(Collection $users): Collection
    {
        $wanted = (int) config('seed.volumes.projects', 25);

        if (Project::query()->count() >= $wanted) {
            return Project::query()->get();
        }

        $now = Carbon::now();
        $projects = collect();

        foreach (array_slice(self::PROJECTS, 0, $wanted) as $index => [$name, $description]) {
            $owner = $users[$this->random->between(0, $users->count() - 1)];
            $startedAt = $now->copy()->subDays($this->random->between(30, 400));

            $projects->push(Project::query()->updateOrCreate(
                ['name' => $name],
                [
                    'user_id' => $owner->id,
                    'description' => $description,
                    'created_at' => $startedAt,
                    'updated_at' => $now->copy()->subDays($this->random->between(0, 29)),
                ],
            ));
        }

        return $projects;
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Project>  $projects
     * @param  list<int>  $obligationIds
     * @return Collection<int, Task>
     */
    private function seedTasks(
        Collection $users,
        Collection $projects,
        array $obligationIds,
    ): Collection {
        $wanted = (int) config('seed.volumes.tasks', 600);

        if (Task::query()->count() >= $wanted) {
            return Task::query()->orderBy('id')->get();
        }

        $now = Carbon::now();
        $parents = [];
        $children = [];

        $statuses = ['pending', 'in_progress', 'on_hold', 'postponed', 'completed', 'cancelled'];
        $statusWeights = [22, 24, 7, 6, 33, 8];
        $priorities = ['low', 'medium', 'high'];
        $priorityWeights = [28, 47, 25];

        for ($i = 0; $i < $wanted; $i++) {
            $project = $projects[$this->random->between(0, $projects->count() - 1)];
            $creator = $users[$this->random->between(0, $users->count() - 1)];
            $responsible = $users[$this->random->between(0, $users->count() - 1)];

            $createdAt = $now->copy()->subDays($this->random->between(0, 240));
            $status = $this->random->pick($statuses, $statusWeights);
            $priority = $this->random->pick($priorities, $priorityWeights);

            $dueDate = $this->dueDate($createdAt, $status, $now);
            $completedAt = $status === 'completed'
                ? $createdAt->copy()->addDays($this->random->between(1, 21))->setTime(17, 30)
                : null;

            $estimated = $this->random->pick([30, 45, 60, 90, 120, 180, 240, 480]);
            $actual = $completedAt !== null
                ? (int) round($estimated * ($this->random->between(70, 140) / 100))
                : ($status === 'in_progress' ? $this->random->between(0, $estimated) : 0);

            $row = [
                'user_id' => $creator->id,
                'responsible_user_id' => $responsible->id,
                'project_id' => $project->id,
                'title' => self::TITLES[$this->random->between(0, count(self::TITLES) - 1)],
                'description' => $this->description($project->name),
                'priority' => $priority,
                'status' => $status,
                'due_date' => $dueDate,
                'completed_at' => $completedAt,
                'estimated_minutes' => $estimated,
                'actual_minutes' => $actual,
                'task_no' => 'TASK-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'parent_id' => null,
                'obligation_id' => $obligationIds !== [] && $this->random->chance(6)
                    ? $obligationIds[$this->random->between(0, count($obligationIds) - 1)]
                    : null,
                'created_at' => $createdAt,
                'updated_at' => $completedAt ?? $createdAt->copy()->addDays($this->random->between(0, 14)),
            ];

            // Top-level tasks go in first so their ids exist before any subtask
            // can name one as its parent. A single pass would have to guess at
            // ids it does not have yet.
            $parents[] = $row;

            if ($this->random->chance(14)) {
                $children[] = [
                    'row' => $row,
                    'parent_no' => 'TASK-'.str_pad((string) ($this->random->between(1, $i + 1)), 5, '0', STR_PAD_LEFT),
                    // Numbered from this row's own index, so two subtasks of the
                    // same parent still get distinct reference numbers.
                    'task_no' => 'TASK-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT).'-S1',
                ];
            }

            if (count($parents) >= 250) {
                $this->insertTasks($parents);
                $parents = [];
            }
        }

        if ($parents !== []) {
            $this->insertTasks($parents);
        }

        $parentIds = Task::query()
            ->whereIn('task_no', array_column($this->insertedTaskNumbers, 'task_no'))
            ->pluck('id', 'task_no')
            ->all();

        $this->seedSubtasks($children, $parentIds);

        return Task::query()->orderBy('id')->get();
    }

    /**
     * The `task_no` of every row written by the current run, so a re-run cannot
     * mistake ids that were already in the table for its own.
     *
     * @var list<array{task_no: string}>
     */
    private array $insertedTaskNumbers = [];

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertTasks(array $rows): void
    {
        DB::table('tasks')->insert($rows);

        foreach ($rows as $row) {
            $this->insertedTaskNumbers[] = ['task_no' => $row['task_no']];
        }
    }

    /**
     * Insert the subtasks, resolving each one's parent from the top-level pass.
     *
     * A parent whose number is not in the map is dropped rather than written as
     * a null or dangling reference: the alternative is a `parent_id` pointing at
     * nothing, which the detail screen renders as a broken link.
     *
     * @param  list<array{row: array<string, mixed>, parent_no: string}>  $children
     * @param  array<string, int>  $parentIds  `task_no` => id.
     */
    private function seedSubtasks(array $children, array $parentIds): void
    {
        $rows = [];

        foreach ($children as $child) {
            $parentId = $parentIds[$child['parent_no']] ?? null;

            if ($parentId === null) {
                continue;
            }

            $row = $child['row'];
            $row['task_no'] = $child['task_no'];
            $row['parent_id'] = $parentId;
            $row['title'] = $row['title'].' (sub-task)';
            $rows[] = $row;

            if (count($rows) >= 250) {
                $this->insertTasks($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            $this->insertTasks($rows);
        }
    }

    /**
     * Due dates spread over the year around today, so the overdue, due-this-week
     * and future buckets are all populated.
     */
    private function dueDate(Carbon $createdAt, string $status, Carbon $now): string
    {
        if ($status === 'completed') {
            return $createdAt->copy()->addDays($this->random->between(3, 45))->toDateString();
        }

        $bucket = $this->random->weighted([18, 8, 20, 24, 30]);

        return match ($bucket) {
            0 => $now->copy()->subDays($this->random->between(1, 45))->toDateString(),
            1 => $now->toDateString(),
            2 => $now->copy()->addDays($this->random->between(1, 7))->toDateString(),
            3 => $now->copy()->addDays($this->random->between(8, 30))->toDateString(),
            default => $now->copy()->addDays($this->random->between(31, 120))->toDateString(),
        };
    }

    private function description(string $projectName): string
    {
        $openers = [
            'Raised as part of the delivery plan',
            'Follow-up from the last review',
            'Carried over from the previous cycle',
            'Requested by the business during the planning session',
            'Required before the audit evidence pack is submitted',
        ];

        return sprintf(
            '%s for %s. %s Acceptance is confirmed against the agreed criteria and the work is recorded against the project.',
            $this->random->pick($openers),
            $projectName,
            $this->random->pick([
                'Keep the change backwards compatible with the current release.',
                'No customer-facing copy changes without sign-off.',
                'Roll out behind the existing feature flag.',
                'Update the documentation in the same change.',
                'Attach the test evidence to the closure note.',
            ]),
        );
    }

    /**
     * Everything hanging off a task: watchers, logged time, remarks, transfers
     * and the notification that a transfer sends.
     *
     * @param  Collection<int, User>  $users
     * @param  Collection<int, Task>  $tasks
     */
    private function seedTaskRelations(Collection $users, Collection $tasks): void
    {
        $taskIds = $tasks->pluck('id')->all();
        $userIds = $users->pluck('id')->all();
        $morphClass = (new Task)->getMorphClass();

        $this->seedTimeEntries($taskIds, $userIds);
        $this->seedWatchers($taskIds, $userIds);
        $this->seedRemarks($taskIds, $userIds);
        $this->seedComments($taskIds, $userIds, $morphClass);
        $this->seedTransfers($taskIds, $userIds);
    }

    /**
     * @param  list<int>  $taskIds
     * @param  list<int>  $userIds
     */
    private function seedTimeEntries(array $taskIds, array $userIds): void
    {
        $wanted = (int) config('seed.volumes.time_entries', 900);

        if (TimeEntry::query()->count() >= $wanted || $taskIds === []) {
            return;
        }

        $now = Carbon::now();
        $rows = [];

        while (count($rows) < $wanted) {
            $rows[] = [
                'task_id' => $taskIds[$this->random->between(0, count($taskIds) - 1)],
                'user_id' => $userIds[$this->random->between(0, count($userIds) - 1)],
                'minutes' => $this->random->pick([30, 45, 60, 90, 120, 180, 240, 300]),
                'logged_on' => $now->copy()->subDays($this->random->between(0, 120))->toDateString(),
                'note' => $this->random->chance(60)
                    ? $this->random->pick([
                        'Pairing session on the failing test.',
                        'Schema change and backfill.',
                        'Investigation and root-cause work.',
                        'Code review and merge.',
                        'Client call and follow-up notes.',
                        'Documentation.',
                    ])
                    : null,
                'created_at' => $now->copy()->subDays($this->random->between(0, 120)),
                'updated_at' => $now->copy()->subDays($this->random->between(0, 120)),
            ];
        }

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('time_entries')->insert($chunk);
        }
    }

    /**
     * @param  list<int>  $taskIds
     * @param  list<int>  $userIds
     */
    private function seedWatchers(array $taskIds, array $userIds): void
    {
        $wanted = (int) config('seed.volumes.task_watchers', 500);

        if (TaskWatcher::query()->count() >= $wanted || $taskIds === []) {
            return;
        }

        $now = Carbon::now();
        $rows = [];
        $seen = [];

        while (count($rows) < $wanted) {
            $taskId = $taskIds[$this->random->between(0, count($taskIds) - 1)];
            $userId = $userIds[$this->random->between(0, count($userIds) - 1)];
            $key = $taskId.':'.$userId;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $rows[] = [
                'task_id' => $taskId,
                'user_id' => $userId,
                'created_at' => $now->copy()->subDays($this->random->between(0, 200)),
                'updated_at' => $now->copy()->subDays($this->random->between(0, 200)),
            ];
        }

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('task_watchers')->insert($chunk);
        }
    }

    /**
     * @param  list<int>  $taskIds
     * @param  list<int>  $userIds
     */
    private function seedRemarks(array $taskIds, array $userIds): void
    {
        $wanted = (int) config('seed.volumes.task_remarks', 260);

        if (TaskRemark::query()->count() >= $wanted || $taskIds === []) {
            return;
        }

        $now = Carbon::now();
        $rows = [];

        while (count($rows) < $wanted) {
            $rows[] = [
                'task_id' => $taskIds[$this->random->between(0, count($taskIds) - 1)],
                'user_id' => $userIds[$this->random->between(0, count($userIds) - 1)],
                'remark' => $this->random->pick(self::REMARKS),
                'created_at' => $now->copy()->subDays($this->random->between(0, 180)),
                'updated_at' => $now->copy()->subDays($this->random->between(0, 180)),
            ];
        }

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('task_remarks')->insert($chunk);
        }
    }

    /**
     * @param  list<int>  $taskIds
     * @param  list<int>  $userIds
     */
    private function seedComments(array $taskIds, array $userIds, string $morphClass): void
    {
        $wanted = (int) config('seed.volumes.task_comments', 400);

        if (Comment::query()->where('commentable_type', $morphClass)->count() >= $wanted || $taskIds === []) {
            return;
        }

        $now = Carbon::now();
        $rows = [];

        while (count($rows) < $wanted) {
            $rows[] = [
                'commentable_type' => $morphClass,
                'commentable_id' => $taskIds[$this->random->between(0, count($taskIds) - 1)],
                'user_id' => $userIds[$this->random->between(0, count($userIds) - 1)],
                'body' => $this->random->pick(self::COMMENTS),
                'created_at' => $now->copy()->subDays($this->random->between(0, 150)),
                'updated_at' => $now->copy()->subDays($this->random->between(0, 150)),
            ];
        }

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('comments')->insert($chunk);
        }
    }

    /**
     * A handover between two people, which is also what generates the transfer
     * notification. One row, both sides of the story.
     *
     * @param  list<int>  $taskIds
     * @param  list<int>  $userIds
     */
    private function seedTransfers(array $taskIds, array $userIds): void
    {
        $wanted = (int) config('seed.volumes.task_transfers', 120);

        if (TaskTransfer::query()->count() >= $wanted || $taskIds === []) {
            return;
        }

        $now = Carbon::now();
        $transfers = [];
        $notifications = [];
        $reasons = [
            'Reassigned during the workload rebalancing exercise.',
            'Handing over before going on leave.',
            'Escalated — this needs a senior engineer.',
            'Moved to the team that owns this service.',
            'Reassigned after the skills review.',
        ];

        while (count($transfers) < $wanted) {
            $from = $userIds[$this->random->between(0, count($userIds) - 1)];
            $to = $userIds[$this->random->between(0, count($userIds) - 1)];

            if ($from === $to) {
                continue;
            }

            $at = $now->copy()->subDays($this->random->between(0, 200));

            $transfers[] = [
                'task_id' => $taskIds[$this->random->between(0, count($taskIds) - 1)],
                'from_user_id' => $from,
                'to_user_id' => $to,
                'transferred_by' => $userIds[$this->random->between(0, count($userIds) - 1)],
                'reason' => $this->random->pick($reasons),
                'transfer_date' => $at->toDateString(),
                'created_at' => $at,
                'updated_at' => $at,
            ];
        }

        DB::table('task_transfers')->insert($transfers);

        // The notification mirrors the row that was just written, so it can be
        // built from the array rather than from a re-read of the table.
        foreach ($transfers as $transfer) {
            $notifications[] = [
                'task_id' => $transfer['task_id'],
                'user_id' => $transfer['to_user_id'],
                'channel' => 'IN_APP',
                'notification_type' => 'TASK_TRANSFERRED',
                'scheduled_at' => $transfer['created_at'],
                'sent_at' => $transfer['created_at'],
                'status' => 'sent',
                'subject' => 'A task has been transferred to you',
                'message' => $transfer['reason'],
                'retry_count' => 0,
                'created_at' => $transfer['created_at'],
                'updated_at' => $transfer['created_at'],
            ];
        }

        $logVolume = (int) config('seed.volumes.task_notification_logs', 300);

        while (count($notifications) < $logVolume) {
            $sentAt = $now->copy()->subDays($this->random->between(0, 200));

            $notifications[] = [
                'task_id' => $taskIds[$this->random->between(0, count($taskIds) - 1)],
                'user_id' => $userIds[$this->random->between(0, count($userIds) - 1)],
                'channel' => $this->random->pick(['IN_APP', 'EMAIL', 'SMS'], [60, 35, 5]),
                'notification_type' => $this->random->pick(
                    ['TASK_ASSIGNED', 'TASK_DUE_SOON', 'TASK_OVERDUE', 'TASK_COMPLETED'],
                    [30, 30, 25, 15],
                ),
                'scheduled_at' => $sentAt,
                'sent_at' => $sentAt,
                'status' => 'sent',
                'subject' => 'Task notification',
                'message' => 'This notification was generated by a scheduled reminder.',
                'retry_count' => 0,
                'created_at' => $sentAt,
                'updated_at' => $sentAt,
            ];
        }

        foreach (array_chunk($notifications, 250) as $chunk) {
            DB::table('task_notification_logs')->insert($chunk);
        }
    }
}
