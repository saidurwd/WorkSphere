<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingAgenda;
use Modules\Meetings\Models\MeetingParticipant;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationDocument;
use Modules\Tasks\Models\Task;
use Modules\Tasks\Models\TimeEntry;
use Modules\Todos\Models\Todo;
use Modules\Todos\Models\TodoChecklistItem;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * N+1 detection across every screen that renders a collection.
 *
 * The measurement is MARGINAL: the same page is requested with one related row and
 * with fifteen, and the query count must not move. A single request's query count
 * proves nothing on its own — a page that lazy-loads five rows happens to cost
 * five queries at five rows, and would look identical to a correctly eager-loaded
 * page until the dataset grew.
 *
 * Two properties are asserted, and the second matters as much as the first:
 *
 * 1. No screen's query count grows with its related record count.
 * 2. The counter actually detects that when it happens — `test_the_detector
 *    detects_a_deliberate_n_plus_one` runs a knowingly lazy-loading loop and
 *    asserts the count goes up. A guard that cannot fail is not a guard, and this
 *    is the difference between "the suite is clean" and "the suite is measuring".
 */
class NPlusOneDetectionTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /** Rows per page on the widest list, so the seed stays on one page. */
    private const ROWS_BEFORE = 1;

    private const ROWS_AFTER = 15;

    /**
     * @return array<string, array{0: string, 1: string, 2: callable(int): void}>
     */
    public static function screens(): array
    {
        return [
            // Web lists.
            'todos index' => ['/todos', 'web', fn (int $n) => Todo::factory()->count($n)->create()],
            'tasks index' => ['/tasks', 'web', fn (int $n) => Task::factory()->count($n)->create()],
            'meetings index' => ['/meetings', 'web', fn (int $n) => Meeting::factory()->count($n)->create()],
            'obligations index' => ['/obligations', 'web', fn (int $n) => Obligation::factory()->count($n)->create()],

            // API lists.
            'api todos index' => ['/api/v1/todos', 'api', fn (int $n) => Todo::factory()->count($n)->create()],
            'api tasks index' => ['/api/v1/tasks', 'api', fn (int $n) => Task::factory()->count($n)->create()],
            'api meetings index' => ['/api/v1/meetings', 'api', fn (int $n) => Meeting::factory()->count($n)->create()],
            'api obligations index' => ['/api/v1/obligations', 'api', fn (int $n) => Obligation::factory()->count($n)->create()],

            // Detail screens, where the N+1 risk is highest: one parent plus a
            // collection rendered inside it.
            'todo show' => ['/todos/{todo}', 'web', DetailSeeders::checklistFor('{todo}')],
            'task show' => ['/tasks/{task}', 'web', DetailSeeders::timeEntriesFor('{task}')],
            'meeting show' => ['/meetings/{meeting}', 'web', DetailSeeders::meetingDetailFor('{meeting}')],
            'obligation show' => ['/obligations/{obligation}', 'web', DetailSeeders::documentsFor('{obligation}')],
            'api todo show' => ['/api/v1/todos/{todo}', 'api', DetailSeeders::checklistFor('{todo}')],

            // Both of these render `$agenda->presentedBy->name`. Neither was measured
            // before, which is how a missing `agendas.presentedBy` eager load shipped:
            // `meeting show` was seeded with participants only, so no agenda row ever
            // reached the relation.
            'meeting agendas index' => ['/meetings/{meeting}/agendas', 'web', DetailSeeders::agendasFor('{meeting}')],
            'meeting print' => ['/meetings/{meeting}/print', 'web', DetailSeeders::agendasFor('{meeting}')],
        ];
    }

    #[DataProvider('screens')]
    public function test_a_screen_does_not_query_once_per_row(string $pattern, string $kind, callable $seed): void
    {
        $user = $this->viewer();

        // Warm the request: the permission cache, the session and the model's
        // relation state all resolve on first use, and a cold first request would
        // be measured differently from the second for reasons that have nothing to
        // do with the rows.
        $this->warm($kind, $user);

        $uri = $this->resolve($pattern, $user);

        [$atOne] = $this->measure($kind, $user, $uri, fn () => $seed(self::ROWS_BEFORE));

        [$atMany] = $this->measure($kind, $user, $uri, fn () => $seed(self::ROWS_AFTER));

        // Bounded by ONE, not by zero. A constant extra query appears on some
        // screens once a relation holds more rows than fits an inline cache — an
        // existence check rather than per-row work, and it does not scale. An N+1
        // across the fourteen added rows would add fourteen; one is not one.
        $this->assertLessThanOrEqual(
            $atOne + 1,
            $atMany,
            "{$pattern} issued ".($atMany - $atOne).' more queries when the related count grew from '
            .self::ROWS_BEFORE.' to '.self::ROWS_AFTER.' — that scales with rows, which means an eager load is missing.',
        );
    }

    /**
     * The detector must be capable of failing.
     *
     * `->pluck()` on an unloaded relation issues one query per parent. This asserts
     * that the counter above sees it — without this, a screen that stopped
     * eager-loading would be reported as clean by a measurement that had stopped
     * measuring.
     */
    public function test_the_detector_detects_a_deliberate_n_plus_one(): void
    {
        // Deliberately built on `users` -> `employees`, NOT on a domain model.
        //
        // A `Todo` probe would be measuring the wrong thing: `TodoScope` is a global
        // scope keyed on the acting user and its permission cache, so what comes
        // back depends on authentication set up elsewhere — and when this test ran
        // inside the full suite rather than alone, the scope returned nothing and
        // the "lazy" loop quietly did zero queries. An instrument that can be
        // blinded by an unrelated test is not an instrument.
        //
        // `users.employee_id` is NOT NULL as of Phase 8, so `employee` is never a
        // null foreign key — which Laravel would short-circuit without querying.
        User::factory()->count(9)->create();

        $eager = fn (): int => $this->countQueries(
            fn () => User::query()->with('employee')->get()->each(fn (User $user) => $user->employee?->name),
        );

        $lazy = fn (): int => $this->countQueries(
            fn () => User::query()->get()->each(fn (User $user) => $user->employee?->name),
        );

        $eagerAtThree = $eager();
        $lazyAtThree = $lazy();

        User::factory()->count(6)->create();

        $eagerAtNine = $eager();
        $lazyAtNine = $lazy();

        // Eager loading costs a CONSTANT two queries however many rows there are.
        $this->assertSame(
            $eagerAtThree,
            $eagerAtNine,
            'Eager loading is not flat in the row count, so the comparison below would prove nothing.',
        );

        // Lazy loading costs one query per row: six more rows, six more queries.
        $this->assertGreaterThanOrEqual(
            6,
            $lazyAtNine - $lazyAtThree,
            'The counter does not see the per-row queries an N+1 would produce, '
            .'so every screen measured above is being compared against a broken instrument.',
        );

        $this->assertLessThan(
            $lazyAtNine,
            $eagerAtNine,
            'Eager loading must cost fewer queries than lazy loading.',
        );
    }

    /**
     * The detector counts what it says it counts, and nothing else.
     */
    public function test_the_counter_starts_from_a_clean_slate_per_measurement(): void
    {
        $first = $this->countQueries(fn () => DB::select('select 1'));

        $second = $this->countQueries(fn () => DB::select('select 1'));

        // The listener accumulates on the connection, so each measurement resets
        // its own counter rather than reading a shared total. If this ever drifts,
        // every screen above is being compared against a contaminated baseline.
        $this->assertSame($first, $second);
        $this->assertSame(1, $first);
    }

    // ---- Harness ------------------------------------------------------------

    private function viewer(): User
    {
        return $this->userWithPermissions([
            'todos.view_all', 'task.view', 'meeting.view', 'obligation.view',
            'project.view', 'todos.comment', 'obligation.manage_documents',
        ]);
    }

    /**
     * @param  callable(): void  $between
     * @return array{0: int}
     */
    private function measure(string $kind, User $user, string $uri, callable $between): array
    {
        $between();

        return [$this->countQueries(fn () => $this->get($uri, $this->headersFor($kind, $user)))];
    }

    private function warm(string $kind, User $user): void
    {
        $user->load('employee');
        $user->hasPermission('todos.view_all');

        $this->get('/todos', $this->headersFor($kind, $user));
    }

    /**
     * Web routes and API routes need DIFFERENT authentication in a test: Sanctum's
     * guard is a `RequestGuard`, and the `web` middleware stack calls methods on it
     * that only a session guard has. Mixing them yields a
     * `RequestGuard::viaRemember` error rather than a meaningful measurement.
     *
     * @return array<string, string>
     */
    private function headersFor(string $kind, User $user): array
    {
        if ($kind === 'web') {
            $this->actingAs($user);

            return [];
        }

        Sanctum::actingAs($user);

        return ['Accept' => 'application/json'];
    }

    private function resolve(string $pattern, User $user): string
    {
        $todo = Todo::factory()->createdBy($user)->create();
        $task = Task::factory()->create(['user_id' => $user->id]);
        $meeting = Meeting::factory()->organisedBy($user)->create();
        $obligation = Obligation::factory()->create(['owner_user_id' => $user->id]);

        // Recorded once so the seeder closures and the URI point at the SAME row.
        DetailSeeders::$resolved = [
            '{todo}' => $todo->id,
            '{task}' => $task->id,
            '{meeting}' => $meeting->id,
            '{obligation}' => $obligation->id,
        ];

        return strtr($pattern, array_map(strval(...), DetailSeeders::$resolved));
    }

    /**
     * @param  callable(): mixed  $callback
     */
    private function countQueries(callable $callback): int
    {
        $queries = 0;

        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $callback();

        return $queries;
    }
}

