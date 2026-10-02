<?php

namespace Tests\Support;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Policies\MeetingActionItemPolicy;
use App\Policies\MeetingPolicy;
use App\Policies\ObligationPolicy;
use App\Policies\ProjectPolicy;
use App\Policies\RolePolicy;
use App\Policies\TaskPolicy;
use App\Policies\UserPolicy;
use Database\Factories\MeetingFactory;
use Database\Factories\ObligationFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\RoleFactory;
use Database\Factories\TaskFactory;
use Database\Factories\UserFactory;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationResponsibility;
use Modules\Projects\Models\Project;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TaskWatcher;
use Modules\Todos\Models\Todo;
use Modules\Todos\Policies\TodoPolicy;

/**
 * The authorization matrix — one row per policy ability.
 *
 * Every row is a PAIR of closures, each returning `[actor, gateArguments]`. The
 * actor and its subject are built together inside one closure because they are one
 * fact: a user cannot "own" a task unless the same closure created both. A matrix
 * built from separately-constructed actor and subject fixtures would pass while
 * describing a relationship the database does not contain.
 *
 * The refused actor is always a user with NO role and NO permissions, so a denial
 * cannot pass by accident through some other grant.
 *
 * This is a REGISTRY, not a set of hand-picked cases: `PolicyMatrixTest` reflects
 * over the policy classes and fails if an ability has no row here, so adding a
 * method to a policy without testing it is a red suite rather than a silent gap.
 */
class PolicyMatrix
{
    /**
     * Every policy the application registers.
     *
     * @return list<class-string>
     */
    public static function policies(): array
    {
        return [
            TaskPolicy::class,
            MeetingPolicy::class,
            MeetingActionItemPolicy::class,
            ObligationPolicy::class,
            ProjectPolicy::class,
            UserPolicy::class,
            RolePolicy::class,
            TodoPolicy::class,
        ];
    }

    /**
     * The model class each policy guards, for class-scoped abilities.
     *
     * The Gate cannot resolve a policy from a bare ability name with no argument, so
     * `Gate::allows('create')` denies everything — quietly, and only for class-scoped
     * abilities. Every policy in the matrix therefore declares the class it guards.
     *
     * @param  class-string  $policy
     * @return class-string
     */
    public static function subjectClassFor(string $policy): string
    {
        return match ($policy) {
            TaskPolicy::class => Task::class,
            MeetingPolicy::class => Meeting::class,
            MeetingActionItemPolicy::class => MeetingActionItem::class,
            ObligationPolicy::class => Obligation::class,
            ProjectPolicy::class => Project::class,
            UserPolicy::class => User::class,
            RolePolicy::class => Role::class,
            TodoPolicy::class => Todo::class,
            default => throw new \LogicException("No subject class declared for {$policy}."),
        };
    }

    /**
     * @return list<PolicyCase>
     */
    public static function cases(): array
    {
        return [
            ...self::taskCases(),
            ...self::meetingCases(),
            ...self::actionItemCases(),
            ...self::obligationCases(),
            ...self::projectCases(),
            ...self::userCases(),
            ...self::roleCases(),
            ...self::todoCases(),
        ];
    }

