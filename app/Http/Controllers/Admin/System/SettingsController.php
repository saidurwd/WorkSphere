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

            $validator = Validator::make(
                [$key => $value],
                [$key => $this->rulesFor($definition['type'])],
                [$key => $this->messagesFor($definition['type'])],
            );

            if ($validator->fails()) {
                return back()
                    ->withInput()
                    ->with('error', $definition['label'].': '.$validator->errors()->first($key));
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
     * @return array<string, string>
     */
    protected function messagesFor(string $type): array
    {
        return match ($type) {
            'integer' => ['required' => 'This setting is required and must be a whole number.', 'integer' => 'This setting must be a whole number.'],
            'float' => ['numeric' => 'This setting must be a number.'],
            default => ['max' => 'This setting is limited to 255 characters.'],
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
