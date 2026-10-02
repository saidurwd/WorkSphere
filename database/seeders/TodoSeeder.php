<?php

namespace Database\Seeders;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Models\Comment;
use App\Models\Department;
use App\Models\Reminder;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Collection as ModelCollection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Todos\Models\Todo;

/**
 * The To-Do module, at demo volume.
 *
 * Deliberately contains no PII and no credentials: titles are fixed phrases
 * about work, and every user referenced is one the organisation seeder created
 * with the shared development password. Nothing here is required by the
 * application — it exists so a developer or a demonstrator has something real to
 * look at after seeding.
 *
 * The distribution is the point. The inbox, the overdue queue, work in progress,
 * completed items, items waiting on someone else, team-shared items and recurring
 * items are all represented, because each one drives a different widget and a
 * different filter and a dataset of only finished work exercises none of them.
 */
class TodoSeeder extends Seeder
{
    use WithoutModelEvents;

    private DeterministicSequence $random;

    /**
     * @var list<string>
     */
    private const TITLES = [
        'Reply to the supplier about the renewal quote',
        'Collect the signed timesheets from the field team',
        'Book the venue for the quarterly review',
        'Chase the outstanding approval on the purchase order',
        'Update the contact list for the regional office',
        'Draft the summary note for the last meeting',
        'File the audit evidence pack',
        'Review the draft procedure before circulation',
        'Confirm the travel dates with the client',
        'Order replacement equipment for the meeting room',
        'Reconcile the expense claims for last month',
        'Arrange access badges for the new joiners',
        'Check the renewal notice period on this contract',
        'Prepare the agenda for the next review',
        'Follow up on the two open support escalations',
        'Update the risk register for this phase',
        'Send the weekly progress note to the department',
        'Verify the figures against the ledger',
        'Arrange the annual inspection visit',
        'Complete the access recertification for the service',
        'Circulate the draft policy for comment',
        'Confirm the invoice with the vendor',
        'Archive the closed case documents',
        'Schedule the training session for the team',
        'Publish the revised runbook',
        'Check the stock levels for the coming quarter',
        'Close out the outstanding actions from the audit',
        'Set up the recurring reminder for the monthly report',
        'Ask the department head to confirm the priority',
        'Update the shared contact list',
    ];

    /**
     * @var list<string>
     */
    private const CHECKLIST_ITEMS = [
        'Draft the first version',
        'Have it reviewed',
        'Apply the corrections',
        'Confirm with the requester',
        'File the final copy',
    ];

    /**
     * @var array<string, string>
     */
    private const TAGS = [
        'Follow up' => '#f59e0b',
        'Approval' => '#8b5cf6',
        'Procurement' => '#0ea5e9',
        'Compliance' => '#ef4444',
        'Facilities' => '#22c55e',
        'Payroll' => '#ec4899',
        'Onboarding' => '#6366f1',
        'Audit' => '#b91c1c',
        'Training' => '#14b8a6',
        'Client' => '#f97316',
        'Urgent' => '#dc2626',
        'This week' => '#3b82f6',
        'Waiting' => '#a855f7',
        'Report' => '#059669',
        'Insurance' => '#7c3aed',
        'Contract' => '#0891b2',
        'Travel' => '#ca8a04',
        'Office' => '#65a30d',
    ];

    public function run(): void
    {
        $this->random = new DeterministicSequence;

        $users = User::query()->with('employee')->where('status', 'active')->get();
        $departments = Department::query()->get();

        if ($users->isEmpty() || $departments->isEmpty()) {
            $this->command?->warn('  To-dos: skipped, the organisation seeders have not run.');

            return;
        }

        $todos = $this->seedTodos($users, $departments);
        $tags = $this->seedTags($users);

        $this->seedChecklistItems($todos, $users);
        $this->seedWatchers($todos, $users);
        $this->seedLinks($todos);
        $this->seedComments($todos, $users);
        $this->seedReminders($todos, $users);
        $this->seedTaggables($todos, $tags, $users);

        $this->command?->info(sprintf(
            '  To-dos: %d (checklist %d, watchers %d, links %d, reminders %d, tags %d)',
            Todo::query()->count(),
            DB::table('todo_checklist_items')->count(),
            DB::table('todo_watchers')->count(),
            DB::table('todo_links')->count(),
            DB::table('reminders')->count(),
            Tag::query()->count(),
        ));
    }