    /**
     * @return list<PolicyCase>
     */
    private static function taskCases(): array
    {
        return [
            new PolicyCase(
                TaskPolicy::class,
                'viewAny',
                'class',
                // Deliberately TRUE for a user with no permissions at all. `viewAny`
                // is the entry point for every authenticated list, and narrowing it
                // would hide the list rather than filter it. The refusal that
                // matters is per record, and every other row covers it.
                fn (): array => [self::nobody(), []],
                fn (): array => [null, []],
                'Allowed for any authenticated user, refused for a guest. `view` is where the filtering lives.',
            ),

            new PolicyCase(
                TaskPolicy::class,
                'view',
                'record',
                self::ownerOfTask(...),
                self::strangerToTask(...),
                'Ownership grants visibility.',
            ),

            new PolicyCase(
                TaskPolicy::class,
                'view',
                'record',
                self::watcherOfTask(...),
                self::strangerToTask(...),
                'A watcher sees the task but is not thereby an owner.',
            ),

            new PolicyCase(
                TaskPolicy::class,
                'view',
                'record',
                fn (): array => [self::withPermission('task.view'), [self::anyTask()]],
                fn (): array => [self::nobody(), [self::anyTask()]],
                'The `task.view` permission widens the list.',
            ),

            new PolicyCase(
                TaskPolicy::class,
                'create',
                'class',
                fn (): array => [self::withPermission('task.create'), []],
                fn (): array => [self::nobody(), []],
            ),

            new PolicyCase(
                TaskPolicy::class,
                'update',
                'record',
                self::ownerOfTask(...),
                self::strangerToTask(...),
            ),

            new PolicyCase(
                TaskPolicy::class,
                'delete',
                'record',
                fn (): array => [self::withPermission('task.delete'), [self::anyTask()]],
                // Ownership is deliberately NOT enough to delete: deletion is
                // permission-gated even for the person who wrote the task.
                fn (): array => [self::ownerOfTask()[0], [self::ownerOfTask()[1]]],
                'Owning a task does not grant the right to delete it.',
            ),

            new PolicyCase(
                TaskPolicy::class,
                'transfer',
                'record',
                self::ownerOfTask(...),
                self::strangerToTask(...),
            ),

            new PolicyCase(
                TaskPolicy::class,
                'addRemark',
                'record',
                self::ownerOfTask(...),
                self::strangerToTask(...),
            ),

            new PolicyCase(
                TaskPolicy::class,
                'watch',
                'record',
                self::watcherOfTask(...),
                fn (): array => [self::ownerOfTask()[0], [self::ownerOfTask()[1]]],
                'The owner is refused: owning is not the same as watching.',
            ),

            new PolicyCase(
                TaskPolicy::class,
                'manageWatchers',
                'record',
                self::ownerOfTask(...),
                self::strangerToTask(...),
                'A watcher may see a task but may not add other watchers to it.',
            ),

            new PolicyCase(
                TaskPolicy::class,
                'logTime',
                'record',
                fn (): array => [self::withPermission('task.update'), [self::anyTask()]],
                fn (): array => [self::watcherOfTask()[0], [self::watcherOfTask()[1]]],
                // A watcher can see the work but cannot log hours against it.
                'Watching does not confer the right to record time.',
            ),

            new PolicyCase(
                TaskPolicy::class,
                'createSubtask',
                'record',
                self::ownerOfTask(...),
                self::strangerToTask(...),
            ),

            new PolicyCase(
                TaskPolicy::class,
                'reparent',
                'record',
                self::ownerOfTask(...),
                self::strangerToTask(...),
                'A move changes the whole ancestry, so it is gated separately.',
            ),
        ];
    }

    /**
     * @return list<PolicyCase>
     */
    private static function meetingCases(): array
    {
        return [
            new PolicyCase(
                MeetingPolicy::class,
                'viewAny',
                'class',
                fn (): array => [self::nobody(), []],
                fn (): array => [null, []],
                'Allowed for any authenticated user, refused for a guest. `view` is where meeting visibility is filtered.',
            ),

            new PolicyCase(
                MeetingPolicy::class,
                'view',
                'record',
                self::organiserOfMeeting(...),
                self::strangerToMeeting(...),
                'Meetings had no object-level check at all before Phase 2.',
            ),

            new PolicyCase(
                MeetingPolicy::class,
                'view',
                'record',
                self::participantInMeeting(...),
                self::strangerToMeeting(...),
                'A participant sees the meeting they were invited to.',
            ),

            new PolicyCase(
                MeetingPolicy::class,
                'create',
                'class',
                fn (): array => [self::withPermission('meeting.create'), []],
                fn (): array => [self::nobody(), []],
            ),

            new PolicyCase(
                MeetingPolicy::class,
                'update',
                'record',
                self::organiserOfMeeting(...),
                self::participantInMeeting(...),
                'A participant is refused: visibility is not edit rights.',
            ),

            new PolicyCase(
                MeetingPolicy::class,
                'delete',
                'record',
                self::organiserOfMeeting(...),
                self::participantInMeeting(...),
            ),

            new PolicyCase(
                MeetingPolicy::class,
                'transition',
                'record',
                self::organiserOfMeeting(...),
                self::strangerToMeeting(...),
                'Lifecycle transitions belong to the organizer.',
            ),

            new PolicyCase(
                MeetingPolicy::class,
                'print',
                'record',
                self::participantInMeeting(...),
                self::strangerToMeeting(...),
                'Printing is a read, so it inherits `view`.',
            ),

            new PolicyCase(
                MeetingPolicy::class,
                'manageMinutes',
                'record',
                self::organiserOfMeeting(...),
                self::participantInMeeting(...),
                'Minutes authorship belongs to the organizer.',
            ),

            new PolicyCase(
                MeetingPolicy::class,
                'submitMinutes',
                'record',
                self::organiserOfMeeting(...),
                self::strangerToMeeting(...),
                'A separate permission from approval, so the two can be split.',
            ),

            new PolicyCase(
                MeetingPolicy::class,
                'approveMinutes',
                'record',
                fn (): array => [self::withPermission('meeting.approve_minutes'), [self::anyMeeting()]],
                self::organiserOfMeeting(...),
                'The author is refused: approval is deliberately separable.',
            ),

            new PolicyCase(
                MeetingPolicy::class,
                'publishMinutes',
                'record',
                fn (): array => [self::withPermission('meeting.publish_minutes'), [self::anyMeeting()]],
                fn (): array => [self::withPermission('meeting.approve_minutes'), [self::anyMeeting()]],
                'Publishing is a third step, distinct from approving.',
            ),

            new PolicyCase(
                MeetingPolicy::class,
                'manageActionItems',
                'record',
                self::organiserOfMeeting(...),
                self::participantInMeeting(...),
            ),

            new PolicyCase(
                MeetingPolicy::class,
                'assignActionItems',
                'record',
                self::organiserOfMeeting(...),
                self::strangerToMeeting(...),
                'Assigning work inside a meeting is the organizer\'s call.',
            ),
        ];
    }

