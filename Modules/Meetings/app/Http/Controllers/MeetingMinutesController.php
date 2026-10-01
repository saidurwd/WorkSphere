<?php

namespace Modules\Meetings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Services\MeetingMinutesService;

class MeetingMinutesController extends Controller
{
    public function __construct(private MeetingMinutesService $minutesService) {}

    public function prepare(Meeting $meeting)
    {
        $this->authorize('manageMinutes', $meeting);

        $minutes = $this->minutesService->prepare($meeting);

        return back()->with('success', 'Minutes prepared.');
    }

    public function submit(Meeting $meeting)
    {
        $this->authorize('submitMinutes', $meeting);

        $minutes = $this->minutesService->submit($meeting);

        return back()->with('success', 'Minutes submitted for approval.');
    }

    public function approve(Meeting $meeting)
    {
        $this->authorize('approveMinutes', $meeting);

        $minutes = $this->minutesService->approve($meeting);

        return back()->with('success', 'Minutes approved.');
    }

    public function publish(Meeting $meeting)
    {
        $this->authorize('publishMinutes', $meeting);

        $minutes = $this->minutesService->publish($meeting);

        return back()->with('success', 'Minutes published.');
    }

    public function returnMinutes(Request $request, Meeting $meeting)
    {
        $this->authorize('approveMinutes', $meeting);

        $validated = $request->validate([
            'comments' => ['required', 'string', 'max:2000'],
        ]);

        $this->minutesService->returnMinutes($meeting, $validated['comments']);

        return back()->with('success', 'Minutes returned for revision.');
    }
}
