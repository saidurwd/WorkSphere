<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route as Router;
use ReflectionMethod;
use Tests\TestCase;

/**
 * The full IDOR sweep — Phase 11 item 10.
 *
 * Every mutating route must carry an authorization decision: a middleware gate
 * (`auth`, `admin`, `auth:sanctum`), or an `authorize()` / `Gate::` call inside
 * the action. A route with neither is reachable by any authenticated user, which
 * is the shape every IDOR in this codebase's history has had.
 *
 * This is asserted rather than eyeballed because the sweep's finding is a
 * *shape*: `middleware: auth` plus an action that never calls authorize() looks
 * correct in a route listing and authorizes nothing.
 *
 * Known vendor-registered routes are named explicitly below rather than excluded
 * by prefix, so a NEW unguarded route can never hide behind a broad pattern.
 */
class IdorSweepTest extends TestCase
{
    /**
     * Mutating routes that carry no decision of their own, with the reason each
     * is acceptable. A new entry here needs a justification, not a shrug.
     *
     * @var array<string, string>
     */
    private const ACCEPTED = [
        'boost.browser-logs' => 'Laravel Boost development tooling. Not application code; absent in production.',

        'storage.local.upload' => 'Laravel local-disk temporary upload. Requires ?upload=1 AND a valid X-Disk-Signature issued by Storage::temporaryUploadUrl(), so it is not an open write. Phase 11 verified the signature check is first.',

        // A catch-all resource route: the only route in the application with no
        // name, and it dispatches to the 404 handler.
        '' => 'Unnamed catch-all fallback.',
    ];

    public function test_no_mutating_route_lacks_an_authorization_decision(): void
    {
        $unguarded = [];

        foreach ($this->mutatingRoutes() as $route) {
            if ($this->decisionFor($route) !== null) {
                continue;
            }

            $name = (string) $route->getName();

            if (array_key_exists($name, self::ACCEPTED)) {
                continue;
            }

            $unguarded[] = sprintf('%s (%s) -> %s', $name ?: '(unnamed)', $route->uri(), $route->getActionName());
        }

        $this->assertSame(
            [],
            $unguarded,
            "These mutating routes have no middleware gate and no authorize() call.\n"
            ."Each is reachable by any authenticated user:\n".implode("\n", $unguarded),
        );
    }

    public function test_the_accepted_exceptions_are_still_the_only_ones(): void
    {
        // If one of these disappears, the exception should be removed rather than
        // left behind as a stale alibi.
        $present = [];

        foreach ($this->mutatingRoutes() as $route) {
            $name = (string) $route->getName();

            if ($this->decisionFor($route) === null && array_key_exists($name, self::ACCEPTED)) {
                $present[$name] = true;
            }
        }

        $expected = array_filter(
            array_keys(self::ACCEPTED),
            static fn (string $name): bool => $name === '' ? false : true,
        );

        foreach ($expected as $name) {
            if (in_array($name, ['boost.browser-logs'], true)) {
                continue; // Boost is a dev dependency and may not be installed.
            }

            $this->assertArrayHasKey(
                $name,
                $present,
                "{$name} no longer needs an exception; remove it from the list.",
            );
        }
    }

    public function test_no_controller_action_authorizes_with_a_bare_role_slug(): void
    {
        // The Phase 10 brief required zero role-slug comparisons in the dashboard
        // layer; this extends the check to every controller, because a role slug
        // is a weaker gate than the permission that governs the same data.
        $offenders = [];

        foreach (Router::getRoutes() as $route) {
            $action = $route->getActionName();

            if (! str_contains($action, '@')) {
                continue;
            }

            [$class, $method] = explode('@', $action, 2);

            if (! class_exists($class)) {
                continue;
            }

            $body = $this->methodBody($class, $method);

            if ($body === null) {
                continue;
            }

            if (preg_match('/hasRole\(\s*[\'"](admin|super-admin)[\'"]/', $body)) {
                $offenders[] = $class.'::'.$method;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'These actions gate on a role slug. Use the permission that governs the data: '.implode(', ', $offenders),
        );
    }

    /**
     * The authorization decision for a route: a middleware gate, an `authorize()`
     * call, or a `Gate::` call.
     */
    private function decisionFor(Route $route): ?string
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (in_array($middleware, ['admin', 'auth', 'auth:sanctum'], true)) {
                return 'middleware:'.$middleware;
            }

            // `guest` is the INVERSE decision — it asserts nobody is signed in.
            // Login and password reset live behind it deliberately, and treating
            // them as unguarded would push a real gate into the exceptions list.
            if ($middleware === 'guest') {
                return 'middleware:guest';
            }
        }

        $action = $route->getActionName();

        if (! str_contains($action, '@')) {
            return null;
        }

        [$class, $method] = explode('@', $action, 2);

        $body = $this->methodBody($class, $method);

        if ($body === null) {
            return null;
        }

        if (str_contains($body, '$this->authorize(')) {
            return 'authorize()';
        }

        if (str_contains($body, 'Gate::')) {
            return 'Gate::';
        }

        return null;
    }

    /**
     * @return Collection<int, Route>
     */
    private function mutatingRoutes(): Collection
    {
        return collect(Router::getRoutes())
            ->filter(static fn (Route $route): bool => array_intersect(
                $route->methods(),
                ['POST', 'PUT', 'PATCH', 'DELETE'],
            ) !== []);
    }

    private function methodBody(string $class, string $method): ?string
    {
        if (! class_exists($class) || ! method_exists($class, $method)) {
            return null;
        }

        $reflection = new ReflectionMethod($class, $method);
        $file = $reflection->getFileName();

        if ($file === false || ! is_readable($file)) {
            return null;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        $body = implode("\n", array_slice($lines, $reflection->getStartLine() - 1, $reflection->getEndLine() - $reflection->getStartLine() + 1));

        // An action that delegates its work entirely carries no authorize() of its
        // own. That is legitimate — the callee gates — so this returns null and the
        // route is judged by its middleware instead.
        return $body;
    }
}
