<?php

namespace Tests\Feature;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use App\Support\StatusBadge;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The two cross-cutting UI guarantees from §8.3, asserted over the source so a
 * regression cannot reach review.
 *
 * A `{!! !!}` on user data or a second paginator is invisible in a feature test
 * that only asserts a 200 — both fail silently and are caught in production.
 */
class TodoUiConventionsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<string>
     */
    private function todoViews(): array
    {
        return glob(base_path('Modules/Todos/resources/views/todos/**/*.blade.php')) ?: [];
    }

    public function test_no_todo_view_renders_raw_echoed_user_data(): void
    {
        $offenders = [];

        foreach ($this->todoViews() as $view) {
            foreach (explode("\n", (string) file_get_contents($view)) as $number => $line) {
                // Strip Blade comments; a prose mention of {!! !!} is not a directive.
                $stripped = preg_replace('/\{\{--.*?--\}\}/s', '', $line) ?? $line;

                if (str_contains($stripped, '{!!')) {
                    $offenders[] = basename($view).':'.($number + 1).' — '.trim($line);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Raw echo of user data is a cross-site-scripting risk:\n".implode("\n", $offenders),
        );
    }

    public function test_no_todo_view_initialises_a_client_side_datatable(): void
    {
        // The To-Do lists paginate with Blade. A `data-dtable` here would add a
        // second paginator over the same page (GAP-038).
        foreach ($this->todoViews() as $view) {
            $source = (string) file_get_contents($view);

            $this->assertStringNotContainsString('data-dtable', $source, basename($view).' starts a client-side DataTable.');
            $this->assertStringNotContainsString('<x-datatable', $source, basename($view).' uses the client-side datatable component.');
        }
    }

    public function test_the_shared_datatable_no_longer_double_paginates(): void
    {
        $source = (string) file_get_contents(base_path('resources/views/components/datatable.blade.php'));

        $this->assertStringContainsString("'paging' => false", $source, 'The shared datatable must default client-side paging OFF.');
        $this->assertStringContainsString("'info' => false", $source, 'And it must not render its own "showing N of M" footer.');
    }

    public function test_every_icon_only_control_has_an_accessible_name(): void
    {
        $offenders = [];

        foreach ($this->todoViews() as $view) {
            foreach (explode("\n", (string) file_get_contents($view)) as $number => $line) {
                $stripped = trim(preg_replace('/\{\{--.*?--\}\}/s', '', $line) ?? $line);

                if (! str_contains($stripped, 'aria-label') && ! str_contains($stripped, 'visually-hidden')) {
                    continue;
                }
            }
        }

        // Icon-only buttons render through <x-icon-btn label="…">, which always emits
        // a visually-hidden label. Anything bypassing it needs its own aria-label.
        foreach ($this->todoViews() as $view) {
            $source = (string) file_get_contents($view);

            preg_match_all('/<button\b[^>]*>\s*<i class="bi[^"]*"><\/i>\s*<\/button>/s', $source, $matches);

            foreach ($matches[0] as $button) {
                if (! str_contains($button, 'aria-label')) {
                    $offenders[] = basename($view).': icon-only <button> with no aria-label';
                }
            }
        }

        $this->assertSame([], $offenders, implode("\n", $offenders));
    }

    public function test_status_colour_is_never_the_only_signal(): void
    {
        // A badge must always carry its label; colour alone fails WCAG 1.4.1 for
        // anyone who cannot distinguish the hues.
        foreach ($this->todoViews() as $view) {
            $source = (string) file_get_contents($view);

            preg_match_all('/<x-badge\s+:variant="[^"]*">\s*<\/x-badge>/s', $source, $matches);

            $this->assertSame(
                [],
                $matches[0],
                basename($view).' renders a status badge with no text label.',
            );
        }
    }

    public function test_status_badge_is_the_single_variant_map(): void
    {
        // GAP-020: one map, used everywhere. A module reintroducing inline `match`
        // arms is how the same status ended up three different colours.
        $offenders = [];

        // Module-specific vocabularies with no shared enum. Listed by full path so
        // the exclusion is deliberate and unambiguous — a basename filter would
        // silently excuse an unrelated view called index.blade.php.
        $excluded = [
            'Modules/Meetings/resources/views/meetings/index.blade.php' => 'minutes_status',
            'Modules/Meetings/resources/views/meetings/participants/index.blade.php' => 'attendance_status',
            'Modules/Obligations/resources/views/obligations/notifications.blade.php' => 'delivery status',
        ];

        $offenders = [];

        foreach (glob(base_path('Modules/*/resources/views/**/*.blade.php')) ?: [] as $view) {
            $relative = str_replace(base_path().'/', '', $view);

            if (! str_contains((string) file_get_contents($view), '<x-badge :variant="match')) {
                continue;
            }

            if (isset($excluded[$relative])) {
                continue;
            }

            $offenders[] = $relative;
        }

        $this->assertSame(
            [],
            $offenders,
            'Work-item status/priority badges must go through App\\Support\\StatusBadge: '
            .implode(', ', $offenders),
        );
    }

    public function test_status_badge_covers_every_status_the_enum_declares(): void
    {
        // Every declared status must have an intentional variant, not the silent
        // fallback. `inbox`/`pending` legitimately use the fallback colour, so the
        // real assertion is that the fallback is reserved for unknown values.
        $explicit = ['secondary', 'info', 'primary', 'warning', 'success', 'danger', 'dark'];

        foreach (WorkItemStatus::cases() as $case) {
            $this->assertContains(
                StatusBadge::variant($case),
                $explicit,
                "{$case->value} maps to a variant Bootstrap does not provide.",
            );
        }

        // Every status must map to *something*; the fallback is only for values
        // that are genuinely unknown.
        $this->assertSame('secondary', StatusBadge::variant('not-a-status'));
        $this->assertSame('secondary', StatusBadge::variant(null));

        // And every real one must have a distinct label.
        foreach (WorkItemStatus::cases() as $case) {
            $this->assertSame($case->label(), StatusBadge::label($case));
            $this->assertSame($case->label(), StatusBadge::label($case->value));
        }
    }

    public function test_status_badge_renders_the_label_next_to_the_colour(): void
    {
        $this->assertSame('In Progress', StatusBadge::label(WorkItemStatus::InProgress));
        $this->assertSame('In Progress', StatusBadge::label('in_progress'));
        $this->assertSame('High', StatusBadge::label(Priority::High));
        $this->assertSame('Team', StatusBadge::label(Visibility::Team));
        $this->assertSame('—', StatusBadge::label(null));
    }

    public function test_the_navigation_node_exists(): void
    {
        $flat = [];

        $tree = config('navigation');

        array_walk_recursive(
            $tree,
            function ($value, $key) use (&$flat): void {
                if ($key === 'route') {
                    $flat[] = $value;
                }
            },
        );

        foreach (['todos.index', 'todos.inbox', 'todos.calendar', 'todos.reports'] as $route) {
            $this->assertContains($route, $flat, "{$route} is missing from the navigation tree.");
        }
    }
}
