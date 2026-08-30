<?php

namespace App\Http\Controllers;

use App\Models\MeetingTag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingTagController extends Controller
{
    public function index(): View
    {
        $tags = MeetingTag::orderBy('name')->paginate(15);

        return view('meetings.tags.index', compact('tags'));
    }

    public function create(): View
    {
        return view('meetings.tags.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:meeting_tags,name'],
            'color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        MeetingTag::create($validated);

        return redirect()->route('meetings.tags.index')->with('success', 'Meeting tag created successfully.');
    }

    public function edit(MeetingTag $meetingTag): View
    {
        return view('meetings.tags.edit', compact('meetingTag'));
    }

    public function update(Request $request, MeetingTag $meetingTag): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:meeting_tags,name,'.$meetingTag->id],
            'color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $meetingTag->update($validated);

        return redirect()->route('meetings.tags.index')->with('success', 'Meeting tag updated successfully.');
    }

    public function destroy(MeetingTag $meetingTag): RedirectResponse
    {
        $meetingTag->delete();

        return redirect()->route('meetings.tags.index')->with('success', 'Meeting tag deleted successfully.');
    }
}
