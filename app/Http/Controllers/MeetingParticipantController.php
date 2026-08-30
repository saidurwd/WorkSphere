<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingParticipantController extends Controller
{
    public function index(Meeting $meeting): View
    {
        $participants = $meeting->participants()->with('user')->paginate(15);
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('meetings.participants.index', compact('meeting', 'participants', 'users'));
    }

    public function create(Meeting $meeting): View
    {
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('meetings.participants.create', compact('meeting', 'users'));
    }

    public function store(Request $request, Meeting $meeting): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'participant_type' => ['required', 'in:organizer,chairperson,member,guest,presenter,observer'],
            'attendance_status' => ['required', 'in:invited,accepted,declined,present,absent,apology'],
            'remarks' => ['nullable', 'string'],
        ]);

        $validated['meeting_id'] = $meeting->id;
        $validated['invited_at'] = now();

        MeetingParticipant::create($validated);

        return redirect()->route('meetings.show', $meeting)->with('success', 'Participant added successfully.');
    }

    public function edit(Meeting $meeting, MeetingParticipant $participant): View
    {
        $users = User::orderBy('name')->get(['id', 'name']);

        return view('meetings.participants.edit', compact('meeting', 'participant', 'users'));
    }

    public function update(Request $request, Meeting $meeting, MeetingParticipant $participant): RedirectResponse
    {
        $validated = $request->validate([
            'participant_type' => ['required', 'in:organizer,chairperson,member,guest,presenter,observer'],
            'attendance_status' => ['required', 'in:invited,accepted,declined,present,absent,apology'],
            'remarks' => ['nullable', 'string'],
        ]);

        $participant->update($validated);

        return redirect()->route('meetings.show', $meeting)->with('success', 'Participant updated successfully.');
    }

    public function destroy(Meeting $meeting, MeetingParticipant $participant): RedirectResponse
    {
        $participant->delete();

        return redirect()->route('meetings.show', $meeting)->with('success', 'Participant removed successfully.');
    }
}
