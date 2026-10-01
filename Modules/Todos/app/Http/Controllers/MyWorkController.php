<?php

namespace Modules\Todos\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\MyWorkService;
use App\Services\WorkItemQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "My Work" — GAP-034.
 *
 * One permission-filtered list across Tasks, To-Dos, meeting action items and
 * Obligations. Each source is gated on the permission that governs its own module
 * list, so this page can never show a row the user could not open there.
 */
class MyWorkController extends Controller
{
    public function __construct(
        private readonly MyWorkService $myWork,
        private readonly WorkItemQuery $workItems,
    ) {}

    public function __invoke(Request $request): View
    {
        $data = $this->myWork->forUser($request->user());

        return view('todos.my-work', [
            'workItems' => $data['workItems'],
            'obligations' => $data['obligations'],
            'counts' => $data['counts'],
            'urls' => $data['workItems']
                ->mapWithKeys(fn (object $row): array => [
                    (string) $row->source_type.':'.$row->source_id => $this->myWork->urlFor($row),
                ])
                ->all(),
        ]);
    }
}
