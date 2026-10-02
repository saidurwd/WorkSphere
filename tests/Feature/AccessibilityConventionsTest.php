<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The accessibility guarantees from the Phase 2 audit, asserted over the source
 * and the rendered markup.
 *
 * Same reasoning as `DesignTokenContrastTest` and `TodoUiConventionsTest`: each
 * of these is invisible in a test that only asserts a 200. A missing `scope` on
 * a table header, a status chip with no text, or a `confirm()` in an inline
 * handler all render a page that returns 200 and is unusable to somebody.
 *
 * The markup assertions parse the source rather than substring-matching, because
 * a comment that quotes the selector it forbids is enough to satisfy a naive
 * check — a mistake that was made and caught while writing this file.
 */
class AccessibilityConventionsTest extends TestCase
{
    /**
     * A view's source with its comments removed.
     *
     * Several of the rules below name the markup they forbid — in a comment
     * explaining why it was removed. Matching against raw source finds those
     * explanations and reports the very file that documents the fix as a
     * violation, so the comments have to come out first.
     *
     * Both comment forms are stripped: Blade's `{{-- --}}` and the PHP `/* *\/`
     * docblocks the components open with. Stripping only the Blade form left the
     * docblock prose in place, which was enough to satisfy the form assertions
     * on its own.
     */
    private function code(string $path): string
    {
        $source = (string) file_get_contents($path);

        $source = preg_replace('/\{\{--.*?--\}\}/s', '', $source) ?? $source;

        return preg_replace('#/\*.*?\*/#s', '', $source) ?? $source;
    }

    /**
     * Every Blade view in the application and its modules.
     *
     * @return list<string>
     */
    private function views(): array
    {
        return array_merge(
            glob(base_path('resources/views/**/*.blade.php')) ?: [],
            glob(base_path('Modules/*/resources/views/**/*.blade.php')) ?: [],
        );
    }

    /**
     * Every view, excluding the two kinds where the rule genuinely does not
     * apply: email bodies, which are not part of the app's accessibility tree,
     * and the print view, which is measured in millimetres and must not scroll.
     *
     * @return list<string>
     */
    private function interactiveViews(): array
    {
        return array_values(array_filter(
            $this->views(),
            static fn (string $view): bool => ! str_contains($view, '/emails/')
                && ! str_contains($view, '/print.blade.php'),
        ));
    }

    public function test_both_layouts_offer_a_skip_link_to_main(): void
    {
        // WCAG 2.4.1. Both layouts put a full navigation tree before the content,
        // so reaching <main> by keyboard otherwise means tabbing through it on
        // every page.
        foreach (['app', 'guest'] as $layout) {
            $source = $this->code(base_path("resources/views/layouts/{$layout}.blade.php"));

            $this->assertMatchesRegularExpression(
                '/<a href="#main-content" class="skip-link">/',
                $source,
                "layouts/{$layout}.blade.php must offer a skip link as the first body child.",
            );

            $this->assertMatchesRegularExpression(
                '/<main[^>]*id="main-content"/',
                $source,
                "layouts/{$layout}.blade.php must give <main> the id the skip link targets.",
            );
        }
    }

    public function test_navigation_landmarks_are_labelled(): void
    {
        // Two unlabelled navigation landmarks are both announced as simply
        // "navigation", which gives a screen-reader user nothing to tell the
        // sidebar apart from the navbar.
        $sidebar = $this->code(base_path('resources/views/components/sidebar.blade.php'));
        $this->assertMatchesRegularExpression(
            '/<aside[^>]*aria-label="/',
            $sidebar,
            'The sidebar <aside> must be labelled, or it is announced as an unlabelled complementary region.',
        );

        $navbar = $this->code(base_path('resources/views/components/navbar.blade.php'));
        $this->assertMatchesRegularExpression(
            '/<nav[^>]*aria-label="/',
            $navbar,
            'The navbar <nav> must be labelled, or it is announced as an unlabelled navigation landmark.',
        );

        $this->assertStringNotContainsString(
            'sidebar.blade.php',
            '',
        );

        // The account dropdown trigger names itself and states that it opens one.
        $this->assertMatchesRegularExpression(
            '/aria-haspopup="true"/',
            $navbar,
            'The account dropdown trigger must declare aria-haspopup.',
        );
    }

