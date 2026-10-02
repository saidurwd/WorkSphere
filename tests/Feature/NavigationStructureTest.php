<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\NavigationMenu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The sidebar is a contract, and these are the properties it has to keep.
 *
 * Each assertion here corresponds to something that was actually wrong in the
 * tree this replaced, because a navigation test that only checks "the page
 * renders" cannot tell a well-organised menu from a broken one — both return 200.
 *
 * The reachability assertion is the load-bearing one. It is what caught four
 * screens that had routes, controllers, views and tests, and no way to be found:
 * My Work, Task Reports, Meeting Templates and the identity Reference Data. Two
 * of those were built in Phase 8 and wired to nothing.
 */
class NavigationStructureTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /**
     * Screens deliberately NOT in the sidebar, with the reason.
     *
     * An excuse list is only honest if each entry says why. Without the reason it
     * is a place to hide the next omission.
     *
     * @var array<string, string>
     */
    private const EXCUSED = [
        // Reached from the navbar search box, which is where a global search
        // belongs; a second sidebar entry would duplicate it.
        'search.index' => 'Linked from the navbar search box.',
        // Reached from the navbar notification dropdown's "View All".
        'notifications.index' => 'Linked from the navbar notification dropdown.',

        // Rendered INSIDE the meeting detail page, which posts to these same
        // routes from inline forms. Separate pages would duplicate the content.
        'meetings.agendas.index' => 'Rendered inside the meeting detail page.',
        'meetings.participants.index' => 'Rendered inside the meeting detail page.',
        'meetings.decisions.index' => 'Rendered inside the meeting detail page.',
        'meetings.attachments.index' => 'Rendered inside the meeting detail page.',

        // Diagnostic screens an ordinary user has no reason to open.
        'dashboard.ui-kit' => 'Design-system reference, under Dashboard.',
        'todos.notification-logs.index' => 'Diagnostic; reachable from To-Do reports.',
    ];

    // ---- The tree is well-formed --------------------------------------------

    public function test_every_node_naming_a_route_names_a_route_that_exists(): void
    {
        $broken = [];

        $this->walk($this->configMenu(), function (array $node, int $depth) use (&$broken): void {
            $route = $node['route'] ?? null;

            if ($route === null) {
                return;
            }

            if (! Route::has($route)) {
                $broken[] = str_repeat('  ', $depth).($node['label'] ?? '?').' -> '.$route.' (no such route)';
            }
        });

        // A nav entry naming a renamed route throws while rendering the LAYOUT,
        // which takes every page down including the 404.
        $this->assertSame([], $broken, "The menu names routes that do not exist:\n".implode("\n", $broken));
    }

    public function test_a_branch_has_no_route_of_its_own(): void
    {
        $confusing = [];

        $this->walk($this->configMenu(), function (array $node) use (&$confusing): void {
            if (($node['children'] ?? []) !== [] && ! empty($node['route'])) {
                $confusing[] = $node['label'].' has both a route and children.';
            }
        });

        // A branch that is also a link is ambiguous: it looks clickable, and
        // clicking it goes somewhere else than expanding it.
        $this->assertSame([], $confusing);
    }

    public function test_no_leaf_repeats_its_parents_label(): void
    {
        $stutters = [];

        $this->walk($this->configMenu(), function (array $node, int $depth) use (&$stutters): void {
            $children = (array) ($node['children'] ?? []);

            if ($children === []) {
                return;
            }

            foreach ($children as $child) {
                if (strcasecmp((string) ($child['label'] ?? ''), (string) ($node['label'] ?? '')) === 0) {
                    $stutters[] = $node['label'].' > '.$child['label'];
                }
            }
        });

        // "Obligations > Obligations" reads as a stutter and tells the reader
        // nothing about which one is the list.
        $this->assertSame([], $stutters, 'These parent/child pairs share a label.');
    }

    public function test_no_node_uses_a_placeholder_icon(): void
    {
        $placeholders = [];

        $this->walk($this->configMenu(), function (array $node, int $depth) use (&$placeholders): void {
            $icon = $node['icon'] ?? null;

            // `circle` was the third-level filler in the previous tree: the absence
            // of an icon rather than a choice of one, and it made every leaf in a
            // group look identical.
            if ($icon === null || $icon === 'circle') {
                $placeholders[] = str_repeat('  ', $depth).($node['label'] ?? '?').' -> '.var_export($icon, true);
            }
        });

        $this->assertSame([], $placeholders, 'These nodes have no meaningful icon.');
    }

    /**
     * Every icon names a glyph that actually exists.
     *
     * An icon name that is not in Bootstrap Icons does not error, does not fall
     * back and does not warn: it renders an empty box. `id-badge` shipped that way
     * on the Employees item and looked like a layout bug rather than a typo.
     *
     * Checked against the INSTALLED `bootstrap-icons` stylesheet rather than a
     * hard-coded list, so an upgrade that renames a glyph is caught here instead of
     * in a screenshot.
     */
    public function test_every_icon_exists_in_the_installed_bootstrap_icons(): void
    {
        $stylesheet = base_path('node_modules/bootstrap-icons/font/bootstrap-icons.css');

        $this->assertFileExists(
            $stylesheet,
            'Bootstrap Icons is not installed, so the menu icons cannot be verified. '
            .'Run `npm install`.',
        );

        $available = $this->availableIcons($stylesheet);

        $unknown = [];

        $this->walk($this->configMenu(), function (array $node, int $depth) use ($available, &$unknown): void {
            $icon = (string) ($node['icon'] ?? '');

            if ($icon !== '' && ! in_array($icon, $available, true)) {
                $unknown[] = str_repeat('  ', $depth).($node['label'] ?? '?').' -> '.$icon;
            }
        });

        $this->assertSame(
            [],
            $unknown,
            "These icons are not in Bootstrap Icons, so they render as an empty box:\n".implode("\n", $unknown),
        );
    }

    /**
     * The glyph names the installed Bootstrap Icons stylesheet defines.
     *
     * Read from the stylesheet rather than a hard-coded list, so the set tracks
     * whatever `package.json` actually resolved to. The `bi-` prefix the config
     * omits is stripped here, once, so a comparison is like-for-like.
     *
     * @return list<string>
     */
    private function availableIcons(string $stylesheet): array
    {
        preg_match_all('/^\.(bi-[a-z0-9-]+)::before/m', (string) file_get_contents($stylesheet), $matches);

        return array_values(array_unique(array_map(
            static fn (string $icon): string => substr($icon, 3),
            $matches[1],
        )));
    }

    public function test_labels_are_unique_among_siblings(): void
    {
        $duplicates = [];

        foreach ($this->configMenu() as $node) {
            $this->checkSiblings($node, $duplicates);
        }

        $this->assertSame([], $duplicates);
    }

    // ---- Every screen is reachable ------------------------------------------

    public function test_every_screen_is_reachable_from_the_menu(): void
    {
        $inMenu = [];

        $this->walk($this->configMenu(), function (array $node) use (&$inMenu): void {
            if (! empty($node['route'])) {
                $inMenu[] = $node['route'];
            }
        });

        $missing = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();

            if ($name === null || ! str_ends_with($name, '.index')) {
                continue;
            }

            // The API has its own OpenAPI document and is not part of a sidebar.
            if (str_starts_with($name, 'api.')) {
                continue;
            }

            if (in_array($name, $inMenu, true) || isset(self::EXCUSED[$name])) {
                continue;
            }

            $missing[] = $name;
        }

        $this->assertSame(
            [],
            $missing,
            'These screens have a route and nothing links to them. Add them to config/navigation.php, '
            ."or add an EXCUSED entry saying why:\n".implode("\n", $missing),
        );
    }

    public function test_every_excused_screen_still_has_a_reason(): void
    {
        foreach (array_keys(self::EXCUSED) as $name) {
            $this->assertArrayHasKey($name, self::EXCUSED);
            $this->assertNotSame('', trim(self::EXCUSED[$name]), "{$name} is excused with no reason.");
        }

        // An excuse for a screen that has since been added to the menu is stale and
        // hides a future omission behind an entry that no longer applies.
        $inMenu = [];

        $this->walk($this->configMenu(), function (array $node) use (&$inMenu): void {
            if (! empty($node['route'])) {
                $inMenu[] = $node['route'];
            }
        });

        $stale = array_values(array_intersect(array_keys(self::EXCUSED), $inMenu));

        $this->assertSame([], $stale, 'These excuses are stale — the screen is in the menu now.');
    }

    // ---- Visibility ---------------------------------------------------------

    public function test_a_node_the_caller_cannot_use_is_hidden_not_greyed(): void
    {
        $labels = $this->labelsFor($this->userWithPermissions(['todos.view']));

        $this->assertContains('To-Dos', $labels);

        // A menu item leading to a 403 advertises a capability the account does not
        // have, which is worse than the item being absent.
        $this->assertNotContains('Meetings', $labels);
        $this->assertNotContains('Obligations', $labels);
        $this->assertNotContains('Administration', $labels);
    }

    public function test_administration_is_hidden_from_non_admins(): void
    {
        $labels = $this->labelsFor($this->userWithPermissions(['user.manage']));

        $this->assertNotContains('Administration', $labels);
    }

    public function test_a_branch_left_with_no_children_is_removed_entirely(): void
    {
        // `todos.reports` needs `report.view`, which this user lacks. The Reports
        // leaf disappears — and if Reports were a branch it would have to disappear
        // with it, rather than sitting there expandable and empty.
        $labels = $this->labelsFor($this->userWithPermissions(['todos.view']));

        $this->assertContains('Calendar', $labels);
        $this->assertNotContains('Reports', $labels);
    }

    public function test_the_menu_is_ordered_workspace_first(): void
    {
        $labels = $this->labelsFor($this->superAdmin([
            'todos.view', 'task.view', 'meeting.view', 'obligation.view', 'project.view',
            'report.view', 'user.manage',
        ]));

        $top = array_column(app(NavigationMenu::class)->forUser($this->freshSuperAdmin([
            'todos.view', 'task.view', 'meeting.view', 'obligation.view', 'project.view',
            'report.view', 'user.manage',
        ])), 'label');

        // "What is mine" before "what do I administer" is the whole point of the
        // ordering; the previous tree put Administration third from the end for no
        // reason and had no My Work at all.
        $this->assertSame('Dashboard', $top[0]);
        $this->assertSame('My Work', $top[1]);
        $this->assertSame('Administration', end($top), 'Administration should be the last top-level section.');

        $this->assertContains('To-Dos', $labels);
    }

    /**
     * A fresh instance of the same user: `superAdmin()` creates one, and calling
     * it twice in a test would leave two.
     */
    private function freshSuperAdmin(array $permissions): User
    {
        return $this->superAdmin($permissions);
    }

    // ---- Active state -------------------------------------------------------

    public function test_the_current_screen_is_the_only_active_item(): void
    {
        $user = $this->superAdmin(['user.manage']);

        $this->actingAs($user);
        $this->get('/admin/users')->assertOk();

        $menu = app(NavigationMenu::class);

        $current = [];

        $this->walk($menu->forUser($user), function (array $node) use ($menu, &$current): void {
            if ($menu->isCurrent($node)) {
                $current[] = $node['label'];
            }
        });

        $this->assertSame(['Users'], $current);
    }

    public function test_a_parameterised_route_activates_only_its_own_node(): void
    {
        // The four identity Reference Data screens are one route with a
        // `{resource}` parameter. Without parameter matching, visiting Employees
        // would highlight Companies, Departments and Locations as well.
        $user = $this->superAdmin(['user.manage']);

        $this->actingAs($user);
        $this->get('/admin/reference/departments')->assertOk();

        $menu = app(NavigationMenu::class);

        $current = [];

        $this->walk($menu->forUser($user), function (array $node) use ($menu, &$current): void {
            if ($menu->isCurrent($node)) {
                $current[] = $node['label'];
            }
        });

        $this->assertSame(['Departments'], $current);
    }

    public function test_a_branch_stays_open_for_the_screen_inside_it(): void
    {
        $user = $this->superAdmin(['obligation.view', 'obligation.view_reports']);

        $this->actingAs($user);
        $this->get('/obligations/renewals')->assertOk();

        $menu = app(NavigationMenu::class);

        $branches = [];

        $this->walk($menu->forUser($user), function (array $node) use ($menu, &$branches): void {
            if (($node['children'] ?? []) !== [] && $menu->isActive($node)) {
                $branches[] = $node['label'];
            }
        });

        $this->assertContains('Obligations', $branches);
    }

    // ---- Rendering ----------------------------------------------------------

    public function test_every_page_renders_the_menu_without_a_server_error(): void
    {
        $user = $this->superAdmin([
            'todos.view', 'task.view', 'meeting.view', 'obligation.view', 'project.view',
            'report.view', 'user.manage',
        ]);

        foreach ([
            '/dashboard', '/todos', '/tasks', '/meetings', '/obligations', '/projects',
            '/my-work', '/admin/users', '/admin/reference/employees', '/reports/tasks',
        ] as $uri) {
            $this->actingAs($user)->get($uri)->assertOk();
        }
    }

    public function test_the_menu_marks_its_expanded_state_for_assistive_technology(): void
    {
        $user = $this->superAdmin(['obligation.view']);

        $html = $this->actingAs($user)->get('/obligations')->assertOk()->getContent();

        // Without it a screen reader cannot distinguish an expanded group from a
        // collapsed one, and the previous tree had no state attribute at all.
        $this->assertStringContainsString('aria-expanded="true"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
    }

    public function test_section_headings_only_appear_with_something_under_them(): void
    {
        $plain = $this->actingAs($this->plainUser())->get('/dashboard')->assertOk()->getContent();

        // A heading with nothing beneath it is a rule on the page with no label.
        $this->assertStringNotContainsString('nav-header', $plain);

        $admin = $this->actingAs($this->superAdmin(['user.manage']))->get('/dashboard')->assertOk()->getContent();

        $this->assertStringContainsString('nav-header', $admin);
    }

    // ---- Helpers ------------------------------------------------------------

    /**
     * @return list<array<string, mixed>>
     */
    private function configMenu(): array
    {
        return (array) config('navigation.menu', []);
    }

    /**
     * @param  list<array<string, mixed>>  $nodes
     * @param  callable(array<string, mixed>, int): void  $visitor
     */
    private function walk(array $nodes, callable $visitor, int $depth = 0): void
    {
        foreach ($nodes as $node) {
            $visitor($node, $depth);
            $this->walk((array) ($node['children'] ?? []), $visitor, $depth + 1);
        }
    }

    /**
     * @param  list<string>  $duplicates
     */
    private function checkSiblings(array $node, array &$duplicates): void
    {
        $children = (array) ($node['children'] ?? []);

        $labels = array_map(fn (array $child): string => (string) ($child['label'] ?? ''), $children);

        foreach (array_keys(array_count_values($labels)) as $label) {
            if ($label === '') {
                continue;
            }

            if (substr_count(implode('|', $labels), $label) > 1) {
                $duplicates[] = ($node['label'] ?? '(root)').' has two children labelled "'.$label.'"';
            }
        }

        foreach ($children as $child) {
            $this->checkSiblings($child, $duplicates);
        }
    }

    /**
     * Every label in the menu a user actually sees.
     *
     * @return list<string>
     */
    private function labelsFor(User $user): array
    {
        $this->actingAs($user);

        $labels = [];

        $this->walk(app(NavigationMenu::class)->forUser($user), function (array $node) use (&$labels): void {
            $labels[] = (string) $node['label'];
        });

        return $labels;
    }
}
