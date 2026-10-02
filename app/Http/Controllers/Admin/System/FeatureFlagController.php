<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Services\FeatureFlags;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The feature-flag screen.
 *
 * Everything is a POST against the flag's own key rather than an edit form per
 * flag. A flag's whole state is four fields — on/off, value, rollout, targeting —
 * and an edit form for each would be four forms to keep consistent for what is
 * really one switch.
 *
 * TOGGLING IS A SEPARATE ENDPOINT from saving, because turning a flag ON is the
 * action with consequences and the one that deserves its own button, its own
 * confirmation and its own audit entry.
 */
class FeatureFlagController extends Controller
{
    public function __construct(private readonly FeatureFlags $flags) {}

    public function index(): View
    {
        $this->authorize('system.flags');

        return view('admin.system.flags', [
            'flags' => $this->flags->definitions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('system.flags');

        $validated = $request->validate([
            'key' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+([._-][a-z0-9]+)*$/'],
            'name' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'in:boolean,integer,float,string,json'],
            'value' => ['nullable'],
            'rollout_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            // A comma-separated string, because that is what a person can type. A
            // dynamic array input is worse: it needs a JS control, and a flag
            // targeted at two roles should not require building two inputs.
            'target_roles_raw' => ['nullable', 'string', 'max:500'],
        ], [
            // A flag key becomes an attribute on the flag object and is used in
            // cache keys, so it is held to an identifier shape rather than free text.
            'key.regex' => 'Use lowercase letters, numbers, dots, dashes or underscores.',
        ]);

        $this->flags->put([
            ...$validated,
            'target_roles' => $this->parseRoles($validated['target_roles_raw'] ?? null),
            'is_enabled' => false,
        ], $request->user()?->id);

        return back()->with('success', 'Flag created. It is off until you switch it on.');
    }

    public function update(Request $request, string $flag): RedirectResponse
    {
        $this->authorize('system.flags');

        if (! array_key_exists($flag, $this->flags->definitions())) {
            return back()->with('error', "Unknown flag [{$flag}].");
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'in:boolean,integer,float,string,json'],
            'value' => ['nullable'],
            'rollout_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'target_roles_raw' => ['nullable', 'string', 'max:500'],
        ]);

        $existing = $this->flags->definitions()[$flag];

        $this->flags->put([
            'key' => $flag,
            ...$validated,
            'target_roles' => $this->parseRoles($validated['target_roles_raw'] ?? null),
            'is_enabled' => (bool) $existing['is_enabled'],
        ], $request->user()?->id);

        return back()->with('success', 'Flag saved.');
    }

    /**
     * Switch a flag on or off.
     */
    public function toggle(Request $request, string $flag): RedirectResponse
    {
        $this->authorize('system.flags');

        $existing = $this->flags->definitions()[$flag] ?? null;

        if ($existing === null) {
            return back()->with('error', "Unknown flag [{$flag}].");
        }

        $enable = ! $existing['is_enabled'];

        $this->flags->put([
            'key' => $flag,
            'name' => $existing['name'],
            'description' => $existing['description'],
            'type' => $existing['type'],
            'value' => $existing['value'],
            'rollout_percentage' => $existing['rollout_percentage'],
            'variants' => $existing['variants'],
            'target_roles' => $existing['target_roles'],
            'is_enabled' => $enable,
        ], $request->user()?->id);

        return back()->with('success', $enable
            ? "Flag [{$flag}] is now ON."
            : "Flag [{$flag}] is now OFF.");
    }

    /**
     * Split the comma-separated targeting field into role slugs.
     *
     * An empty string becomes NULL rather than an empty array, because the two
     * mean different things to `FeatureFlag::targets()`: NULL is "everyone", and
     * an empty array is "nobody", which would silently disable every flag the
     * moment somebody cleared the field.
     *
     * @return list<string>|null
     */
    protected function parseRoles(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $roles = array_values(array_filter(array_map('trim', explode(',', $raw))));

        return $roles === [] ? null : $roles;
    }

    public function destroy(Request $request, string $flag): RedirectResponse
    {
        $this->authorize('system.flags');

        $this->flags->delete($flag);

        // Deliberately framed as a consequence rather than as a cleanup: deleting a
        // flag returns every call site to its stated default, which is usually what
        // the operator wants and occasionally is not.
        return back()->with('success', "Flag [{$flag}] deleted. Its call sites fall back to their defaults.");
    }
}
