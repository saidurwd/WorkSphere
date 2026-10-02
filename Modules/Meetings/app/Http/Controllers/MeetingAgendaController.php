<?php

namespace Modules\Meetings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ResolvesReferenceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingAgenda;

class MeetingAgendaController extends Controller
{
    use ResolvesReferenceData;

    public function index(Meeting $meeting): View
    {
        $this->authorize('view', $meeting);

        // `presentedBy` is read by the table below. Without this the row count
        // sets the query count, and under `Model::preventLazyLoading()` — which
        // AppServiceProvider enables everywhere except the test suite — the
        // first row 500s the page rather than merely slowing it down.
        $agendas = $meeting->agendas()
            ->with('presentedBy')
            ->orderBy('sort_order')
            ->paginate(15);
        $users = $this->referenceData()->users();

        return view('meetings.agendas.index', compact('meeting', 'agendas', 'users'));
    }

    public function create(Meeting $meeting): View
    {
        $this->authorize('update', $meeting);

        $users = $this->referenceData()->users();

        return view('meetings.agendas.create', compact('meeting', 'users'));
    }

    public function store(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorize('update', $meeting);

        $validated = $request->validate([
            'agenda_no' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'presented_by' => ['nullable', 'exists:users,id'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:pending,in_progress,completed,skipped'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['meeting_id'] = $meeting->id;

        $agenda = MeetingAgenda::create($validated);

        return redirect()->route('meetings.show', $meeting)->with('success', 'Agenda item created successfully.');
    }

    public function edit(Meeting $meeting, MeetingAgenda $agenda): View
    {
        $this->authorize('update', $meeting);

        $users = $this->referenceData()->users();

        return view('meetings.agendas.edit', compact('meeting', 'agenda', 'users'));
    }

    public function update(Request $request, Meeting $meeting, MeetingAgenda $agenda): RedirectResponse
    {
        $this->authorize('update', $meeting);

        $validated = $request->validate([
            'agenda_no' => ['required', 'integer', 'min:1'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'presented_by' => ['nullable', 'exists:users,id'],
            'estimated_minutes' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:pending,in_progress,completed,skipped'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);

        $agenda->update($validated);

        return redirect()->route('meetings.show', $meeting)->with('success', 'Agenda item updated successfully.');
    }

    public function destroy(Meeting $meeting, MeetingAgenda $agenda): RedirectResponse
    {
        $this->authorize('update', $meeting);

        $agenda->delete();

        return redirect()->route('meetings.show', $meeting)->with('success', 'Agenda item deleted successfully.');
    }
}