    /**
     * @return list<PolicyCase>
     */
    private static function actionItemCases(): array
    {
        return [
            new PolicyCase(
                MeetingActionItemPolicy::class,
                'view',
                'record',
                self::organiserOfActionItem(...),
                self::strangerToActionItem(...),
                'Inherits visibility from the parent meeting.',
            ),

            new PolicyCase(
                MeetingActionItemPolicy::class,
                'view',
                'record',
                self::assigneeOfActionItem(...),
                self::strangerToActionItem(...),
                'Without this clause an assignee could see the item and not open it.',
            ),

            new PolicyCase(
                MeetingActionItemPolicy::class,
                'create',
                'record',
                fn (): array => [self::withPermission('meeting.create_action'), [self::anyActionItem()]],
                fn (): array => [self::nobody(), [self::anyActionItem()]],
            ),

            new PolicyCase(
                MeetingActionItemPolicy::class,
                'update',
                'record',
                self::assigneeOfActionItem(...),
                self::strangerToActionItem(...),
                'The assignee can progress their own item.',
            ),

            new PolicyCase(
                MeetingActionItemPolicy::class,
                'delete',
                'record',
                fn (): array => [self::withPermission('meeting.create_action'), [self::anyActionItem()]],
                self::assigneeOfActionItem(...),
                'Progressing an item is not the same as erasing it.',
            ),

            new PolicyCase(
                MeetingActionItemPolicy::class,
                'linkTask',
                'record',
                fn (): array => [self::withPermission('meeting.assign_action'), [self::anyActionItem()]],
                self::assigneeOfActionItem(...),
                'Linking a Task is an assignment decision, not an edit.',
            ),
        ];
    }

    /**
     * @return list<PolicyCase>
     */
    private static function obligationCases(): array
    {
        return [
            new PolicyCase(
                ObligationPolicy::class,
                'viewAny',
                'class',
                fn (): array => [self::nobody(), []],
                fn (): array => [null, []],
                'Allowed for any authenticated user, refused for a guest. `view` is where obligation visibility is filtered.',
            ),

            new PolicyCase(
                ObligationPolicy::class,
                'view',
                'record',
                self::ownerOfObligation(...),
                self::strangerToObligation(...),
                'Obligations had no object-level check at all before Phase 2.',
            ),

            new PolicyCase(
                ObligationPolicy::class,
                'view',
                'record',
                self::activeResponsibleForObligation(...),
                self::strangerToObligation(...),
                'An ACTIVE responsibility grants visibility.',
            ),

            new PolicyCase(
                ObligationPolicy::class,
                'view',
                'record',
                // Somebody else's obligation, so this row is about the visibility
                // clause and not about ownership.
                fn (): array => [self::withPermission('obligation.view'), [self::anyObligation()]],
                self::dischargedResponsibleForObligation(...),
                // A discharged responsibility must STOP granting visibility, or a
                // closed-out duty becomes permanent read access.
                'A DISCHARGED responsibility is refused.',
            ),

            new PolicyCase(
                ObligationPolicy::class,
                'create',
                'class',
                fn (): array => [self::withPermission('obligation.create'), []],
                fn (): array => [self::nobody(), []],
            ),

            new PolicyCase(
                ObligationPolicy::class,
                'update',
                'record',
                self::ownerOfObligation(...),
                self::strangerToObligation(...),
            ),

            new PolicyCase(
                ObligationPolicy::class,
                'delete',
                'record',
                self::ownerOfObligation(...),
                self::strangerToObligation(...),
                'Ownership or `obligation.delete`; neither alone is insufficient.',
            ),

            new PolicyCase(
                ObligationPolicy::class,
                'delete',
                'record',
                fn (): array => [self::withPermission('obligation.delete'), [self::anyObligation()]],
                self::strangerToObligation(...),
            ),

            new PolicyCase(
                ObligationPolicy::class,
                'approve',
                'record',
                self::namedApproverOfObligation(...),
                self::strangerToObligation(...),
                'The named approver decides.',
            ),

            new PolicyCase(
                ObligationPolicy::class,
                'approve',
                'record',
                fn (): array => [self::withPermission('obligation.approve'), [self::anyObligation()]],
                self::ownerOfObligation(...),
                // The owner cannot approve their own obligation: `approve` is the
                // named approver's act, or a dedicated permission's.
                'The owner is refused: approving is separate from owning.',
            ),

            new PolicyCase(
                ObligationPolicy::class,
                'approve',
                'record',
                self::namedApproverOfObligation(...),
                self::activeResponsibleForObligation(...),
                'A responsible party is refused: only the named approver or a dedicated permission approves.',
            ),

            new PolicyCase(
                ObligationPolicy::class,
                'assign',
                'record',
                self::ownerOfObligation(...),
                self::strangerToObligation(...),
            ),

            new PolicyCase(
                ObligationPolicy::class,
                'renew',
                'record',
                self::ownerOfObligation(...),
                self::strangerToObligation(...),
            ),

            new PolicyCase(
                ObligationPolicy::class,
                'manageDocuments',
                'record',
                self::ownerOfObligation(...),
                self::strangerToObligation(...),
            ),
        ];
    }