    /**
     * @param  ModelCollection<int, User>  $users
     * @param  ModelCollection<int, Department>  $departments
     * @return ModelCollection<int, Todo>
     */
    private function seedTodos(ModelCollection $users, ModelCollection $departments): Collection
    {
        $wanted = (int) config('seed.volumes.todos', 350);

        if (Todo::query()->count() >= $wanted) {
            return Todo::query()->orderBy('id')->get();
        }

        $now = Carbon::now();

        for ($i = 0; $i < $wanted; $i++) {
            $creator = $users->random();

            Todo::query()->create($this->attributesFor(
                $this->bucket(),
                $creator,
                $departments,
                $now,
                $i,
            ));
        }

        return Todo::query()->orderBy('id')->get();
    }

    /**
     * Which part of the life cycle a seeded To-Do represents.
     *
     * Weighted so the queues a person actually checks — the inbox and the
     * overdue list — are the full ones, which is what a real inbox looks like.
     */
    private function bucket(): string
    {
        return $this->random->pick(
            ['inbox', 'planned', 'in_progress', 'waiting', 'completed', 'overdue', 'cancelled', 'recurring'],
            [22, 16, 18, 10, 22, 8, 2, 2],
        );
    }

    /**
     * @param  ModelCollection<int, Department>  $departments
     * @return array<string, mixed>
     */
    private function attributesFor(
        string $bucket,
        User $creator,
        ModelCollection $departments,
        Carbon $now,
        int $index,
    ): array {
        $title = self::TITLES[$this->random->between(0, count(self::TITLES) - 1)];
        $estimated = $this->random->pick([15, 30, 45, 60, 90, 120]);
        $priority = $this->random->pick(
            [Priority::Low, Priority::Medium, Priority::High, Priority::Normal, Priority::Important, Priority::Urgent],
            [16, 34, 24, 12, 9, 5],
        );

        $assignee = match ($bucket) {
            // The inbox is captured but not triaged, which is what makes it an
            // inbox: nobody has decided who owns it. Neither has an item that is
            // waiting on somebody else — `waiting_on` records the blocker.
            'inbox', 'waiting' => null,
            default => $creator->id,
        };

        $dueDate = match ($bucket) {
            'overdue' => $now->copy()->subDays($this->random->between(1, 40))->toDateString(),
            'completed' => $now->copy()->subDays($this->random->between(1, 90))->toDateString(),
            'in_progress' => $now->copy()->addDays($this->random->between(-2, 12))->toDateString(),
            default => $now->copy()->addDays($this->random->between(1, 45))->toDateString(),
        };

        $completedAt = $bucket === 'completed'
            ? Carbon::parse($dueDate)->addDays($this->random->between(0, 5))->setTime(16, 0)
            : null;

        return [
            'title' => $title,
            'description' => $bucket === 'inbox'
                ? null
                : 'Captured from the weekly review. '.$this->random->pick([
                    'No deadline agreed yet.',
                    'Needs to be closed before the month-end.',
                    'Waiting on a response before it can move.',
                    'Low effort once the paperwork is to hand.',
                ]),
            'status' => match ($bucket) {
                'inbox' => WorkItemStatus::Inbox->value,
                'planned' => WorkItemStatus::Planned->value,
                'in_progress' => WorkItemStatus::InProgress->value,
                'waiting' => WorkItemStatus::Waiting->value,
                'completed' => WorkItemStatus::Completed->value,
                'overdue' => WorkItemStatus::InProgress->value,
                'cancelled' => WorkItemStatus::Cancelled->value,
                default => WorkItemStatus::Open->value,
            },
            'priority' => $priority->value,
            'visibility' => $this->random->pick(
                [Visibility::Personal, Visibility::Personal, Visibility::Team, Visibility::Private],
                [50, 50, 76, 24],
            ),
            'assignee_id' => $assignee,
            'creator_id' => $creator->id,
            'department_id' => $this->random->chance(30) ? $departments->random()->id : null,
            'start_date' => $bucket === 'inbox' ? null : $now->copy()->subDays($this->random->between(0, 20))->toDateString(),
            'due_date' => $dueDate,
            'due_time' => $this->random->chance(35) ? sprintf('%02d:%02d:00', $this->random->between(9, 17), $this->random->pick([0, 30])) : null,
            'estimated_minutes' => $estimated,
            'actual_minutes' => $completedAt === null ? null : (int) round($estimated * ($this->random->between(60, 130) / 100)),
            'completed_at' => $completedAt,
            'completed_by' => $completedAt === null ? null : $creator->id,
            'archived_from' => null,
            'waiting_on' => $bucket === 'waiting' ? $this->random->pick(['the vendor', 'the client', 'the department head', 'finance', 'legal']) : null,
            'color' => $this->random->chance(20) ? $this->random->pick(['#ef4444', '#f59e0b', '#3b82f6', '#22c55e']) : null,
            'sort_order' => $index,
            'recurrence_rule' => $bucket === 'recurring' ? $this->recurrenceRule() : null,
            'previous_occurrence_at' => null,
            'last_reminded_at' => $this->random->chance(25) ? $now->copy()->subDays($this->random->between(0, 6)) : null,
        ];
    }

