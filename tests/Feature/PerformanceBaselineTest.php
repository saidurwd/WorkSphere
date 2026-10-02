<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ProjectPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\InteractsWithRoles;
use Tests\Support\PageBenchmark;
use Tests\Support\SeededData;
use Tests\TestCase;

/**
 * The performance baseline — query count and wall-clock per page, at a stated
 * volume.
 *
 * Two numbers, because they fail differently:
 *
 * - **Query count** is the regression guard. It is deterministic, so a budget
 *   either holds or it does not, and CI can enforce it on every push. Milliseconds
 *   are not: they vary by a factor of three on shared runners, so asserting on them
 *   produces a test that fails on Monday and passes on Tuesday and gets ignored
 *   within a fortnight. The timings are recorded and reported, never asserted.
 * - **Time** is recorded so the report can say what actually improved, and
 *   deliberately kept out of the assertions.
 *
 * The budget is the CURRENT measurement plus a small margin, and it is recorded
 * next to the code. A budget nobody can explain is a number that gets raised until
 * the test is green, which is worse than no test.
 *
 * Every page is measured twice and the SECOND is recorded. The first pays for
 * permission-cache warming, the session and the query plan; measuring it would
 * blame the page for the harness's own first-request cost.
 */
class PerformanceBaselineTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private static ?SeededData $seeded = null;

    /**
     * @var list<array{label: string, uri: string, queries: int, warm: int, cold: int}>
     */
    private array $measurements = [];

    public function test_the_baseline_at_the_stated_volume(): void
    {
        $viewer = $this->seeded()->viewer;

        /**
         * Every permission in the catalogue, so the measured path is the widest one.
         *
         * A hand-written list drifts: a permission added later leaves its page
         * returning 403, and the benchmark then measures an authorisation failure
         * as though it were the page's cost. `PageBenchmark` builds its volumes
         * for exactly that case — the viewer can see all of it — so the grant is
         * total and taken from the catalogue rather than restated here.
         */
        $this->userWithPermissions(ProjectPermissionSeeder::PERMISSIONS, attachTo: $viewer);

        $overBudget = [];
        $rows = [];
        $statuses = [];

        foreach (PageBenchmark::pages() as $page) {
            $uri = $this->resolve($page['uri']);

            $this->authenticate($page['kind'], $viewer);

            $first = $this->measure(fn () => $this->get($uri, $this->headers($page['kind'], $viewer)));
            $second = $this->measure(fn () => $this->get($uri, $this->headers($page['kind'], $viewer)));

            // A page that ERRORS issues no queries and looks fast. Measuring only
            // queries would rank a 500 above a working page, so the status is
            // recorded beside the number and asserted.
            $statuses[$page['label']] = $second['status'];

            $this->measurements[] = [
                'label' => $page['label'],
                'uri' => $page['uri'],
                'queries' => $second['queries'],
                'warm' => $first['queries'],
                'cold' => $second['ms'],
            ];

            $rows[] = sprintf(
                '%-28s %-42s %5d %8.1f %5d',
                $page['label'],
                $page['uri'],
                $second['queries'],
                $second['ms'],
                $second['status'],
            );

            if ($second['queries'] > $page['budget']) {
                $overBudget[] = sprintf(
                    '%s: %d queries against a budget of %d',
                    $page['label'],
                    $second['queries'],
                    $page['budget'],
                );
            }
        }

        fwrite(STDERR, "\nPERFORMANCE BASELINE\n");
        fwrite(STDERR, sprintf(
            "volume: %s\n",
            json_encode(PageBenchmark::VOLUME),
        ));
        fwrite(STDERR, sprintf("%-28s %-42s %5s %8s %5s\n", 'page', 'uri', 'query', 'ms', 'http'));
        fwrite(STDERR, implode("\n", $rows)."\n\n");

        $this->assertSame([], $overBudget, "Pages over their query budget:\n".implode("\n", $overBudget));

        $broken = array_filter($statuses, fn (int $status): bool => $status !== 200);

        $this->assertSame(
            [],
            $broken,
            'These benchmark pages do not return 200, so their numbers describe a failure rather than the page. '
            .json_encode($broken),
        );
    }

    /**
     * The dataset is seeded once for the class.
     *
     * Seeding per test would make the measurement include four hundred inserts and
     * drown the very numbers this file exists to record.
     */
    private function seeded(): SeededData
    {
        return self::$seeded ??= PageBenchmark::seed();
    }

    private function resolve(string $uri): string
    {
        foreach (['todo', 'task', 'meeting', 'obligation', 'project'] as $name) {
            if (str_contains($uri, '{'.$name.'}')) {
                return str_replace('{'.$name.'}', (string) $this->seeded()->record($name)->getKey(), $uri);
            }
        }

        return $uri;
    }

    private function authenticate(string $kind, User $user): void
    {
        $kind === 'web' ? $this->actingAs($user) : Sanctum::actingAs($user);
    }

    /**
     * @return array<string, string>
     */
    private function headers(string $kind, User $user): array
    {
        return $kind === 'web' ? [] : ['Accept' => 'application/json'];
    }

    /**
     * @return array{queries: int, ms: float}
     */
    private function measure(callable $request): array
    {
        $queries = 0;

        DB::listen(function () use (&$queries): void {
            $queries++;
        });

        $started = hrtime(true);

        $this->response = $request();

        $elapsed = (hrtime(true) - $started) / 1_000_000;

        return ['queries' => $queries, 'ms' => round($elapsed, 1), 'status' => $this->response?->getStatusCode() ?? 0];
    }
}