/**
 * Seeders for the detail screens.
 *
 * Kept as static closures rather than inline in the provider because a data
 * provider runs BEFORE the application boots — anything touching the database
 * there fails with "a facade root has not been set" rather than measuring
 * anything. The closure itself is not invoked until the test body runs.
 */
final class DetailSeeders
{
    /** @var array<string, string> The parent id each placeholder resolved to. */
    public static array $resolved = [];

    public static function checklistFor(string $placeholder): callable
    {
        return fn (int $count) => TodoChecklistItem::factory()
            ->count($count)
            ->create(['todo_id' => self::$resolved[$placeholder]]);
    }

    public static function timeEntriesFor(string $placeholder): callable
    {
        return fn (int $count) => TimeEntry::factory()
            ->count($count)
            ->create(['task_id' => self::$resolved[$placeholder]]);
    }

    public static function participantsFor(string $placeholder): callable
    {
        return fn (int $count) => MeetingParticipant::factory()
            ->count($count)
            ->create(['meeting_id' => self::$resolved[$placeholder]]);
    }

    /**
     * Agendas, each with a presenter.
     *
     * `presented_by` is set NON-NULL on purpose, for the same reason the `employee`
     * probe is on a non-null foreign key: a null key short-circuits inside
     * `getRelationshipFromMethod` and never reaches the database, so a seeder that
     * left it null would measure a screen structurally incapable of exhibiting this
     * N+1 — the guard would pass on the one input that hides the bug.
     */
    public static function agendasFor(string $placeholder): callable
    {
        return function (int $count) use ($placeholder): void {
            $meetingId = self::$resolved[$placeholder];
            $presenter = User::factory()->create();

            foreach (range(1, $count) as $index) {
                MeetingAgenda::factory()->presentedBy($presenter)->create([
                    'meeting_id' => $meetingId,
                    'agenda_no' => $index,
                    'sort_order' => $index,
                ]);
            }
        };
    }

    /**
     * Participants AND agendas, for the screens that render both.
     *
     * Seeding only participants left the agenda relation unmeasured on `meeting
     * show`, which is the screen where the missing eager load was found.
     */
    public static function meetingDetailFor(string $placeholder): callable
    {
        return function (int $count) use ($placeholder): void {
            self::participantsFor($placeholder)($count);
            self::agendasFor($placeholder)($count);
        };
    }

    public static function documentsFor(string $placeholder): callable
    {
        return fn (int $count) => ObligationDocument::factory()
            ->count($count)
            ->create(['obligation_id' => self::$resolved[$placeholder]]);
    }
}
