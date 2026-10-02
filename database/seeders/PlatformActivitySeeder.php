<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\LoginLog;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Meetings\Models\Meeting;
use Modules\Obligations\Models\Obligation;
use Modules\Tasks\Models\Task;
use Modules\Todos\Models\Todo;

/**
 * The platform tables that record what happened: activity, sign-in, audit and
 * in-app notifications.
 *
 * None of these are decoration. The activity screen, the security dashboard,
 * the audit trail and the notification bell are all empty without them, and an
 * empty table reads as "nothing has happened here" rather than as "this
 * installation is new" — which is the wrong impression for a demonstration.
 *
 * The security events matter most: `login_logs` with its `failed` and `locked`
 * rows is what the account-lockout and suspicious-activity views filter on, and
 * a log containing nothing but successful sign-ins exercises none of it.
 */
class PlatformActivitySeeder extends Seeder
{
    use WithoutModelEvents;

    private DeterministicSequence $random;

    public function run(): void
    {
        $this->random = new DeterministicSequence;

        $users = User::query()->where('status', 'active')->get();

        if ($users->isEmpty()) {
            $this->command?->warn('  Activity: skipped, no accounts exist.');

            return;
        }

        $this->seedActivityLogs($users);
        $this->seedLoginLogs($users);
        $this->seedAuditLogs($users);
        $this->seedNotifications($users);
        $this->seedNotificationPreferences($users);

        $this->command?->info(sprintf(
            '  Activity: %d logs, %d sign-ins, %d audit entries, %d notifications, %d preferences',
            ActivityLog::query()->count(),
            LoginLog::query()->count(),
            AuditLog::query()->count(),
            DB::table('notifications')->count(),
            DB::table('notification_preferences')->count(),
        ));
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function seedActivityLogs(Collection $users): void
    {
        $wanted = (int) config('seed.volumes.activity_logs', 800);

        if (ActivityLog::query()->count() >= $wanted) {
            return;
        }

        $userIds = $users->pluck('id')->all();
        $subjects = $this->subjects();
        $rows = [];

        while (count($rows) < $wanted) {
            $at = Carbon::now()->subDays($this->random->between(0, 180))->setTime(
                $this->random->between(8, 20),
                $this->random->pick([0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55]),
            );
            $action = $this->random->pick(['created', 'updated', 'viewed', 'deleted', 'exported'], [40, 32, 18, 6, 4]);
            $subject = $this->random->pick($subjects);

            $rows[] = [
                'user_id' => $userIds[$this->random->between(0, count($userIds) - 1)],
                'module_name' => $subject['module'],
                'record_id' => $subject['id']($this->random),
                'action' => $action,
                'old_value' => $action === 'updated' ? json_encode(['status' => 'pending']) : null,
                'new_value' => json_encode(['status' => $action === 'created' ? 'draft' : 'active']),
                'ip_address' => $this->ip(),
                'subject_type' => $subject['type'],
                'subject_id' => $subject['id']($this->random),
                'user_agent' => $this->userAgent(),
                'created_at' => $at,
                'updated_at' => $at,
            ];
        }

        $this->insert('activity_logs', $rows);
    }

    /**
     * Every module the activity log can name, with a callable that draws a
     * record id from that module.
     *
     * The record ids are real rather than random numbers, so opening a logged
     * record lands on a row that exists. A log pointing at nothing makes the
     * activity screen look broken in a way no amount of data volume fixes.
     *
     * @return list<array{module: string, type: class-string, id: callable}>
     */
    private function subjects(): array
    {
        $modules = [
            ['module' => 'tasks', 'type' => Task::class],
            ['module' => 'todos', 'type' => Todo::class],
            ['module' => 'meetings', 'type' => Meeting::class],
            ['module' => 'obligations', 'type' => Obligation::class],
        ];

        $subjects = [];

        foreach ($modules as ['module' => $module, 'type' => $type]) {
            /** @var Model $model */
            $model = new $type;
            $ids = DB::table($model->getTable())->pluck('id')->all();

            if ($ids === []) {
                continue;
            }

            $subjects[] = [
                'module' => $module,
                'type' => $type,
                'id' => fn (DeterministicSequence $random): int => $ids[$random->between(0, count($ids) - 1)],
            ];
        }

        return $subjects;
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function seedLoginLogs(Collection $users): void
    {
        $wanted = (int) config('seed.volumes.login_logs', 420);

        if (LoginLog::query()->count() >= $wanted) {
            return;
        }

        $rows = [];

        while (count($rows) < $wanted) {
            $user = $users->random();
            $at = Carbon::now()->subDays($this->random->between(0, 60))->setTime(
                $this->random->between(7, 21),
                $this->random->pick([0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55]),
            );

            // Most sign-ins succeed. The failures cluster around a handful of
            // accounts, which is what makes them read as attempts on those
            // accounts rather than as background noise.
            $event = $this->random->pick(
                [LoginLog::LOGIN, LoginLog::LOGOUT, LoginLog::FAILED, LoginLog::LOCKED],
                [62, 26, 9, 3],
            );

            $rows[] = [
                'user_id' => $event === LoginLog::FAILED ? null : $user->id,
                'email' => $user->email,
                'event' => $event,
                'ip_address' => $this->ip(),
                'user_agent' => $this->userAgent(),
                'device' => $this->random->pick(['Desktop', 'Mobile', 'Tablet', 'Unknown'], [46, 34, 12, 8]),
                'failure_reason' => match ($event) {
                    LoginLog::FAILED => $this->random->pick([
                        'Invalid credentials.',
                        'Password expired.',
                        'Account temporarily locked after repeated failures.',
                    ]),
                    LoginLog::LOCKED => 'Too many failed attempts.',
                    default => null,
                },
                'attempted_at' => $at,
                'created_at' => $at,
                'updated_at' => $at,
            ];
        }

        $this->insert('login_logs', $rows);
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function seedAuditLogs(Collection $users): void
    {
        $wanted = (int) config('seed.volumes.audit_logs', 320);

        if (AuditLog::query()->count() >= $wanted) {
            return;
        }

        $userIds = $users->pluck('id')->all();
        $subjects = $this->subjects();
        $rows = [];

        while (count($rows) < $wanted) {
            $at = Carbon::now()->subDays($this->random->between(0, 180))->setTime(
                $this->random->between(8, 20),
                $this->random->pick([0, 10, 20, 30, 40, 50]),
            );
            $subject = $this->random->pick($subjects);

            $rows[] = [
                'user_id' => $userIds[$this->random->between(0, count($userIds) - 1)],
                'event' => $this->random->pick(['created', 'updated', 'deleted', 'restored'], [30, 46, 18, 6]),
                'auditable_type' => $subject['type'],
                'auditable_id' => $subject['id']($this->random),
                'old_values' => json_encode(['status' => 'pending', 'priority' => 'medium']),
                'new_values' => json_encode(['status' => 'in_progress', 'priority' => 'high']),
                'metadata' => json_encode(['source' => 'web', 'reason' => null]),
                'created_at' => $at,
                'ip_address' => $this->ip(),
                'user_agent' => $this->userAgent(),
            ];
        }

        $this->insert('tyro_audit_logs', $rows);
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function seedNotifications(Collection $users): void
    {
        $wanted = (int) config('seed.volumes.notifications', 520);

        if (DB::table('notifications')->count() >= $wanted) {
            return;
        }

        $rows = [];
        $types = [
            ['TaskAssigned', 'A task has been assigned to you.'],
            ['TaskOverdue', 'A task you own has passed its due date.'],
            ['MeetingInvitation', 'You have been invited to a meeting.'],
            ['MinutesPublished', 'The minutes of a meeting have been published.'],
            ['ObligationReminder', 'An obligation is approaching its expiry date.'],
            ['TodoAssigned', 'A to-do has been assigned to you.'],
        ];

        while (count($rows) < $wanted) {
            $at = Carbon::now()->subDays($this->random->between(0, 60));
            [$type, $message] = $this->random->pick($types);

            // Roughly a third read: an unread-only bell never demonstrates the
            // read state, and read-only never demonstrates the unread badge.
            $readAt = $this->random->chance(35) ? $at->copy()->addHours($this->random->between(1, 48)) : null;

            $rows[] = [
                'id' => (string) Str::uuid(),
                'type' => $type,
                'notifiable_type' => (new User)->getMorphClass(),
                'notifiable_id' => $users->random()->id,
                'data' => json_encode(['message' => $message, 'link' => null]),
                'read_at' => $readAt,
                'created_at' => $at,
                'updated_at' => $readAt ?? $at,
            ];
        }

        $this->insert('notifications', $rows);
    }

    /**
     * The channel matrix each account would have been configured with.
     *
     * @param  Collection<int, User>  $users
     */
    private function seedNotificationPreferences(Collection $users): void
    {
        $wanted = (int) config('seed.volumes.notification_preferences', 1200);

        if (DB::table('notification_preferences')->count() >= $wanted) {
            return;
        }

        $rows = [];
        $types = [
            'task.assigned', 'task.overdue', 'task.completed',
            'meeting.invitation', 'meeting.reminder', 'minutes.published',
            'obligation.reminder', 'obligation.escalated',
            'todo.assigned', 'todo.due_soon', 'todo.mentioned',
        ];
        $channels = ['database', 'mail', 'in_app', 'sms'];
        $rows = [];
        $seen = [];

        // The table is unique on (user_id, notification_type, channel): one row
        // is one person's preference for one event over one channel, so a repeat
        // draw is discarded rather than written.
        while (count($rows) < $wanted) {
            $user = $users->random();
            $type = $this->random->pick($types);
            $channel = $this->random->pick($channels);
            $key = $user->id.':'.$type.':'.$channel;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $rows[] = [
                'user_id' => $user->id,
                'notification_type' => $type,
                'channel' => $channel,
                'enabled' => $this->random->chance(74),
                'created_at' => Carbon::now()->subDays($this->random->between(0, 300)),
                'updated_at' => Carbon::now()->subDays($this->random->between(0, 300)),
            ];
        }

        $this->insert('notification_preferences', $rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insert(string $table, array $rows): void
    {
        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    private function ip(): string
    {
        return sprintf(
            '10.%d.%d.%d',
            $this->random->between(0, 40),
            $this->random->between(0, 255),
            $this->random->between(1, 254),
        );
    }

    private function userAgent(): string
    {
        return $this->random->pick([
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
        ]);
    }
}
