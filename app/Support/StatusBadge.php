<?php

namespace App\Support;

use App\Enums\Priority;
use App\Enums\Visibility;
use App\Enums\WorkItemStatus;
use BackedEnum;

/**
 * The single status → badge-variant map (GAP-020).
 *
 * Every module had its own inline `match` arms for the same decision, and they
 * had already drifted: Tasks rendered `completed` as `success` and `in_progress`
 * as `primary`, Meetings rendered the same two values differently, and the
 * dashboard had a third copy. The same status therefore appeared in three
 * colours depending on the screen.
 *
 * Both a `WorkItemStatus` enum case and the raw string are accepted so this can
 * be called from a Blade template, a controller, or a report row without the
 * caller having to cast first.
 *
 * Colours alone never carry the meaning — the label is always rendered next to
 * the badge — so a status is still legible in monochrome or with a colour
 * vision deficiency (WCAG AA).
 */
final class StatusBadge
{
    /**
     * Status → badge variant.
     *
     * @var array<string, string>
     */
    private const STATUS_VARIANTS = [
        'inbox' => 'secondary',
        'planned' => 'info',
        'pending' => 'secondary',
        'scheduled' => 'info',
        'open' => 'info',
        'in_progress' => 'primary',
        'on_hold' => 'warning',
        'waiting' => 'warning',
        'postponed' => 'warning',
        'skipped' => 'secondary',
        'completed' => 'success',
        'cancelled' => 'danger',
        'archived' => 'dark',
    ];

    /**
     * Priority → badge variant.
     *
     * @var array<string, string>
     */
    private const PRIORITY_VARIANTS = [
        'low' => 'secondary',
        'normal' => 'secondary',
        'medium' => 'info',
        'important' => 'warning',
        'high' => 'warning',
        'urgent' => 'danger',
        'critical' => 'danger',
    ];

    /**
     * Visibility → badge variant.
     *
     * @var array<string, string>
     */
    private const VISIBILITY_VARIANTS = [
        'personal' => 'secondary',
        'team' => 'info',
        'private' => 'dark',
    ];

    private const FALLBACK = 'secondary';

    /**
     * Priority → a CSS colour, for charts and progress bars that cannot use a
     * Bootstrap variant.
     *
     * Lives beside `PRIORITY_VARIANTS` rather than in a controller because the
     * charts and the badges describe the same priority and had drifted apart: the
     * dashboard coloured `critical` one way and badged it another.
     *
     * @var array<string, string>
     */
    private const PRIORITY_COLORS = [
        'low' => 'var(--bs-success)',
        'normal' => 'var(--bs-success)',
        'medium' => 'var(--bs-info)',
        'important' => 'var(--bs-warning)',
        'high' => 'var(--bs-warning)',
        'urgent' => 'var(--bs-danger)',
        'critical' => 'var(--bs-danger)',
    ];

    private const FALLBACK_COLOR = 'var(--bs-secondary)';

    /**
     * Status → a CSS colour, for charts that paint slices rather than badges.
     *
     * The sibling of {@see PRIORITY_COLORS} and for the same reason: the badge
     * variant and the chart colour describe the same status, and building one from
     * the other produced a `match` with no `default` arm that threw
     * `UnhandledMatchError` the first time a status was added. An explicit map with
     * a fallback cannot.
     *
     * `pending` and `in_progress` deliberately differ from the naive
     * `var(--bs-<variant>)` derivation: the dashboard has always drawn pending as
     * amber and in-progress as blue, and changing the colours of an existing chart
     * is not part of adding a status.
     *
     * @var array<string, string>
     */
    private const STATUS_COLORS = [
        'inbox' => 'var(--bs-secondary)',
        'planned' => 'var(--bs-info)',
        'pending' => 'var(--bs-warning)',
        'scheduled' => 'var(--bs-info)',
        'open' => 'var(--bs-info)',
        'in_progress' => 'var(--bs-info)',
        'on_hold' => 'var(--bs-warning)',
        'waiting' => 'var(--bs-warning)',
        'postponed' => 'var(--bs-secondary)',
        'skipped' => 'var(--bs-secondary)',
        'completed' => 'var(--bs-success)',
        'cancelled' => 'var(--bs-danger)',
        'archived' => 'var(--bs-dark, var(--bs-secondary))',
    ];

    /**
     * Bootstrap contextual variant for a work-item status.
     */
    public static function variant(WorkItemStatus|string|null $status): string
    {
        return self::lookup(self::STATUS_VARIANTS, self::value($status));
    }