    /**
     * @return list<PolicyCase>
     */
    private static function projectCases(): array
    {
        return [
            new PolicyCase(
                ProjectPolicy::class,
                'viewAny',
                'class',
                fn (): array => [self::nobody(), []],
                fn (): array => [null, []],
                'Allowed for any authenticated user, refused for a guest. `view` filters the records.',
            ),

            new PolicyCase(
                ProjectPolicy::class,
                'view',
                'record',
                self::ownerOfProject(...),
                self::strangerToProject(...),
            ),

            new PolicyCase(
                ProjectPolicy::class,
                'create',
                'class',
                fn (): array => [self::withPermission('project.create'), []],
                fn (): array => [self::nobody(), []],
            ),

            new PolicyCase(
                ProjectPolicy::class,
                'update',
                'record',
                self::ownerOfProject(...),
                self::strangerToProject(...),
            ),

            new PolicyCase(
                ProjectPolicy::class,
                'delete',
                'record',
                fn (): array => [self::withPermission('project.delete'), [self::anyProject()]],
                self::ownerOfProject(...),
                'Owning a project does not grant the right to delete it.',
            ),
        ];
    }

    /**
     * @return list<PolicyCase>
     */
    private static function userCases(): array
    {
        return [
            new PolicyCase(
                UserPolicy::class,
                'viewAny',
                'class',
                fn (): array => [self::withPermission('user.manage'), []],
                fn (): array => [self::nobody(), []],
                'The staff list is behind a permission, unlike every other list.',
            ),

            new PolicyCase(
                UserPolicy::class,
                'view',
                'record',
                self::oneself(...),
                fn (): array => [self::nobody(), [self::someoneElse()->id]],
                'A user always sees their own record.',
            ),

            new PolicyCase(
                UserPolicy::class,
                'view',
                'record',
                fn (): array => [self::withPermission('user.manage'), [self::someoneElse()]],
                fn (): array => [self::nobody(), [self::someoneElse()]],
            ),

            new PolicyCase(
                UserPolicy::class,
                'create',
                'class',
                fn (): array => [self::withPermission('user.manage'), []],
                fn (): array => [self::nobody(), []],
            ),

            new PolicyCase(
                UserPolicy::class,
                'updateAny',
                'class',
                // TRUE for every authenticated user: `updateAny()` delegates to
                // `update($user, $user)` and `isSelf` matches. Pinned because the
                // reference-data routes rely on the surrounding `admin` middleware
                // for their real gate — if this ever changes, those routes change
                // with it, which is exactly when somebody needs to know.
                fn (): array => [self::nobody(), []],
                fn (): array => [null, []],
                'Always allowed for an authenticated user: it delegates to self-update. '
                .'The admin reference-data routes depend on the group middleware for their real gate.',
            ),

            new PolicyCase(
                UserPolicy::class,
                'deleteAny',
                'class',
                fn (): array => [self::withPermission('user.manage'), []],
                fn (): array => [self::nobody(), []],
            ),

            new PolicyCase(
                UserPolicy::class,
                'manageRolesAny',
                'class',
                fn (): array => [self::withPermission('user.manage'), []],
                fn (): array => [self::nobody(), []],
            ),

            new PolicyCase(
                UserPolicy::class,
                'update',
                'record',
                self::oneself(...),
                fn (): array => [self::nobody(), [self::someoneElse()]],
                'A user edits their own account without holding any permission.',
            ),

            new PolicyCase(
                UserPolicy::class,
                'update',
                'record',
                fn (): array => [self::withPermission('user.manage'), [self::someoneElse()]],
                fn (): array => [self::nobody(), [self::someoneElse()]],
            ),

            new PolicyCase(
                UserPolicy::class,
                'delete',
                'record',
                fn (): array => [self::withPermission('user.manage'), [self::someoneElse()]],
                self::oneself(...),
                // Self-service is refused: there is no self-delete, and an `update`
                // that allows self-service does not imply one.
                'There is no self-delete.',
            ),

            new PolicyCase(
                UserPolicy::class,
                'manageRoles',
                'record',
                fn (): array => [self::withPermission('user.manage'), [self::someoneElse()]],
                self::oneself(...),
                'Role assignment is never self-service, even on your own account.',
            ),
        ];
    }

