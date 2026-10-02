<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Reads and writes the application's own settings.
 *
 * Two decisions worth stating.
 *
 * **DEFAULTS LIVE IN CODE, NOT ONLY IN THE DATABASE.** {@see DEFAULTS} is the
 * authoritative list of what the application understands, and the table holds
 * overrides. That direction matters: a settings table as the only source means a
 * deployment that has never opened the settings screen has no settings at all,
 * and a key renamed in code is silently ignored rather than loudly unknown.
 *
 * **READS ARE CACHED, AND THE CACHE IS INVALIDATED ON WRITE.** The settings screen
 * is read on nearly every page (the layout resolves the date format), so an
 * uncached read is a query per page. The cache is versioned rather than keyed per
 * setting, for the same reason the dashboard widget cache is: the store cannot
 * target a wildcard, so one write has to be able to drop everything.
 */
class Settings
{
    /**
     * Every setting the application understands, with its type and default.
     *
     * `group` drives the settings screen's navigation. `label` and `description`
     * are shown to the operator, so a value's meaning does not have to be inferred
     * from its key.
     *
     * @var array<string, array{type: string, default: mixed, group: string, label: string, description: string}>
     */
    public const DEFAULTS = [
        'app.locale' => [
            'type' => 'string',
            'default' => 'en',
            'group' => 'Localisation',
            'label' => 'Default language',
            'description' => 'BCP 47 language tag used when a user has not chosen one. Example: en, bn, ar.',
        ],
        'app.timezone' => [
            'type' => 'string',
            'default' => 'UTC',
            'group' => 'Localisation',
            'label' => 'Timezone',
            'description' => 'IANA timezone the business operates in. Scheduled commands run in this zone.',
        ],
        'app.date_format' => [
            'type' => 'string',
            'default' => 'Y-m-d',
            'group' => 'Localisation',
            'label' => 'Date format',
            'description' => 'PHP date format used for display. Storage stays ISO 8601 regardless of this setting.',
        ],
        'app.week_starts_on' => [
            'type' => 'integer',
            'default' => 1,
            'group' => 'Localisation',
            'label' => 'Week starts on',
            'description' => 'ISO day of week: 1 = Monday, 7 = Sunday.',
        ],

        'security.session_lifetime' => [
            'type' => 'integer',
            'default' => 120,
            'group' => 'Security',
            'label' => 'Session lifetime (minutes)',
            'description' => 'A session expires after this much inactivity.',
        ],
        'security.password_min_length' => [
            'type' => 'integer',
            'default' => 12,
            'group' => 'Security',
            'label' => 'Minimum password length',
            'description' => 'NIST SP 800-63B recommends a minimum of 8; 12 is this deployment\'s policy.',
        ],
        'security.password_expires_days' => [
            'type' => 'integer',
            'default' => 0,
            'group' => 'Security',
            'label' => 'Password expiry (days)',
            'description' => '0 disables forced expiry. NIST SP 800-63B advises against routine rotation.',
        ],
        'security.max_failed_attempts' => [
            'type' => 'integer',
            'default' => 5,
            'group' => 'Security',
            'label' => 'Failed logins before lockout',
            'description' => 'Applies within the throttle window.',
        ],

        'notifications.reminder_default_lead' => [
            'type' => 'integer',
            'default' => 30,
            'group' => 'Notifications',
            'label' => 'Default reminder lead time (minutes)',
            'description' => 'Used when a reminder is created without an explicit offset.',
        ],
        'notifications.digest_hour' => [
            'type' => 'integer',
            'default' => 8,
            'group' => 'Notifications',
            'label' => 'Daily digest hour',
            'description' => 'Local hour the daily summary is sent.',
        ],

        'branding.organisation_name' => [
            'type' => 'string',
            'default' => 'WorkSphere',
            'group' => 'Branding',
            'label' => 'Organisation name',
            'description' => 'Shown in the sidebar and in exported documents.',
        ],
        'branding.support_email' => [
            'type' => 'string',
            'default' => '',
            'group' => 'Branding',
            'label' => 'Support email',
            'description' => 'Displayed on error and empty states that ask for help.',
        ],

        'compliance.retention_days' => [
            'type' => 'integer',
            'default' => 2555,
            'group' => 'Compliance',
            'label' => 'Audit retention (days)',
            'description' => 'ISO 8601 seven years. Audit rows are not deleted before this age.',
        ],
    ];

    private const CACHE_KEY = 'system:settings:v';

    public function get(string $key, mixed $fallback = null): mixed
    {
        $defaults = self::DEFAULTS[$key] ?? null;

        if ($defaults === null) {
            // An unknown key is a programming error. Returning the fallback keeps
            // a typo in one call site from taking a page down, which is the right
            // trade — but it is logged, because silence here is how a misspelled
            // setting goes unnoticed for months.
            logger()->warning('Unknown setting requested.', ['key' => $key]);

            return $fallback;
        }

        $stored = $this->all()[$key] ?? null;

        if ($stored === null) {
            return $defaults['default'];
        }

        return $this->cast($stored, $defaults['type']);
    }

    public function bool(string $key, bool $fallback = false): bool
    {
        return (bool) $this->get($key, $fallback);
    }

    public function int(string $key, int $fallback = 0): int
    {
        $value = $this->get($key, $fallback);

        return is_numeric($value) ? (int) $value : $fallback;
    }

    public function string(string $key, string $fallback = ''): string
    {
        $value = $this->get($key, $fallback);

        return is_scalar($value) ? (string) $value : $fallback;
    }

    /**
     * Every setting, defaults merged with stored overrides.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $stored = Cache::remember(
            self::CACHE_KEY,
            300,
            fn (): array => Setting::query()->pluck('value', 'key')->all(),
        );

        return $stored;
    }

    /**
     * Persist a value, and drop the cache.
     *
     * Rejects a key the application does not declare. Allowing an undeclared key
     * means the settings screen can hold rows nothing reads, which is how a table
     * of forty settings becomes a table of five.
     */
    public function set(string $key, mixed $value, ?int $actorId = null): Setting
    {
        $definition = self::DEFAULTS[$key] ?? null;

        if ($definition === null) {
            throw new \InvalidArgumentException("Unknown setting [{$key}].");
        }

        $setting = Setting::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $definition['type'],
                'group' => $definition['group'],
                'label' => $definition['label'],
                'description' => $definition['description'],
                'updated_by' => $actorId,
            ],
        );

        $this->flush();

        return $setting;
    }

    /**
     * Every declared setting, resolved, grouped for the screen.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function grouped(): array
    {
        $stored = $this->all();

        $groups = [];

        foreach (self::DEFAULTS as $key => $definition) {
            $value = $stored[$key] ?? null;

            $groups[$definition['group']][] = [
                'key' => $key,
                'label' => $definition['label'],
                'description' => $definition['description'],
                'type' => $definition['type'],
                'group' => $definition['group'],
                'value' => $value === null ? $definition['default'] : $this->cast($value, $definition['type']),
                'default' => $definition['default'],
                'is_overridden' => $value !== null,
            ];
        }

        return $groups;
    }

    /**
     * Drop the cache.
     *
     * A flush rather than a targeted forget, so one call is always sufficient and
     * a partially-stale settings screen is not a state the API allows.
     */
    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function cast(mixed $value, string $type): mixed
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            'integer' => is_numeric($value) ? (int) $value : 0,
            'float' => is_numeric($value) ? (float) $value : 0.0,
            'json', 'array' => $value,
            default => is_array($value) ? json_encode($value) : (string) $value,
        };
    }
}
