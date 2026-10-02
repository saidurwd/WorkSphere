<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * The colour and accessibility guarantees in `resources/css/tokens.css` and
 * `resources/css/widgets.css`, asserted against the source.
 *
 * These are the same class of promise as `TodoUiConventionsTest`: a token pair
 * that falls below its contrast floor is invisible in a rendered page and in a
 * functional test that only asserts a 200, but it is a hard number in a
 * stylesheet, so it can be checked directly. The alternative — a browser
 * screenshot diff — needs a headless browser in CI and still cannot explain
 * *why* a colour changed.
 *
 * Every ratio below is the WCAG 2.2 relative-luminance formula, recomputed from
 * the hex literal in the file rather than copied from a comment, so editing a
 * token to a different-but-equally-bad value fails the test.
 */
class DesignTokenContrastTest extends TestCase
{
    /**
     * The token file, read once per test.
     */
    private function tokens(): string
    {
        return (string) file_get_contents(base_path('resources/css/tokens.css'));
    }

    private function widgets(): string
    {
        return (string) file_get_contents(base_path('resources/css/widgets.css'));
    }

    /**
     * A stylesheet with its comments removed.
     *
     * The rules in widgets.css are introduced by comments that name and explain
     * the selectors they contain. A substring search over the raw source
     * therefore finds a selector in the prose explaining a rule that no longer
     * has it, and reports the rule as intact when it is broken.
     */
    private function stripComments(string $css): string
    {
        return preg_replace('#/\*.*?\*/#s', '', $css) ?? $css;
    }

    /**
     * The value declared for `$token` inside `$block`, or null when the token
     * is not declared there.
     *
     * Scoped to a block on purpose: the light and dark themes declare the same
     * names with different values, and a search across the whole file would
     * return whichever came first.
     */
    private function tokenIn(string $css, string $block, string $token): ?string
    {
        $start = strpos($css, $block);

        if ($start === false) {
            return null;
        }

        $depth = 0;
        $end = null;

        for ($i = strpos($css, '{', $start); $i !== false; $i++) {
            if ($css[$i] === '{') {
                $depth++;
            } elseif ($css[$i] === '}') {
                $depth--;

                if ($depth === 0) {
                    $end = $i;
                    break;
                }
            }
        }

        $body = $end === null ? substr($css, $start) : substr($css, $start, $end - $start);

        return preg_match('/--'.preg_quote($token, '/').'\s*:\s*([^;]+);/', $body, $m) === 1
            ? trim($m[1])
            : null;
    }

    /**
     * WCAG 2.2 relative luminance.
     */
    private function luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        $channels = array_map(
            static fn (int $value): float => $value / 255,
            [
                hexdec(substr($hex, 0, 2)),
                hexdec(substr($hex, 2, 2)),
                hexdec(substr($hex, 4, 2)),
            ],
        );

        $linear = array_map(
            static fn (float $c): float => $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4,
            $channels,
        );