    /**
     * @return list<PolicyCase>
     */
    private static function roleCases(): array
    {
        return [
            new PolicyCase(
                RolePolicy::class,
                'viewAny',
                'class',
                fn (): array => [self::withPermission('role.manage'), []],
                fn (): array => [self::nobody(), []],
                'Role administration is behind a permission, not merely a session.',
            ),

            new PolicyCase(
                RolePolicy::class,
                'viewAny',
                'class',
                fn (): array => [self::withPermission('privilege.manage'), []],
                fn (): array => [self::nobody(), []],
                'A second permission string grants the same surface.',
            ),

            new PolicyCase(
                RolePolicy::class,
                'view',
                'record',
                fn (): array => [self::withPermission('role.manage'), [RoleFactory::new()->create()]],
                fn (): array => [self::nobody(), [RoleFactory::new()->create()]],
            ),

            new PolicyCase(
                RolePolicy::class,
                'create',
                'class',
                fn (): array => [self::withPermission('role.manage'), []],
                fn (): array => [self::nobody(), []],
            ),

            new PolicyCase(
                RolePolicy::class,
                'update',
                'record',
                fn (): array => [self::withPermission('role.manage'), [RoleFactory::new()->create()]],
                fn (): array => [self::nobody(), [RoleFactory::new()->create()]],
            ),

            new PolicyCase(
                RolePolicy::class,
                'delete',
                'record',
                fn (): array => [self::withPermission('role.manage'), [RoleFactory::new()->create()]],
                fn (): array => [self::nobody(), [RoleFactory::new()->create()]],
            ),

            new PolicyCase(
                RolePolicy::class,
                'managePermissions',
                'record',
                fn (): array => [self::withPermission('role.manage'), [RoleFactory::new()->create()]],
                fn (): array => [self::nobody(), [RoleFactory::new()->create()]],
                'Editing a role\'s permissions is the highest-privilege write in the app.',
            ),
        ];
    }

