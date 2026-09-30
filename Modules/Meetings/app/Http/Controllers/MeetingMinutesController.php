<?php

namespace Modules\Meetings\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Services\MeetingMinutesService;
use Illuminate\Http\Request;

class MeetingMinutesController extends Controller
{
    public function __construct(private MeetingMinutesService $minutesService) {}

    public function prepare(Meeting $meeting)
    {
        $minutes = $this->minutesService->prepare($meeting);

        return back()->with('success', 'Minutes prepared.');
    }

    public function submit(Meeting $meeting)
    {
        $minutes = $this->minutesService->submit($meeting);

        return back()->with('success', 'Minutes submitted for approval.');
    }

    public function approve(Meeting $meeting)
    {
        $minutes = $this->minutesService->approve($meeting);

        return back()->with('success', 'Minutes approved.');
    }

    public function publish(Meeting $meeting)
    {
        $minutes = $this->minutesService->publish($meeting);

        return back()->with('success', 'Minutes published.');
    }

    public function returnMinutes(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'comments' => ['required', 'string', 'max:2000'],
        ]);

        $this->minutesService->returnMinutes($meeting, $validated['comments']);

        return back()->with('success', 'Minutes returned for revision.');
    }
}
