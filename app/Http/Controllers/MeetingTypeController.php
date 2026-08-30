<?php

namespace App\Http\Controllers;

use App\Models\MeetingType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingTypeController extends Controller
{
    public function index(): View
    {
        $types = MeetingType::orderBy('sort_order')->paginate(15);

        return view('meetings.types.index', compact('types'));
    }

    public function create(): View
    {
        return view('meetings.types.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:meeting_types,code'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        MeetingType::create($validated);

        return redirect()->route('meetings.types.index')->with('success', 'Meeting type created successfully.');
    }

    public function edit(MeetingType $meetingType): View
    {
        return view('meetings.types.edit', compact('meetingType'));
    }

    public function update(Request $request, MeetingType $meetingType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:meeting_types,code,'.$meetingType->id],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = (int) ($validated['sort_order'] ?? 0);
        $validated['updated_by'] = auth()->id();

        $meetingType->update($validated);

        return redirect()->route('meetings.types.index')->with('success', 'Meeting type updated successfully.');
    }

    public function destroy(MeetingType $meetingType): RedirectResponse
    {
        $meetingType->delete();

        return redirect()->route('meetings.types.index')->with('success', 'Meeting type deleted successfully.');
    }
}
