<?php

namespace Modules\Meetings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ResolvesReferenceData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingDecision;

class MeetingDecisionController extends Controller
{
    use ResolvesReferenceData;

    public function index(Meeting $meeting): View
    {
        $decisions = $meeting->decisions()->orderByDesc('decision_no')->paginate(15);
        $users = $this->referenceData()->users();

        return view('meetings.decisions.index', compact('meeting', 'decisions', 'users'));
    }

    public function create(Meeting $meeting): View
    {
        $this->authorize('update', $meeting);

        $users = $this->referenceData()->users();

        return view('meetings.decisions.create', compact('meeting', 'users'));
    }

    public function store(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorize('update', $meeting);

        $validated = $request->validate([
            'agenda_id' => ['nullable', 'exists:meeting_agendas,id'],
            'discussion_id' => ['nullable', 'exists:meeting_discussions,id'],
            'decision_no' => ['required', 'integer', 'min:1'],
            'decision_title' => ['required', 'string', 'max:255'],
            'decision_description' => ['nullable', 'string'],
            'decision_type' => ['required', 'in:approved,rejected,deferred,noted,further_discussion_required'],
            'decision_status' => ['required', 'in:active,superseded,cancelled'],
            'decision_date' => ['nullable', 'date'],
            'approved_by' => ['nullable', 'exists:users,id'],
            'effective_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['meeting_id'] = $meeting->id;
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        MeetingDecision::create($validated);

        return redirect()->route('meetings.show', $meeting)->with('success', 'Decision created successfully.');
    }

    public function edit(Meeting $meeting, MeetingDecision $decision): View
    {
        $this->authorize('update', $meeting);

        $users = $this->referenceData()->users();

        return view('meetings.decisions.edit', compact('meeting', 'decision', 'users'));
    }

    public function update(Request $request, Meeting $meeting, MeetingDecision $decision): RedirectResponse
    {
        $this->authorize('update', $meeting);

        $validated = $request->validate([
            'agenda_id' => ['nullable', 'exists:meeting_agendas,id'],
            'discussion_id' => ['nullable', 'exists:meeting_discussions,id'],
            'decision_no' => ['required', 'integer', 'min:1'],
            'decision_title' => ['required', 'string', 'max:255'],
            'decision_description' => ['nullable', 'string'],
            'decision_type' => ['required', 'in:approved,rejected,deferred,noted,further_discussion_required'],
            'decision_status' => ['required', 'in:active,superseded,cancelled'],
            'decision_date' => ['nullable', 'date'],
            'approved_by' => ['nullable', 'exists:users,id'],
            'effective_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['updated_by'] = auth()->id();

        $decision->update($validated);

        return redirect()->route('meetings.show', $meeting)->with('success', 'Decision updated successfully.');
    }

    public function destroy(Meeting $meeting, MeetingDecision $decision): RedirectResponse
    {
        $this->authorize('update', $meeting);

        $decision->delete();

        return redirect()->route('meetings.show', $meeting)->with('success', 'Decision deleted successfully.');
    }
}
