<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Services\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * The settings screen.
 *
 * VALIDATION IS PER-TYPE AND DERIVED FROM THE DECLARATION, not hand-written per
 * field. A settings form with hand-written rules drifts from the settings it
 * governs — the classic case being a field that accepts integers in the form and
 * strings in the table. `rules()` reads the same `type` the reader casts with, so
 * the two cannot disagree.
 *
 * Only keys declared in `Settings::DEFAULTS` are accepted. An undeclared key in a
 * POST would create a row nothing reads, which is how a settings table becomes a
 * table of five usable rows out of forty.
 */
class SettingsController extends Controller
{
    public function __construct(private readonly Settings $settings) {}

    public function index(): View
    {
        $this->authorize('system.settings');

        return view('admin.system.settings', [
            'groups' => $this->settings->grouped(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('system.settings');

        $input = $request->input('settings', []);

        if (! is_array($input)) {
            return back()->with('error', 'No settings were submitted.');
        }

        $unknown = array_diff(array_keys($input), array_keys(Settings::DEFAULTS));

        if ($unknown !== []) {
            // Rejected rather than ignored: silently dropping an unrecognised key
            // produces a form that appears to save and does not.
            return back()
                ->withInput()
                ->with('error', 'Unknown setting: '.implode(', ', $unknown));
        }

        $validated = [];

        foreach ($input as $key => $value) {
            $definition = Settings::DEFAULTS[$key];

            /**
             * Validated as a single `value` field, NOT as `[$key => $value]`.
             *
             * Laravel resolves a dotted field name as a nested path, so
             * `'security.max_failed_attempts' => '8'` is read as
             * `data['security']['max_failed_attempts']` — which does not exist — and
             * every such setting failed validation with "this field is required"
             * regardless of what was submitted. Setting keys contain dots by
             * design (`app.timezone`, `security.session_lifetime`), so the value is
             * validated under a name that cannot be interpreted as a path and the
             * error is reported back under the name the view reads.
             */
            $validator = Validator::make(
                ['value' => $value],
                ['value' => $this->rulesFor($definition['type'])],
                ['value.required' => 'This setting is required.', 'value.integer' => 'This setting must be a whole number.'],
            );

            if ($validator->fails()) {
                $name = 'settings['.$key.']';

                return back()
                    ->withInput()
                    ->withErrors([$name => $validator->errors()->first('value')])
                    ->with('error', $definition['label'].': '.$validator->errors()->first('value'));
            }

            $validated[$key] = $this->normalise($value, $definition['type']);
        }

        foreach ($validated as $key => $value) {
            $this->settings->set($key, $value, $request->user()?->id);
        }

        return back()->with('success', 'Settings saved.');
    }

    /**
     * @return list<mixed>
     */
    protected function rulesFor(string $type): array
    {
        return match ($type) {
            'integer' => ['required', 'integer', 'min:0', 'max:1000000'],
            'float' => ['required', 'numeric'],
            'boolean' => ['nullable', 'boolean'],
            default => ['nullable', 'string', 'max:255'],
        };
    }

    /**
     * Coerce the submitted form value into the declared type.
     *
     * A checkbox submits nothing when unticked, so `false` arrives as an absent
     * key. `boolean` therefore treats absence as false rather than as null — the
     * alternative is a boolean setting that can never be switched off.
     */
    protected function normalise(mixed $value, string $type): mixed
    {
        return match ($type) {
            'integer' => (int) $value,
            'float' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            default => is_string($value) ? trim($value) : $value,
        };
    }
}