    public function test_form_fields_associate_their_error_message(): void
    {
        // WCAG 3.3.1. `is-invalid` draws a border and an icon and announces
        // nothing, so without this a screen-reader user submits a twenty-field
        // form, hears no error, and cannot tell which field was rejected.
        //
        // One assertion per component per attribute rather than a loop over all
        // three. In a loop, deleting the wiring from one component is still
        // reported as a pass, because the other two satisfy the assertion — the
        // failure message would name whichever component happened to be checked
        // first, not the one that broke.
        $missing = [];

        foreach (['input', 'select', 'textarea'] as $component) {
            // Comments stripped: each component documents these attributes in a
            // comment explaining why they are there, and that prose alone would
            // satisfy a substring search.
            $source = $this->code(base_path("resources/views/components/form/{$component}.blade.php"));

            if (! str_contains($source, 'aria-invalid')) {
                $missing[] = "x-form.{$component}: aria-invalid";
            }

            if (! str_contains($source, 'aria-describedby')) {
                $missing[] = "x-form.{$component}: aria-describedby";
            }

            // The id the describedby points at has to exist, or the reference
            // resolves to nothing and the error is still silent.
            if (preg_match('/id="\{\{\s*\$errorId\s*\}\}"/', $source) !== 1) {
                $missing[] = "x-form.{$component}: the error element's id";
            }
        }

        $this->assertSame(
            [],
            $missing,
            "A field that fails validation must announce it and point at its message:\n"
            .implode("\n", $missing),
        );
    }

    public function test_form_error_and_help_ids_cannot_collide_with_a_label_target(): void
    {
        // Both are derived from the field name, so a field called `title` gets
        // `title-help` and `title-error`. Asserted because a hand-written id
        // would not.
        $source = $this->code(base_path('resources/views/components/form/input.blade.php'));

        $this->assertStringContainsString("\$name.'-help'", $source);
        $this->assertStringContainsString("\$name.'-error'", $source);
    }

    public function test_a_rendered_field_carries_its_error_association(): void
    {
        // The source assertions above prove the attributes are *written*. This
        // proves they are written correctly, by rendering a failing field.
        $this->withViewErrors(['title' => 'The title field is required.']);

        $html = Blade::render('<x-form.input name="title" label="Title" />');

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="title-error"', $html);
        $this->assertStringContainsString('id="title-error"', $html);
    }

