<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Phase 2 moved no routes, renamed nothing and changed no middleware. This
 * freezes the name -> uri -> middleware map so a future route edit has to be a
 * deliberate, visible change rather than an accidental one.
 *
 * Regenerate the fixture with:
 *   php artisan route:list --json | php -r '...'
 * after an INTENDED route change, and record the reason in the commit message.
 */
class RouteParityTest extends TestCase
{
    public function test_the_route_map_matches_the_recorded_baseline(): void
    {
        $baseline = $this->baseline();
        $current = $this->currentRouteMap();

        $missing = array_values(array_diff($baseline, $current));
        $added = array_values(array_diff($current, $baseline));

        $this->assertSame([], $missing, 'Routes present in the baseline are missing or changed.');
        $this->assertSame([], $added, 'Routes were added or changed without updating the baseline.');
    }

    public function test_no_route_is_defined_twice_for_the_same_method_and_uri(): void
    {
        $seen = [];
        $duplicates = [];

        foreach ($this->currentRouteMap() as $entry) {
            [$method, $uri] = explode('|', $entry, 3);

            $key = $method.'|'.$uri;

            if (isset($seen[$key])) {
                $duplicates[] = $key;
            }

            $seen[$key] = true;
        }

        $this->assertSame([], $duplicates, 'A method/uri pair is registered more than once.');
    }

    public function test_every_named_route_still_resolves_to_a_url(): void
    {
        $unnamed = [];

        foreach (Route::getRoutes() as $route) {
            if ($route->getName() === null) {
                continue;
            }

            // A named route with no parameters must still generate a URL.
            if ($route->parameterNames() === [] && ! str_contains($route->uri(), '{')) {
                try {
                    route($route->getName());
                } catch (\Throwable $e) {
                    $unnamed[] = $route->getName().': '.$e->getMessage();
                }
            }
        }

        $this->assertSame([], $unnamed);
    }

    /**
     * @return list<string>
     */
    private function baseline(): array
    {
        $path = __DIR__.'/../Fixtures/routes-baseline.json';

        $this->assertFileExists($path, 'The route baseline fixture is missing.');

        $contents = json_decode((string) file_get_contents($path), true);

        $this->assertIsArray($contents, 'The route baseline fixture is not valid JSON.');

        return $contents;
    }

    /**
     * @return list<string>
     */
    private function currentRouteMap(): array
    {
        $entries = [];

        foreach (Route::getRoutes() as $route) {
            // Boost registers its own tooling routes, and only outside the testing
            // environment. They are not application routes, so they are excluded
            // from both sides of the comparison.
            if (str_starts_with((string) $route->getName(), 'boost.')) {
                continue;
            }

            $entries[] = implode('|', [
                $route->methods()[0] ?? '',
                $route->uri(),
                $route->getName() ?? '',
                $this->middlewareSignature($route->gatherMiddleware()),
            ]);
        }

        sort($entries);

        return $entries;
    }

    /**
     * `gatherMiddleware()` returns class names with escaped namespace separators,
     * which is not the form recorded in the fixture.
     *
     * @param  array<int, string>  $middleware
     */
    private function middlewareSignature(array $middleware): string
    {
        $normalised = array_map(
            fn (string $name): string => str_replace('\\\\', '\\', ltrim($name, '\\')),
            $middleware,
        );

        sort($normalised);

        return implode(',', $normalised);
    }
}
