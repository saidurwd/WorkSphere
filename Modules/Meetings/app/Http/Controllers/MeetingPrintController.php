<?php

namespace Modules\Meetings\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;
use Modules\Meetings\Models\Meeting;

/**
 * The printable meeting sheet. It is a standalone page (its own layout, made to be
 * printed or saved as PDF), so it stays a plain route rather than a panel page.
 */
class MeetingPrintController extends Controller
{
    public function __invoke(Meeting $meeting): View
    {
        $this->authorize('print', $meeting);

        $meeting->load([
            'type',
            'organizer',
            'chairperson',
            'department',
            'participants.user',
            'agendas.presentedBy',
            'agendas.discussions',
            'agendas.discussions.decisions',
            'decisions',
            'actionItems.assignedTo',
            'actionItems.assignedDepartment',
            'actionItems.task',
            'attachments',
            'versions',
            'tags',
            'recurrence',
            'minutesApprovals.approver',
        ]);

        return view('meetings.print', compact('meeting'));
    }
}
