<?php

namespace App\Providers;

use App\Enums\Role as RoleSlug;
use App\Models\Role;
use App\Models\User;
use App\Observers\ActivityObserver;
use App\Observers\AuditObserver;
use App\Policies\MeetingActionItemPolicy;
use App\Policies\MeetingPolicy;
use App\Policies\ObligationPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\RolePolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Obligations\Models\Obligation;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;
use Modules\Todos\Models\Todo;
use Modules\Todos\Policies\TodoPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Model => policy map. Registered explicitly rather than relying on
     * convention so a renamed model can never silently lose its policy.
     *
     * @var array<class-string<Model>, class-string>
     */
    protected array $policies = [
        Task::class => TaskPolicy::class,
        Project::class => ProjectPolicy::class,
        Meeting::class => MeetingPolicy::class,
        MeetingActionItem::class => MeetingActionItemPolicy::class,
        Obligation::class => ObligationPolicy::class,
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        Todo::class => TodoPolicy::class,
    ];

    /**
     * Work-item models whose mutations are recorded on the `activity_logs`
     * timeline as well as the security audit trail.
     *
     * @var array<class-string<Model>, list<class-string>>
     */
    protected array $observed = [
        User::class => [AuditObserver::class, ActivityObserver::class],
        Role::class => [AuditObserver::class],
        Task::class => [AuditObserver::class, ActivityObserver::class],
        Project::class => [AuditObserver::class, ActivityObserver::class],
        Meeting::class => [AuditObserver::class, ActivityObserver::class],
        Obligation::class => [AuditObserver::class, ActivityObserver::class],
    ];

    /**
     * Module-level administrative abilities that govern shared reference data or
     * cross-record actions, so they take no model and cannot be a policy method.
     * Named `module.action` to match the seeded permission strings.
     *
     * @var array<string, string>
     */
    protected array $gates = [
        'meeting.manage_tags' => 'meeting.manage_tags',
        'meeting.manage_types' => 'meeting.manage_types',
        'meeting.view_reports' => 'meeting.view_reports',
        'meeting.delete_notification_logs' => '@super-admin',
        'obligation.manage_vendors' => 'obligation.manage_settings',
        'obligation.view_reports' => 'obligation.view_reports',
        'obligation.delete_notification_logs' => '@super-admin',
        'task.view_reports' => 'report.view',
        'task.delete_notification_logs' => '@super-admin',
        'todo.view_all' => 'todos.view_all',
        'todo.delete_notification_logs' => '@super-admin',
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Laravel's default resolver only strips the `App\Models\` prefix, so a
        // module model resolves to `Database\Factories\Modules\Tasks\Models\TaskFactory`
        // and never matches. Resolve on the class basename instead, which is the
        // single convention every factory in this codebase already follows.
        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Database\\Factories\\'.class_basename($modelName).'Factory',
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        foreach ($this->observed as $model => $observers) {
            $model::observe($observers);
        }

        foreach ($this->gates as $ability => $requirement) {
            $this->defineGate($ability, $requirement);
        }

        // A super-admin bypasses every registered policy. This mirrors the
        // `hasRole('super-admin')` short-circuit the ad-hoc controller checks
        // performed, and is additive for the object policies above.
        Gate::before(fn (User $user): ?bool => $user->hasRole(RoleSlug::SuperAdmin->value) ? true : null);

        $this->enforceStrictMassAssignment();
    }

    /**
     * `@super-admin` means "super-admin only"; anything else names a permission
     * that the user must hold through one of their roles.
     */
    protected function defineGate(string $ability, string $requirement): void
    {
        if ($requirement === '@super-admin') {
            Gate::define(
                $ability,
                fn (User $user): bool => $user->hasRole(RoleSlug::SuperAdmin->value),
            );

            return;
        }

        Gate::define(
            $ability,
            fn (User $user): bool => $user->hasPermission($requirement),
        );
    }

    /**
     * Surface accidental non-fillable attribute writes as exceptions rather than
     * silently dropping them. Enabled in local and testing only: in production a
     * forgotten column must degrade, not take the request down.
     */
    protected function enforceStrictMassAssignment(): void
    {
        if (app()->environment(['local', 'testing'])) {
            Model::preventSilentlyDiscardingAttributes();
        }
    }
}
