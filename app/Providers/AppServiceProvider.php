<?php

namespace App\Providers;

use App\Enums\Role as RoleSlug;
use App\Models\Role;
use App\Models\User;
use App\Observers\AuditObserver;
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
use Modules\Obligations\Models\Obligation;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;

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
        Obligation::class => ObligationPolicy::class,
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
    ];

    /**
     * Models whose create/update/delete is written to `tyro_audit_logs`.
     *
     * Identity and privilege records only. Operational work items (tasks,
     * meetings, obligations) write to `activity_logs` instead, which is a user
     * facing timeline rather than a security record.
     *
     * @var list<class-string<Model>>
     */
    protected array $audited = [
        User::class,
        Role::class,
    ];

    protected array $observers = [
        User::class => [AuditObserver::class],
        Role::class => [AuditObserver::class],
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

        foreach ($this->observers as $model => $observers) {
            $model::observe($observers);
        }

        // A super-admin bypasses every registered policy. This mirrors the
        // `hasRole('super-admin')` short-circuit the ad-hoc controller checks
        // performed, and is additive for the object policies above.
        Gate::before(fn (User $user): ?bool => $user->hasRole(RoleSlug::SuperAdmin->value) ? true : null);

        $this->enforceStrictMassAssignment();
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
