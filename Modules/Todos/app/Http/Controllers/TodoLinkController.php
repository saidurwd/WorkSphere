<?php

namespace Modules\Todos\Http\Controllers;

use App\Enums\LinkType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Modules\Todos\Models\Todo;
use Modules\Todos\Services\TodoLinkService;

/**
 * Cross-module links.
 *
 * The morph key comes from a hidden form field, so it is treated as untrusted
 * input and resolved through TodoLinkService's allow-list. A forged
 * `linkable_type` cannot name an arbitrary class.
 */
class TodoLinkController extends Controller
{
    public function __construct(private readonly TodoLinkService $links) {}

    public function store(Request $request, Todo $todo): RedirectResponse
    {
        $this->authorize('update', $todo);

        $validated = $request->validate([
            'linkable_type' => ['required', Rule::in(TodoLinkService::linkableTypes())],
            'linkable_id' => ['required', 'integer', 'min:1'],
            'link_type' => ['nullable', Rule::enum(LinkType::class)],
        ]);

        try {
            $this->links->attach(
                $request->user(),
                $todo,
                $validated['linkable_type'],
                (int) $validated['linkable_id'],
                LinkType::from($validated['link_type'] ?? LinkType::Related->value),
            );
        } catch (InvalidArgumentException $e) {
            // The type resolved but the target does not exist. That is a
            // validation failure, not a server error: reporting it as one would
            // turn a mistyped id into a 500.
            return back()
                ->withErrors(['linkable_id' => $e->getMessage()])
                ->withInput();
        }

        return back()->with('success', 'Link added.');
    }

    public function destroy(Request $request, Todo $todo, int $link): RedirectResponse
    {
        $this->authorize('update', $todo);

        $this->links->detach($request->user(), $todo, $link);

        return back()->with('success', 'Link removed.');
    }
}