    /**
     * @return list<PolicyCase>
     */
    private static function todoCases(): array
    {
        return [
            new PolicyCase(
                TodoPolicy::class,
                'viewAny',
                'class',
                fn (): array => [self::nobody(), []],
                fn (): array => [null, []],
                'Always allowed for an authenticated user; `TodoScope` filters, mirroring `view`.',
            ),

            new PolicyCase(
                TodoPolicy::class,
                'create',
                'class',
                fn (): array => [self::withPermission('todos.create'), []],
                fn (): array => [self::nobody(), []],
            ),

            new PolicyCase(
                TodoPolicy::class,
                'createForOthers',
                'class',
                fn (): array => [self::withPermission('todos.create_for_others'), []],
                fn (): array => [self::withPermission('todos.create'), []],
                // Delegating to somebody else is a DIFFERENT permission from creating
                // a To-Do at all. Sharing them would let any author assign work.
                '`todos.create` alone is refused: delegating is separate.',
            ),

            new PolicyCase(
                TodoPolicy::class,
                'view',
                'record',
                self::ownerOfTodo(...),
                self::strangerToTodo(...),
            ),

            new PolicyCase(
                TodoPolicy::class,
                'view',
                'record',
                fn (): array => [self::withPermission('todos.view_all'), [self::anyTodo()]],
                fn (): array => [self::nobody(), [self::anyTodo()]],
                '`todos.view_all` widens visibility without granting authorship.',
            ),

            new PolicyCase(
                TodoPolicy::class,
                'update',
                'record',
                // The creator may always edit their own To-Do, with no permission at
                // all. That asymmetry is deliberate: authorship is the grant.
                self::ownerOfTodo(...),
                fn (): array => [self::withPermission('todos.view_all'), [self::anyTodo()]],
                'Viewing everything does not confer the right to edit it.',
            ),

            new PolicyCase(
                TodoPolicy::class,
                'update',
                'record',
                // `update_any` is deliberately ANDed with `view`: the authority to
                // edit somebody else's work does not also make it visible.
                fn (): array => [self::withPermission('todos.update_any', 'todos.view_all'), [self::anyTodo()]],
                fn (): array => [self::nobody(), [self::anyTodo()]],
            ),

            new PolicyCase(
                TodoPolicy::class,
                'delete',
                'record',
                // `delete` requires `todos.delete` even for the creator, so the
                // creator row carries the permission and the note.
                fn (): array => self::creatorWithPermission('todos.delete'),
                fn (): array => [self::withPermission('todos.delete'), [self::anyTodo()]],
                'The permission alone is refused for another user\'s To-Do.',
            ),

            new PolicyCase(
                TodoPolicy::class,
                'delete',
                'record',
                fn (): array => [self::withPermission('todos.update_any', 'todos.delete'), [self::anyTodo()]],
                fn (): array => [self::withPermission('todos.delete'), [self::anyTodo()]],
                '`update_any` substitutes for authorship, but never for the permission.',
            ),

            new PolicyCase(
                TodoPolicy::class,
                'delete',
                'record',
                fn (): array => self::creatorWithPermission('todos.delete'),
                self::ownerOfTodo(...),
                // `delete` gates on `todos.delete` FIRST, unlike `update` where
                // authorship alone suffices. So a creator with no permission is
                // refused on their own To-Do — the asymmetry is deliberate.
                'The creator is refused without `todos.delete`: authorship is not enough to delete.',
            ),

            new PolicyCase(
                TodoPolicy::class,
                'assign',
                'record',
                fn (): array => self::creatorWithPermission('todos.assign'),
                fn (): array => [self::nobody(), [self::anyTodo()]],
                'Assigning is permission-gated even for the creator.',
            ),

            new PolicyCase(
                TodoPolicy::class,
                'assign',
                'record',
                fn (): array => self::creatorWithPermission('todos.assign'),
                fn (): array => [self::withPermission('todos.assign'), [self::anyTodo()]],
                'The creator needs the permission as well as the authorship.',
            ),

            new PolicyCase(
                TodoPolicy::class,
                'assign',
                'record',
                fn (): array => [self::withPermission('todos.assign', 'todos.update_any'), [self::anyTodo()]],
                fn (): array => [self::withPermission('todos.assign'), [self::anyTodo()]],
                '`todos.assign` alone is refused for another user\'s To-Do.',
            ),

            new PolicyCase(
                TodoPolicy::class,
                'restore',
                'record',
                fn (): array => self::creatorWithPermission('todos.restore'),
                self::ownerOfTodo(...),
                // A separate permission from `update`: unarchiving is deliberately
                // not something every editor may do.
                'Ownership alone is refused: restoring is its own permission.',
            ),

            new PolicyCase(
                TodoPolicy::class,
                'restore',
                'record',
                fn (): array => [self::withPermission('todos.restore', 'todos.update_any'), [self::anyTodo()]],
                fn (): array => [self::withPermission('todos.restore'), [self::anyTodo()]],
                '`update_any` substitutes for authorship, not for the permission.',
            ),

            new PolicyCase(
                TodoPolicy::class,
                'comment',
                'record',
                fn (): array => self::creatorWithPermission('todos.comment'),
                fn (): array => [self::withPermission('todos.comment'), [self::anyTodo()]],
                'The permission alone does not grant access to somebody else\'s To-Do.',
            ),

            new PolicyCase(
                TodoPolicy::class,
                'comment',
                'record',
                fn (): array => [self::withPermission('todos.comment', 'todos.view_all'), [self::anyTodo()]],
                fn (): array => [self::nobody(), [self::anyTodo()]],
            ),

            new PolicyCase(
                TodoPolicy::class,
                'complete',
                'record',
                self::ownerOfTodo(...),
                self::strangerToTodo(...),
                'Completing inherits `update`; it is not a separate grant.',
            ),

            new PolicyCase(
                TodoPolicy::class,
                'reopen',
                'record',
                self::ownerOfTodo(...),
                self::strangerToTodo(...),
            ),

            new PolicyCase(
                TodoPolicy::class,
                'archive',
                'record',
                self::ownerOfTodo(...),
                self::strangerToTodo(...),
            ),

            new PolicyCase(
                TodoPolicy::class,
                'manageRecurrence',
                'record',
                fn (): array => [self::withPermission('todos.manage_recurrence', 'todos.view_all'), [self::anyTodo()]],
                fn (): array => [self::withPermission('todos.manage_recurrence'), [self::anyTodo()]],
                'Changing the recurrence rule needs its own permission AND visibility.',
            ),

            new PolicyCase(
                TodoPolicy::class,
                'manageRecurrence',
                'record',
                fn (): array => self::creatorWithPermission('todos.manage_recurrence'),
                self::ownerOfTodo(...),
                // Ownership of a recurring To-Do is not authority to change its rule.
                'Owning a To-Do does not grant authority over its recurrence.',
            ),
        ];
    }