    private function recurrenceRule(): string
    {
        return json_encode([
            'frequency' => $this->random->pick(['daily', 'weekly', 'biweekly', 'monthly', 'quarterly'], [14, 40, 16, 22, 8]),
            'interval' => $this->random->between(1, 3),
            'ends_on' => null,
        ]);
    }

    /**
     * @param  ModelCollection<int, User>  $users
     * @return Collection<string, Tag>
     */
    private function seedTags(ModelCollection $users): Collection
    {
        $tags = collect();

        foreach (self::TAGS as $name => $color) {
            $tags->put($name, Tag::query()->firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'color' => $color],
            ));
        }

        return $tags;
    }

    /**
     * @param  ModelCollection<int, Todo>  $todos
     * @param  ModelCollection<int, User>  $users
     */
    private function seedChecklistItems(ModelCollection $todos, ModelCollection $users): void
    {
        $wanted = (int) config('seed.volumes.todo_checklist_items', 450);

        if (DB::table('todo_checklist_items')->count() >= $wanted) {
            return;
        }

        $rows = [];

        while (count($rows) < $wanted) {
            $todo = $todos->random();

            foreach (self::CHECKLIST_ITEMS as $order => $title) {
                $completed = $todo->status === WorkItemStatus::Completed->value
                    ? true
                    : $this->random->chance(45);

                $rows[] = [
                    'todo_id' => $todo->id,
                    'title' => $title,
                    'is_completed' => $completed,
                    'completed_at' => $completed ? Carbon::now()->subDays($this->random->between(0, 40)) : null,
                    'completed_by' => $completed ? $users->random()->id : null,
                    'sort_order' => $order + 1,
                    'created_at' => $todo->created_at,
                    'updated_at' => $todo->updated_at,
                ];
            }
        }

        $this->insert('todo_checklist_items', array_slice($rows, 0, $wanted));
    }

    /**
     * @param  ModelCollection<int, Todo>  $todos
     * @param  ModelCollection<int, User>  $users
     */
    private function seedWatchers(ModelCollection $todos, ModelCollection $users): void
    {
        $wanted = (int) config('seed.volumes.todo_watchers', 320);

        if (DB::table('todo_watchers')->count() >= $wanted) {
            return;
        }

        $userIds = $users->pluck('id')->all();
        $rows = [];
        $seen = [];

        // The table is unique on (todo_id, user_id); a repeat draw is discarded.
        while (count($rows) < $wanted) {
            $todoId = $todos->random()->id;
            $userId = $userIds[$this->random->between(0, count($userIds) - 1)];
            $key = $todoId.':'.$userId;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $rows[] = [
                'todo_id' => $todoId,
                'user_id' => $userId,
                'created_at' => Carbon::now()->subDays($this->random->between(0, 120)),
                'updated_at' => Carbon::now()->subDays($this->random->between(0, 120)),
            ];
        }

        $this->insert('todo_watchers', $rows);
    }

    /**
     * Links to the other work items — a To-Do pointing at the task or the
     * obligation that gives it context.
     *
     * @param  ModelCollection<int, Todo>  $todos
     */
    private function seedLinks(ModelCollection $todos): void
    {
        $wanted = (int) config('seed.volumes.todo_links', 220);

        if (DB::table('todo_links')->count() >= $wanted) {
            return;
        }

        $taskIds = DB::table('tasks')->pluck('id')->all();
        $obligationIds = DB::table('obligations')->pluck('id')->all();

        if ($taskIds === [] && $obligationIds === []) {
            return;
        }

        $rows = [];
        $seen = [];

        while (count($rows) < $wanted) {
            $linkToTask = $obligationIds === [] || ($taskIds !== [] && $this->random->chance(65));
            $linkableType = $linkToTask ? 'Modules\Tasks\Models\Task' : 'Modules\Obligations\Models\Obligation';
            $linkableId = $linkToTask
                ? $taskIds[$this->random->between(0, count($taskIds) - 1)]
                : $obligationIds[$this->random->between(0, count($obligationIds) - 1)];

            $todoId = $todos->random()->id;
            $key = $todoId.':'.$linkableType.':'.$linkableId;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $rows[] = [
                'todo_id' => $todoId,
                'linkable_type' => $linkableType,
                'linkable_id' => $linkableId,
                'link_type' => $this->random->pick(['related', 'relates_to', 'blocks', 'blocked_by', 'derived_from'], [24, 30, 16, 16, 14]),
                'created_at' => Carbon::now()->subDays($this->random->between(0, 120)),
                'updated_at' => Carbon::now()->subDays($this->random->between(0, 120)),
            ];
        }

        $this->insert('todo_links', $rows);
    }

    /**
     * @param  ModelCollection<int, Todo>  $todos
     * @param  ModelCollection<int, User>  $users
     */
    private function seedComments(ModelCollection $todos, ModelCollection $users): void
    {
        $wanted = (int) config('seed.volumes.todo_comments', 180);

        if (Comment::query()->where('commentable_type', (new Todo)->getMorphClass())->count() >= $wanted) {
            return;
        }

        $bodies = [
            'Picking this up today.',
            'Not started — waiting on the paperwork.',
            'Partly done. The rest is with legal.',
            'Closed this, superseded by the new request.',
            'Adding it to the agenda for review.',
            'Done and confirmed with the requester.',
        ];

        $rows = [];
        $morphClass = (new Todo)->getMorphClass();

        while (count($rows) < $wanted) {
            $rows[] = [
                'commentable_type' => $morphClass,
                'commentable_id' => $todos->random()->id,
                'user_id' => $users->random()->id,
                'body' => $this->random->pick($bodies),
                'created_at' => Carbon::now()->subDays($this->random->between(0, 90)),
                'updated_at' => Carbon::now()->subDays($this->random->between(0, 90)),
            ];
        }

        $this->insert('comments', $rows);
    }

    /**
     * @param  ModelCollection<int, Todo>  $todos
     * @param  ModelCollection<int, User>  $users
     */
    private function seedReminders(ModelCollection $todos, ModelCollection $users): void
    {
        $wanted = (int) config('seed.volumes.todo_reminders', 260);

        if (Reminder::query()->where('subject_type', (new Todo)->getMorphClass())->count() >= $wanted) {
            return;
        }

        $morphClass = (new Todo)->getMorphClass();
        $rows = [];
        $seen = [];

        // `reminders` is unique on (subject_type, subject_id, remind_at): one
        // reminder per item per moment. A repeat draw is discarded rather than
        // written.
        while (count($rows) < $wanted) {
            $todo = $todos->random();
            $remindAt = $this->random->chance(40)
                ? Carbon::now()->addDays($this->random->between(1, 21))->setTime(9, 0)
                : Carbon::now()->subDays($this->random->between(1, 60))->setTime(9, 0);

            $key = $todo->id.':'.$remindAt->toDateTimeString();

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;

            $status = $remindAt->isPast()
                ? $this->random->pick(['sent', 'sent', 'sent', 'cancelled', 'failed'], [50, 50, 50, 30, 8])
                : 'pending';

            $rows[] = [
                'subject_type' => $morphClass,
                'subject_id' => $todo->id,
                'remind_at' => $remindAt,
                'channel' => $this->random->pick(['database', 'mail', 'in_app', 'sms'], [46, 30, 20, 4]),
                'status' => $status,
                'sent_at' => $status === 'sent' ? $remindAt : null,
                'cancelled_at' => $status === 'cancelled' ? $remindAt : null,
                'created_by' => $users->random()->id,
                'created_at' => $remindAt->copy()->subDays($this->random->between(1, 20)),
                'updated_at' => $remindAt,
            ];
        }

        $this->insert('reminders', $rows);
    }

    /**
     * @param  ModelCollection<int, Todo>  $todos
     * @param  Collection<string, Tag>  $tags
     * @param  ModelCollection<int, User>  $users
     */
    private function seedTaggables(ModelCollection $todos, Collection $tags, ModelCollection $users): void
    {
        $wanted = (int) config('seed.volumes.taggables', 320);

        if (DB::table('taggables')->count() >= $wanted) {
            return;
        }

        $tagIds = $tags->pluck('id')->all();
        $morphClass = (new Todo)->getMorphClass();
        $rows = [];
        $seen = [];

        while (count($rows) < $wanted) {
            $todoId = $todos->random()->id;
            $tagId = $tagIds[$this->random->between(0, count($tagIds) - 1)];
            $key = $todoId.':'.$tagId;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $rows[] = [
                'tag_id' => $tagId,
                'taggable_type' => $morphClass,
                'taggable_id' => $todoId,
                'created_by' => $users->random()->id,
                'created_at' => Carbon::now()->subDays($this->random->between(0, 90)),
                'updated_at' => Carbon::now()->subDays($this->random->between(0, 90)),
            ];
        }

        $this->insert('taggables', $rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insert(string $table, array $rows): void
    {
        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
