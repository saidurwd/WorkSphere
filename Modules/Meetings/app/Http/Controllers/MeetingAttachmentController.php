<?php

namespace Modules\Meetings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingAttachment;

class MeetingAttachmentController extends Controller
{
    public function index(Meeting $meeting): View
    {
        $attachments = $meeting->attachments()->paginate(15);

        return view('meetings.attachments.index', compact('meeting', 'attachments'));
    }

    public function create(Meeting $meeting): View
    {
        $this->authorize('update', $meeting);

        return view('meetings.attachments.create', compact('meeting'));
    }

    public function store(Request $request, Meeting $meeting): RedirectResponse
    {
        $this->authorize('update', $meeting);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'description' => ['nullable', 'string'],
        ]);

        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('meeting-attachments', 'public');
            $validated['file_path'] = $path;
            $validated['file_name'] = $request->file('file')->getClientOriginalName();
            $validated['file_type'] = $request->file('file')->getClientMimeType();
            $validated['file_size'] = $request->file('file')->getSize();
            $validated['meeting_id'] = $meeting->id;
            $validated['uploaded_by'] = auth()->id();

            MeetingAttachment::create($validated);
        }

        return redirect()->route('meetings.show', $meeting)->with('success', 'Attachment uploaded successfully.');
    }

    public function destroy(Meeting $meeting, MeetingAttachment $attachment): RedirectResponse
    {
        $this->authorize('update', $meeting);

        $attachment->delete();

        return redirect()->route('meetings.show', $meeting)->with('success', 'Attachment deleted successfully.');
    }
}
