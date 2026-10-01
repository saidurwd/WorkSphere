<?php

namespace Modules\Meetings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingAttachment;
use Modules\Meetings\Services\MeetingAttachmentSynchroniser;

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

            // `$validated` still carries the uploaded `file`, which is not a
            // column; it has already been consumed above.
            $attachment = MeetingAttachment::create(
                collect($validated)->except('file')->all()
            );

            // GAP-048: dual-write into the shared attachments table. No file is
            // copied — both rows point at the same object. The disk recorded is the
            // one actually used; moving to the private disk is Phase 11's job.
            app(MeetingAttachmentSynchroniser::class)
                ->mirror($meeting, $attachment, 'public');
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