    /**
     * The CSS classes that render a variant as a readable badge.
     *
     * `text-bg-<variant>` is the right utility for most of these, but not for
     * `secondary`. Bootstrap's `.text-bg-secondary` hard-codes `color: #fff` and
     * fills with `--bs-secondary-rgb`. This theme redefines that token as a light
     * surface grey (`--app-secondary`, `244, 244, 245`) because it is used for
     * `--app-muted`, `--app-accent` and the sidebar highlight — none of which sit
     * behind white text. The result is white text on a near-white fill at roughly
     * 1.05:1 contrast, so every neutral badge rendered as a blank rectangle.
     *
     * `secondary` therefore maps to the token pair that was designed for it:
     * the light surface grey as the fill, dark text on top. It reads as the
     * neutral chip it is meant to be, and matches the muted badges already used
     * in dark mode via `--bs-secondary-bg-subtle`.
     *
     * Every other variant keeps Bootstrap's own utility, so nothing else changes
     * appearance. `text-bg-warning` is left as-is too: white on amber is poor
     * contrast, but it is visible, and restating the whole scale here would be a
     * different change.
     *
     * An unrecognised variant degrades to the neutral pair rather than becoming
     * `text-bg-<whatever>`. A class that does not exist applies no fill at all, so
     * the badge renders as bare text with no chip — the same symptom as the bug
     * this replaced, reached by a different route. It happens the moment a caller
     * passes a status where a variant belongs.
     *
     * @return string One or more Bootstrap utility classes.
     */
    public static function badgeClass(string|BackedEnum|null $variant): string
    {
        return match (self::value($variant)) {
            'secondary', null => 'bg-secondary text-dark',
            'primary', 'info', 'warning', 'success', 'danger', 'dark' => 'text-bg-'.self::value($variant),
            default => 'bg-secondary text-dark',
        };
    }

    /**
     * The classes for a badge showing a status, however the caller reaches the
     * status. Shorthand for `badgeClass(variant($status))`.
     */
    public static function statusBadgeClass(WorkItemStatus|string|null $status): string
    {
        return self::badgeClass(self::variant($status));
    }

    /**
     * The classes for a badge showing a priority.
     */
    public static function priorityBadgeClass(Priority|string|null $priority): string
    {
        return self::badgeClass(self::priorityVariant($priority));
    }

    /**
     * A CSS colour for a status, for anything that paints rather than badges.
     */
    public static function statusColor(WorkItemStatus|string|null $status): string
    {
        $value = self::value($status);

        return $value === null ? self::FALLBACK_COLOR : (self::STATUS_COLORS[$value] ?? self::FALLBACK_COLOR);
    }

    public static function priorityVariant(Priority|string|null $priority): string
    {
        return self::lookup(self::PRIORITY_VARIANTS, self::value($priority));
    }

    /**
     * A CSS colour for a priority, for anything that paints rather than badges.
     *
     * Falls back for a value outside the map. A dashboard that 500s because a
     * column holds a string nobody anticipated is a page that is one data-entry
     * mistake away from being down for everybody who looks at it.
     */
    public static function priorityColor(Priority|string|null $priority): string
    {
        $value = self::value($priority);

        return $value === null ? self::FALLBACK_COLOR : (self::PRIORITY_COLORS[$value] ?? self::FALLBACK_COLOR);
    }

    public static function visibilityVariant(Visibility|string|null $visibility): string
    {
        return self::lookup(self::VISIBILITY_VARIANTS, self::value($visibility));
    }

    /**
     * Human label for a status, priority or visibility, safe to render as-is.
     *
     * Accepts any backed enum so a caller does not have to know which family a
     * value belongs to before labelling it.
     */
    public static function label(BackedEnum|string|null $subject): string
    {
        if ($subject === null) {
            return '—';
        }

        if ($subject instanceof WorkItemStatus || $subject instanceof Priority || $subject instanceof Visibility) {
            return $subject->label();
        }

        $case = WorkItemStatus::tryFrom($subject);

        return $case?->label() ?? ucwords(str_replace('_', ' ', $subject));
    }

    /**
     * Every status a filter may offer, so a list and its filter cannot disagree
     * about which values exist.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (WorkItemStatus $case): array => [
                'value' => $case->value,
                'label' => $case->label(),
            ],
            WorkItemStatus::cases(),
        );
    }

    /**
     * @param  array<string, string>  $map
     */
    private static function lookup(array $map, ?string $value): string
    {
        if ($value === null) {
            return self::FALLBACK;
        }

        return $map[$value] ?? self::FALLBACK;
    }

    private static function value(WorkItemStatus|Priority|Visibility|string|null $subject): ?string
    {
        return $subject instanceof BackedEnum ? $subject->value : $subject;
    }
}