        return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
    }

    /**
     * WCAG 2.2 contrast ratio between two hex colours.
     */
    private function contrast(string $a, string $b): float
    {
        $la = $this->luminance($a);
        $lb = $this->luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    private function badgePairs(string $block, array $variants): array
    {
        $pairs = [];

        foreach ($variants as $variant) {
            $fill = $this->tokenIn($this->tokens(), $block, 'app-'.$variant);
            $foreground = $this->tokenIn($this->tokens(), $block, 'app-'.$variant.'-foreground');

            if ($fill !== null && $foreground !== null) {
                $pairs[$variant] = [$foreground, $fill];
            }
        }

        return $pairs;
    }

    public function test_status_badges_clear_wcag_aa_in_both_themes(): void
    {
        // The regression this guards: AdminLTE hardcodes the `.text-bg-*`
        // foreground to white for primary, success and danger, which put white
        // on a saturated fill at 2.28:1 and 3.76:1. The tokens now pair each
        // fill with a foreground that clears 4.5:1 — a 12px badge label is body
        // text, not large text, so the 3:1 allowance does not apply.
        $variants = ['primary', 'success', 'warning', 'info', 'danger'];
        $failures = [];

        foreach ([':root', "[data-bs-theme='dark']"] as $block) {
            foreach ($this->badgePairs($block, $variants) as $variant => [$foreground, $fill]) {
                $ratio = $this->contrast($foreground, $fill);

                if ($ratio < 4.5) {
                    $failures[] = sprintf(
                        '%s %s: %s on %s = %.2f:1, needs 4.5:1',
                        $block,
                        $variant,
                        $foreground,
                        $fill,
                        $ratio,
                    );
                }
            }
        }

        $this->assertSame([], $failures, "Badge pairs below WCAG AA:\n".implode("\n", $failures));
    }

    public function test_badge_foregrounds_are_overridden_in_css(): void
    {
        // Declaring a correct token is not enough: AdminLTE's `.text-bg-*`
        // rules set `color` on `!important`, so a token change cannot reach the
        // rendered badge unless widgets/tokens restate the foreground. This
        // asserts the override exists for each variant, and that it carries the
        // `!important` needed to win.
        $css = $this->tokens();
        $missing = [];

        foreach (['primary', 'success', 'warning', 'info', 'danger'] as $variant) {
            $pattern = '/\.text-bg-'.$variant.'\s*\{[^}]*--app-'.$variant.'-foreground[^}]*!important[^}]*\}/';

            if (preg_match($pattern, $css) !== 1) {
                $missing[] = $variant;
            }
        }

        $this->assertSame(
            [],
            $missing,
            'Each .text-bg-* variant needs a foreground override carrying !important, '
            .'or AdminLTE\'s hardcoded colour wins: '.implode(', ', $missing),
        );
    }

    public function test_muted_foreground_clears_aa_on_a_striped_table_row(): void
    {
        // `text-body-secondary` is the second most-used colour in the app and
        // is concentrated in dense tables, where Bootstrap tints the row 5%
        // toward the emphasis colour. The old #71717a measured 4.36:1 on that
        // tinted row even though it passed on white.
        $foreground = $this->tokenIn($this->tokens(), ':root', 'app-muted-foreground');

        $this->assertNotNull($foreground, '--app-muted-foreground must be declared in :root.');

        // #f3f3f3 is white with 5% of the light emphasis colour (#09090b) mixed
        // in — Bootstrap's `.table-striped-bg`.
        $ratio = $this->contrast($foreground, '#f3f3f3');

        $this->assertGreaterThanOrEqual(
            4.5,
            $ratio,
            "--app-muted-foreground {$foreground} on a striped row is {$ratio}:1, needs 4.5:1.",
        );
    }

    public function test_dark_theme_border_is_not_white(): void
    {
        // `--app-border` feeds `--bs-border-color` and the legacy `--border`,
        // which inline styles in the Tasks dashboard and show pages draw
        // directly. A pure-white value put a 1px glare outline on every card.
        $border = $this->tokenIn($this->tokens(), "[data-bs-theme='dark']", 'app-border');

        $this->assertNotNull($border, '--app-border must be declared in the dark theme block.');

        $ratio = $this->contrast($border, '#18181b');

        $this->assertLessThan(
            3.0,
            $ratio,
            "--app-border {$border} on a dark card is {$ratio}:1. A border should read as a "
            .'separation, not compete with the content inside it.',
        );
    }

    public function test_dark_theme_page_plane_sits_below_its_cards(): void
    {
        // Cards render on the page, so the page must be the darker plane in dark
        // mode. The page previously reused --app-muted (#27272a), which is
        // lighter than the card (#18181b) and made every card read as a hole.
        $surface = $this->tokenIn($this->tokens(), "[data-bs-theme='dark']", 'app-surface');
        $card = $this->tokenIn($this->tokens(), "[data-bs-theme='dark']", 'app-card');

        $this->assertNotNull($surface, '--app-surface must be declared in the dark theme block.');
        $this->assertNotNull($card, '--app-card must be declared in the dark theme block.');

        $this->assertLessThan(
            $this->luminance($card),
            $this->luminance($surface),
            "--app-surface {$surface} is not darker than --app-card {$card}: the elevation is inverted.",
        );
    }

    public function test_page_and_sidebar_neutral_triplets_are_mapped(): void
    {
        // `.bg-body-tertiary` and `.bg-body-secondary` are `!important`
        // utilities that resolve through an *-rgb triplet AdminLTE declares in
        // its own :root. Without an override the page and sidebar rendered in
        // AdminLTE's cool blue-grey while the rest of the app used this file's
        // warm zinc, so the two palettes never met.
        foreach ([':root', "[data-bs-theme='dark']"] as $block) {
            foreach (['bs-tertiary-bg-rgb', 'bs-secondary-bg-rgb'] as $token) {
                $this->assertNotNull(
                    $this->tokenIn($this->tokens(), $block, $token),
                    "--{$token} must be mapped in {$block} or the page and sidebar keep AdminLTE's palette.",
                );
            }
        }
    }

    public function test_focus_ring_is_declared_and_outranks_bootstrap(): void
    {
        // Two things have to hold. The ring must exist at all, and it must
        // outrank the `outline: 0` that Bootstrap sets on its own focusable
        // components — those selectors are at 0,2,0, so a bare `:focus-visible`
        // at 0,1,0 silently loses on .btn, .nav-link and .form-control.
        $css = $this->widgets();

        $this->assertMatchesRegularExpression(
            '/:focus-visible[^{]*\{[^}]*outline:\s*2px solid var\(--app-ring\)/',
            $css,
            'widgets.css must draw a 2px focus ring in --app-ring.',
        );

        // Parsed out of the rule's selector list rather than searched for as a
        // substring: the selectors are also named in the comment above the rule,
        // and a substring check is satisfied by that prose while the rule itself
        // has lost them.
        //
        // Anchored on `:focus` with no other pseudo-class before it, so an
        // earlier rule that happens to draw the same outline — `.skip-link:focus`
        // does — cannot be mistaken for the general rule and yield its two
        // selectors instead.
        //
        // Comments are removed first. The rule is introduced by a comment that
        // names and explains every selector in the list, so matching raw source
        // would pull that prose into the selector list and let a broken rule
        // pass on the strength of the note describing it.
        $css = $this->stripComments($this->widgets());

        $this->assertSame(
            1,
            preg_match(
                '/(?<selectors>[^{}]*?\B:focus-visible[^{}]*?)\{[^}]*?outline:\s*2px solid var\(--app-ring\)/s',
                $css,
                $match,
            ),
            'Could not locate the focus-ring rule in widgets.css.',
        );

        $selectors = array_map(
            static fn (string $selector): string => trim($selector),
            explode(',', $match['selectors']),
        );

        // The selectors are the `:focus-visible` form of the rules Bootstrap
        // cancels, not the `:focus` form it happens to use: a mouse click on a
        // text field should not draw a ring, and matching `:focus` here would
        // put one back.
        foreach (['.btn:focus-visible', '.nav-link:focus-visible', '.form-control:focus-visible'] as $required) {
            $this->assertContains(
                $required,
                $selectors,
                "The focus ring selector list must include {$required}; Bootstrap cancels the "
                .'outline on it at a specificity a bare :focus-visible cannot match.',
            );
        }
    }

    public function test_reduced_motion_is_honoured(): void
    {
        // The dashboard animates donut strokes, the sidebar and dropdowns
        // transition, and every confirmation is a SweetAlert2 animation. None
        // of it carries meaning, so WCAG 2.3.3 applies.
        $this->assertMatchesRegularExpression(
            '/@media \(prefers-reduced-motion: reduce\)/',
            $this->widgets(),
            'widgets.css must collapse animation and transition under prefers-reduced-motion.',
        );

        $this->assertMatchesRegularExpression(
            '/prefers-reduced-motion: reduce.*?transition-duration:\s*0\.01ms\s*!important/s',
            $this->widgets(),
            'The reduced-motion block must shorten transitions, not merely open the query.',
        );
    }

    public function test_type_and_spacing_scales_are_declared(): void
    {
        // 300 inline `font-size` declarations across the views exist because
        // there was no scale to reach for. Declaring one is the precondition for
        // retiring them; without the assertion the scale itself is the thing
        // that quietly goes missing.
        $css = $this->tokens();

        foreach (['text-xs', 'text-sm', 'text-base', 'text-lg', 'text-xl', 'text-2xl'] as $token) {
            $this->assertNotNull(
                $this->tokenIn($css, ':root', $token),
                "--{$token} must be declared so a view can ask for a size by name.",
            );
        }

        foreach (['space-1', 'space-2', 'space-3', 'space-4', 'space-5', 'space-6'] as $token) {
            $this->assertNotNull(
                $this->tokenIn($css, ':root', $token),
                "--{$token} must be declared so a view can ask for a gutter by name.",
            );
        }
    }
}