    // ---- Fixtures -----------------------------------------------------------
    //
    // Each of these builds the actor and the subject INSIDE one call, because they
    // are a single fact. A user who "owns" a task the same closure did not create is
    // a description the database does not contain.

    public static function nobody(): User
    {
        return UserFactory::new()->create();
    }

    /**
     * @param  list<string>  $permissions
     */
    public static function withPermission(string ...$permissions): User
    {
        $user = self::nobody();

        $role = Role::query()->create([
            'name' => 'Matrix role',
            'slug' => 'matrix-'.implode('-', array_map(
                fn (string $permission): string => str_replace('.', '-', $permission),
                $permissions,
            )).'-'.substr(md5(implode(',', $permissions)), 0, 6).'-'.substr(md5(uniqid('', true)), 0, 4),
        ]);

        foreach ($permissions as $permission) {
            $model = Permission::query()->firstOrCreate(['permission_name' => $permission]);

            RolePermission::query()->firstOrCreate([
                'role_id' => $role->id,
                'permission_id' => $model->id,
            ]);
        }

        $user->roles()->attach($role->id);
        $user->forgetPermissionCache();

        return $user->fresh();
    }

    public static function anyTask(): Task
    {
        return TaskFactory::new()->create();
    }

    /**
     * @return array{0: User, 1: list<Task>}
     */
    public static function ownerOfTask(): array
    {
        $owner = self::nobody();

        return [$owner, [TaskFactory::new()->create(['user_id' => $owner->id])]];
    }

    /**
     * The creator of a To-Do, holding a permission.
     *
     * Several To-Do abilities gate on a permission BEFORE checking authorship, so
     * "the creator" and "the creator with `todos.delete`" are different actors and
     * the matrix needs both.
     *
     * @return array{0: User, 1: list<Todo>}
     */
    public static function creatorWithPermission(string $permission): array
    {
        $creator = self::withPermission($permission);

        return [$creator, [Todo::factory()->createdBy($creator)->create()]];
    }

    /**
     * @return array{0: User, 1: list<Task>}
     */
    public static function strangerToTask(): array
    {
        return [self::nobody(), [TaskFactory::new()->create()]];
    }

    /**
     * @return array{0: User, 1: list<Task>}
     */
    public static function watcherOfTask(): array
    {
        $watcher = self::nobody();

        $task = TaskFactory::new()->create();

        TaskWatcher::factory()->create(['task_id' => $task->id, 'user_id' => $watcher->id]);

        return [$watcher, [$task]];
    }

    public static function anyMeeting(): Meeting
    {
        return MeetingFactory::new()->create();
    }

    /**
     * @return array{0: User, 1: list<Meeting>}
     */
    public static function organiserOfMeeting(): array
    {
        $organiser = self::nobody();

        return [$organiser, [MeetingFactory::new()->organisedBy($organiser)->create()]];
    }

    /**
     * @return array{0: User, 1: list<Meeting>}
     */
    public static function participantInMeeting(): array
    {
        $participant = self::nobody();

        $meeting = MeetingFactory::new()->organisedBy(self::nobody())->create();

        $meeting->participants()->create([
            'user_id' => $participant->id,
            'participant_type' => 'member',
        ]);

        return [$participant, [$meeting]];
    }

    /**
     * @return array{0: User, 1: list<Meeting>}
     */
    public static function strangerToMeeting(): array
    {
        $meeting = MeetingFactory::new()->organisedBy(self::nobody())->create();

        $stranger = self::nobody();

        return [$stranger, [$meeting]];
    }

