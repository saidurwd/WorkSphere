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
}
