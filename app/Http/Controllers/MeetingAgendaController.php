<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingAgenda;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingAgendaController extends Controller
{
    public function index(Meeting $meeting): View
    {
        $agendas = $meeting->agendas()->orderBy('sort_order')->paginate(15);
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('meetings.agendas.index', compact('meeting', 'agendas', 'users'));
    }

    public function create(Meeting $meeting): View
    {
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('meetings.agendas.create', compact('meeting', 'users'));
    }

    public function store(Request $request, Meeting $meeting): RedirectResponse
    {
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
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('meetings.agendas.edit', compact('meeting', 'agenda', 'users'));
    }

    public function update(Request $request, Meeting $meeting, MeetingAgenda $agenda): RedirectResponse
    {
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
        $agenda->delete();

        return redirect()->route('meetings.show', $meeting)->with('success', 'Agenda item deleted successfully.');
    }
}