    public static function anyActionItem(): MeetingActionItem
    {
        return MeetingActionItem::factory()->create();
    }

    /**
     * @return array{0: User, 1: list<MeetingActionItem>}
     */
    public static function organiserOfActionItem(): array
    {
        $organiser = self::nobody();

        $item = MeetingActionItem::factory()->create([
            'meeting_id' => MeetingFactory::new()->organisedBy($organiser)->create()->id,
            'assigned_to' => null,
        ]);

        return [$organiser, [$item]];
    }

    /**
     * @return array{0: User, 1: list<MeetingActionItem>}
     */
    public static function assigneeOfActionItem(): array
    {
        $assignee = self::nobody();

        $item = MeetingActionItem::factory()->create([
            'meeting_id' => MeetingFactory::new()->organisedBy(self::nobody())->create()->id,
            'assigned_to' => $assignee->id,
        ]);

        return [$assignee, [$item]];
    }

    /**
     * @return array{0: User, 1: list<MeetingActionItem>}
     */
    public static function strangerToActionItem(): array
    {
        $item = MeetingActionItem::factory()->create([
            'meeting_id' => MeetingFactory::new()->organisedBy(self::nobody())->create()->id,
            'assigned_to' => null,
        ]);

        return [self::nobody(), [$item]];
    }

    public static function anyObligation(): Obligation
    {
        return ObligationFactory::new()->create();
    }

    /**
     * @return array{0: User, 1: list<Obligation>}
     */
    public static function ownerOfObligation(): array
    {
        $owner = self::nobody();

        return [$owner, [ObligationFactory::new()->create(['owner_user_id' => $owner->id])]];
    }

    /**
     * @return array{0: User, 1: list<Obligation>}
     */
    public static function strangerToObligation(): array
    {
        $obligation = ObligationFactory::new()->create(['owner_user_id' => self::nobody()->id]);

        return [self::nobody(), [$obligation]];
    }

    /**
     * @return array{0: User, 1: list<Obligation>}
     */
    public static function activeResponsibleForObligation(): array
    {
        $responsible = self::nobody();

        $obligation = ObligationFactory::new()->create(['owner_user_id' => self::nobody()->id]);

        ObligationResponsibility::factory()->create([
            'obligation_id' => $obligation->id,
            'user_id' => $responsible->id,
        ]);

        return [$responsible, [$obligation]];
    }

    /**
     * @return array{0: User, 1: list<Obligation>}
     */
    public static function dischargedResponsibleForObligation(): array
    {
        $former = self::nobody();

        $obligation = ObligationFactory::new()->create(['owner_user_id' => self::nobody()->id]);

        ObligationResponsibility::factory()->discharged()->create([
            'obligation_id' => $obligation->id,
            'user_id' => $former->id,
        ]);

        return [$former, [$obligation]];
    }

    /**
     * @return array{0: User, 1: list<Obligation>}
     */
    public static function namedApproverOfObligation(): array
    {
        $approver = self::nobody();

        $obligation = ObligationFactory::new()->create([
            'owner_user_id' => self::nobody()->id,
            'approver_user_id' => $approver->id,
        ]);

        return [$approver, [$obligation]];
    }

    public static function anyProject(): Project
    {
        return ProjectFactory::new()->create();
    }

    /**
     * @return array{0: User, 1: list<Project>}
     */
    public static function ownerOfProject(): array
    {
        $owner = self::nobody();

        return [$owner, [ProjectFactory::new()->create(['user_id' => $owner->id])]];
    }

    /**
     * @return array{0: User, 1: list<Project>}
     */
    public static function strangerToProject(): array
    {
        $owner = self::nobody();

        return [self::nobody(), [ProjectFactory::new()->create(['user_id' => $owner->id])]];
    }

    /**
     * @return array{0: User, 1: list<User>}
     */
    public static function oneself(): array
    {
        $user = self::nobody();

        return [$user, [$user]];
    }

    public static function someoneElse(): User
    {
        return self::nobody();
    }

    public static function anyTodo(): Todo
    {
        return Todo::factory()->create();
    }

    /**
     * @return array{0: User, 1: list<Todo>}
     */
    public static function ownerOfTodo(): array
    {
        $owner = self::nobody();

        return [$owner, [Todo::factory()->createdBy($owner)->create()]];
    }

    /**
     * @return array{0: User, 1: list<Todo>}
     */
    public static function strangerToTodo(): array
    {
        return [self::nobody(), [Todo::factory()->create()]];
    }
}