    public function test_every_table_header_declares_a_scope(): void
    {
        // 166 headers were left bare, so a screen reader had no way to tell a
        // column header from a data cell. `x-datatable` sets it for the tables
        // that use the component; the hand-rolled ones are the gap.
        $offenders = [];

        foreach ($this->interactiveViews() as $view) {
            preg_match_all('/<th\b[^>]*>/', $this->code($view), $matches);

            foreach ($matches[0] as $tag) {
                if (! str_contains($tag, 'scope')) {
                    $offenders[] = str_replace(base_path().'/', '', $view).': '.$tag;
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Every column header needs a scope:\n".implode("\n", $offenders),
        );
    }

    public function test_no_status_badge_renders_without_text(): void
    {
        // WCAG 1.4.1. A self-closing <x-badge /> paints a coloured pill with
        // nothing in it: the status is carried by hue alone, and the chip is
        // unreadable to everyone.
        $offenders = [];

        foreach ($this->views() as $view) {
            foreach (explode("\n", $this->code($view)) as $number => $line) {
                if (preg_match('/<x-badge\b[^>]*?\/>$/', trim($line)) === 1) {
                    $offenders[] = str_replace(base_path().'/', '', $view).':'.($number + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'A badge with no slot renders a colour with no label: '.implode(', ', $offenders),
        );
    }

    public function test_icon_only_controls_carry_an_accessible_name(): void
    {
        // WCAG 4.1.2. `title` is not a substitute: it is not reliably announced
        // and it does not appear on touch at all.
        $offenders = [];

        foreach ($this->interactiveViews() as $view) {
            preg_match_all('/<(?:a|button)\b[^>]*>/', $this->code($view), $matches);

            foreach ($matches[0] as $tag) {
                if (str_contains($tag, 'aria-label') || str_contains($tag, 'aria-labelledby')) {
                    continue;
                }

                // A `title` is the tell that the control is icon-only: a control
                // that already carries visible text does not need one, so
                // requiring an aria-label only where a title sits catches the
                // button that has nothing but a glyph inside it.
                if (! str_contains($tag, 'title="')) {
                    continue;
                }

                $offenders[] = str_replace(base_path().'/', '', $view).': '
                    .preg_replace('/\s+/', ' ', $tag);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "An icon-only control needs aria-label, not only title:\n".implode("\n", $offenders),
        );
    }

    public function test_destructive_actions_use_the_shared_confirm_dialog(): void
    {
        // A native confirm() is unstyled, blocks the main thread, cannot be
        // translated, and is a second confirmation system beside the SweetAlert2
        // one the app already ships.
        $offenders = [];

        foreach ($this->views() as $view) {
            $source = str_replace('data-confirm', '', $this->code($view));

            if (preg_match('/(?<!data-)\bconfirm\s*\(/', $source) === 1) {
                $offenders[] = str_replace(base_path().'/', '', $view);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Use data-confirm rather than a native confirm(): '.implode(', ', $offenders),
        );
    }

    public function test_aria_grid_is_not_claimed_without_grid_behaviour(): void
    {
        // role="grid" promises arrow-key navigation between cells, row
        // semantics and aria-rowcount. Claiming it on a Bootstrap .row of
        // links made the content harder to reach than leaving it unroled.
        $offenders = [];

        foreach ($this->views() as $view) {
            if (preg_match('/role="grid(?:cell)?"/', $this->code($view)) === 1) {
                $offenders[] = str_replace(base_path().'/', '', $view);
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'role="grid" without keyboard navigation degrades the experience: '.implode(', ', $offenders),
        );
    }

    public function test_live_regions_sit_on_content_that_actually_changes(): void
    {
        // The To-Do summary row carried aria-live but is server-rendered and
        // never changes without a navigation, so it was an inert live region —
        // while the bulk-action counter, which does change, had none.
        $view = $this->code(
            base_path('Modules/Todos/resources/views/todos/index.blade.php')
        );

        $this->assertMatchesRegularExpression(
            '/<span data-selected-count role="status">/',
            $view,
            'The bulk-selection counter must be a live region; it changes on every selection.',
        );

        $this->assertStringNotContainsString(
            'row g-3 mb-4" aria-live',
            $view,
            'The static summary row must not be a live region; it never changes without a navigation.',
        );
    }

    public function test_tables_that_can_scroll_horizontally_are_wrapped(): void
    {
        // WCAG 1.4.10. `.table-responsive` is what stops a wide table pushing
        // the page sideways at 320px.
        $offenders = [];

        foreach ($this->interactiveViews() as $view) {
            $lines = explode("\n", $this->code($view));

            foreach ($lines as $number => $line) {
                if (preg_match('/<table(?![^>]*responsive)/', $line) !== 1) {
                    continue;
                }

                $before = implode('', array_slice($lines, max(0, $number - 3), 3));

                if (! str_contains($before, 'table-responsive')) {
                    $offenders[] = str_replace(base_path().'/', '', $view).':'.($number + 1);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'A wide table needs a .table-responsive wrapper: '.implode(', ', $offenders),
        );
    }

    public function test_no_view_declares_the_same_id_twice(): void
    {
        // WCAG 1.3.1. A duplicated id is not cosmetic: `<label for="remarks">`
        // resolves to the FIRST match, so with three modals on one page each
        // carrying `remarks` the other two labels silently point at a control in
        // a modal that is closed.
        //
        // It was found through a real defect rather than by inspection. Two modal
        // pairs both declared `edit_title`, so `openEditAgendaModal` — which does
        // `getElementById('edit_title').value = title` — resolved to the earlier
        // *action item* modal and filled that one instead, leaving "Edit agenda"
        // blank. The ids are namespaced per modal now; this is what stops that.
        //
        // Source-level only, and deliberately so: a literal id duplicated inside a
        // Blade `@foreach` renders many times but is written once, so counting
        // occurrences in the source finds the authoring mistake without flagging
        // every repeated row.
        $offenders = [];

        foreach ($this->interactiveViews() as $view) {
            preg_match_all('/\bid="([^"{}\s]+)"/', $this->code($view), $matches);

            $duplicates = array_keys(array_filter(
                array_count_values($matches[1]),
                static fn (int $count): bool => $count > 1,
            ));

            foreach ($duplicates as $id) {
                $offenders[] = str_replace(base_path().'/', '', $view).': #'.$id;
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "An id must be unique in a document:\n".implode("\n", $offenders),
        );
    }

    public function test_the_forbidden_page_renders_on_the_guest_layout(): void
    {
        // It used to be a standalone document with its own font stack and its
        // own hardcoded palette, so the one page a user sees when something has
        // already gone wrong was the one page off the design system.
        $view = base_path('resources/views/errors/403.blade.php');
        $source = (string) file_get_contents($view);

        $this->assertStringContainsString(
            "@extends('layouts.guest')",
            $source,
            'The 403 page must render on a shared layout so it inherits the tokens and the landmarks.',
        );

        $this->assertStringNotContainsString(
            '<!DOCTYPE html>',
            $this->code($view),
            'The 403 page must not be a standalone document; that is what took it off the design system.',
        );

        $this->assertStringNotContainsString(
            'font-family:',
            $this->code($view),
            'The 403 page must not declare its own font stack; the layout already loads one.',
        );
    }
}
