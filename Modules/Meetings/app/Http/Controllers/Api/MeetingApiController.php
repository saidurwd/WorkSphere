<?php

namespace Modules\Meetings\Http\Controllers\Api;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\MeetingResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Meetings\Http\Requests\IndexMeetingRequest;
use Modules\Meetings\Models\Meeting;

/**
 * `/api/v1/meetings` — read only.
 *
 * Visibility is `MeetingController::index`'s predicate, unchanged: without
 * `meeting.view`, a meeting is visible only when the caller organises it or
 * participates in it. `MeetingPolicy::view` also honours the `super-admin` role
 * through `Gate::before`, so the object check and the list agree.
 *
 * Read-only for the same reason as Tasks: the meeting lifecycle runs through
 * `MeetingService`, and an API write path would have to be handed that service
 * rather than reimplemented.
 */
class MeetingApiController extends ApiController
{
    public function index(IndexMeetingRequest $request): AnonymousResourceCollection
    {
        $user = $this->actor($request);

        $query = Meeting::query()
            ->with(['type', 'organizer'])
            ->withCount(['participants', 'agendas', 'actionItems']);

        if (! $this->allows($user, 'meeting.view')) {
            $query->where(function (Builder $inner) use ($user): void {
                $inner->where('organizer_id', $user->id)
                    ->orWhereHas('participants', fn (Builder $p) => $p->where('user_id', $user->id));
            });
        }

        return $this->paginated($this->filter($query, $request), MeetingResource::class, $request);
    }

    public function show(Request $request, Meeting $meeting): MeetingResource
    {
        $this->authorizeAction($this->actor($request), 'view', $meeting);

        return new MeetingResource(
            $meeting->load(['type', 'organizer'])->loadCount(['participants', 'agendas', 'actionItems']),
        );
    }

    /**
     * @param  Builder<Meeting>  $query
     * @return Builder<Meeting>
     */
    protected function filter(Builder $query, IndexMeetingRequest $request): Builder
    {
        if ($request->filled('q')) {
            $term = '%'.$request->string('q')->trim()->toString().'%';

            $query->where(function (Builder $inner) use ($term): void {
                $inner->where('title', 'like', $term)
                    ->orWhere('meeting_no', 'like', $term);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        foreach (['meeting_type_id', 'department_id', 'organizer_id'] as $column) {
            if ($request->filled($column)) {
                $query->where($column, $request->integer($column));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('meeting_date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('meeting_date', '<=', $request->date('date_to'));
        }

        return $query;
    }
}
