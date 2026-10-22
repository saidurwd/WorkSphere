<?php

namespace Tests\Feature;

use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

/**
 * The workspace menu is built by Filament from the resources and pages, so these
 * tests pin what a person is offered rather than a hand-written tree.
 */
class FilamentNavigationTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    /**
     * @return array<string, list<string>> group label => item labels
     */
    private function menuFor(string $panel): array
    {
        Filament::setCurrentPanel($panel);

        $menu = [];

        foreach (Filament::getNavigation() as $group) {
            $menu[(string) ($group->getLabel() ?? '')] = collect($group->getItems())->map(fn ($item): string => (string) $item->getLabel())->all();
        }

        return $menu;
    }

    public function test_every_module_has_its_own_group_in_a_fixed_order(): void
    {
        $this->actingAs($this->superAdmin(['report.view', 'task.view_all', 'todos.view_all']));

        $groups = array_keys($this->menuFor('workspace'));

        $this->assertSame(
            ['To-Dos', 'Tasks', 'Projects', 'Obligations', 'Meetings'],
            array_values(array_intersect($groups, ['To-Dos', 'Tasks', 'Projects', 'Obligations', 'Meetings'])),
        );
    }

    public function test_the_top_of_the_menu_holds_the_personal_pages(): void
    {
        $this->actingAs($this->plainUser());

        $ungrouped = $this->menuFor('workspace')[''] ?? [];

        foreach (['Dashboard', 'My Work', 'Notifications', 'Search'] as $label) {
            $this->assertContains($label, $ungrouped, "{$label} should be in the top-level menu.");
        }
    }

    public function test_reports_are_hidden_from_someone_without_their_permissions(): void
    {
        $this->actingAs($this->plainUser());

        $tasks = $this->menuFor('workspace')['Tasks'] ?? [];

        $this->assertNotContains('Task completion report', $tasks);
        $this->assertNotContains('Task workload report', $tasks);
    }

    public function test_the_completion_report_needs_report_view_and_the_workload_report_needs_task_view_all(): void
    {
        $this->actingAs($this->userWithPermissions(['report.view']));

        $tasks = $this->menuFor('workspace')['Tasks'] ?? [];

        $this->assertContains('Task completion report', $tasks);
        $this->assertNotContains('Task workload report', $tasks);
    }

    public function test_the_workload_report_is_offered_with_task_view_all(): void
    {
        $this->actingAs($this->userWithPermissions(['report.view', 'task.view_all']));

        $this->assertContains('Task workload report', $this->menuFor('workspace')['Tasks'] ?? []);
    }

    public function test_a_notification_log_is_hidden_without_its_permission(): void
    {
        $this->actingAs($this->userWithPermissions(['task.view']));

        $this->assertNotContains('Task notification log', $this->menuFor('workspace')['Tasks'] ?? []);
    }

    public function test_a_notification_log_is_offered_with_its_permission(): void
    {
        $this->actingAs($this->userWithPermissions(['task.view', 'task.view_notification_logs']));

        $this->assertContains('Task notification log', $this->menuFor('workspace')['Tasks'] ?? []);
    }

    public function test_the_administration_menu_is_built_for_administrators_only(): void
    {
        $this->actingAs($this->superAdmin());

        $menu = $this->menuFor('admin');

        $this->assertArrayHasKey('Administration', $menu);
        $this->assertContains('Users', $menu['Administration']);
        $this->assertContains('Roles', $menu['Administration']);
        $this->assertArrayHasKey('System', $menu);
    }
}
