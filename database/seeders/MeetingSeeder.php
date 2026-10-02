<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Meetings\Models\MeetingAgenda;
use Modules\Meetings\Models\MeetingAttachment;
use Modules\Meetings\Models\MeetingDecision;
use Modules\Meetings\Models\MeetingDiscussion;
use Modules\Meetings\Models\MeetingMinutesApproval;
use Modules\Meetings\Models\MeetingParticipant;
use Modules\Meetings\Models\MeetingRecurrence;
use Modules\Meetings\Models\MeetingTag;
use Modules\Meetings\Models\MeetingTemplate;
use Modules\Meetings\Models\MeetingTemplateAgenda;
use Modules\Meetings\Models\MeetingType;

/**
 * The meeting register, at demo volume, with everything a meeting record hangs off.
 *
 * A meeting in this application is not one row. The detail screen is assembled
 * from participants, agendas, discussions, decisions, action items, tags,
 * attachments, the minutes approval chain and the version history, so seeding
 * only the `meetings` table produces a register whose every detail page is
 * empty. Each seeded meeting therefore gets the whole graph around it, and the
 * approval chain advances in step with the minutes status rather than at random.
 *
 * Depends on {@see MeetingTypeSeeder} and {@see MeetingTagSeeder} for its
 * reference data, and on the organisation seeders for people and departments.
 */
class MeetingSeeder extends Seeder
{
    use WithoutModelEvents;

    private DeterministicSequence $random;

    /**
     * @var list<string>
     */
    private const TITLES = [
        'Sprint Planning', 'Sprint Retrospective', 'Product Review', 'Budget Review',
        'Security Audit Review', 'Client Onboarding', 'Architecture Review',
        'Release Coordination', 'Incident Postmortem', 'Roadmap Planning',
        'Vendor Evaluation', 'Compliance Review', 'Performance Review',
        'Infrastructure Upgrade Planning', 'Feature Kickoff', 'Quarterly Business Review',
        'Change Advisory Board', 'Data Governance Review', 'UX Research Sync',
        'Incident Response Drill', 'Stakeholder Update', 'Department All-Hands',
        'Payroll Review', 'Procurement Sync', 'Hiring Panel',
        'Risk Register Review', 'Service Level Review', 'Annual Planning Workshop',
    ];

    /**
     * @var list<string>
     */
    private const AGENDA_ITEMS = [
        'Review the previous minutes and outstanding actions',
        'Progress update against the plan',
        'Risks, blockers and escalations',
        'Budget position and forecast',
        'Decisions required from this group',
        'Open floor',
    ];

    /**
     * @var list<string>
     */
    private const DECISIONS = [
        'Approved the revised delivery plan',
        'Deferred pending a costed proposal',
        'Approved the additional headcount request',
        'Noted the report; no action required',
        'Approved the vendor shortlist',
        'Further discussion required on scope',
        'Approved the change to the service level',
        'Rejected the proposal in favour of the existing contract',
    ];

    /**
     * @var list<string>
     */
    private const ACTION_TITLES = [
        'Circulate the revised plan to the department',
        'Prepare the costed proposal',
        'Update the risk register',
        'Confirm the vendor availability in writing',
        'Re-baseline the forecast',
        'Close the outstanding audit findings',
        'Schedule the follow-up review',
        'Obtain sign-off from the steering group',
        'Publish the revised procedure',
        'Complete the access recertification for this service',
        'Draft the communication to affected teams',
        'Validate the figures against the ledger',
    ];

    /**
     * @var list<array{0: string, 1: list<string>}>
     */
    private const TEMPLATES = [
        ['Project Status Review', ['Progress since the last review', 'Plan for the next period', 'Risks and blockers', 'Decisions and actions']],
        ['Incident Postmortem', ['Timeline of the incident', 'Impact and scope', 'Root cause analysis', 'Corrective and preventive actions', 'Owner and due date for each action']],
        ['Sprint Retrospective', ['What went well', 'What did not go well', 'Ideas for improvement', 'Actions selected']],
        ['Quarterly Business Review', ['Scorecard against the targets', 'Financial summary', 'Delivery status', 'Market and client feedback', 'Priorities for the next quarter']],
        ['Monthly Operations Meeting', ['Service level performance', 'Incident summary', 'Capacity and demand', 'Vendor performance', 'Action review']],
        ['Change Advisory Board', ['Change summary', 'Risk assessment', 'Business justification', 'Implementation plan', 'Decision']],
        ['Compliance Review', ['Regulatory updates', 'Obligation status', 'Audit findings', 'Remediation progress']],
        ['One to One', ['Progress since the last meeting', 'Development goals', 'Support needed', 'Forward plan']],
        ['Client Onboarding', ['Introduction and team', 'Scope and deliverables', 'Timeline and milestones', 'Communication plan', 'Next steps']],
        ['Department All-Hands', ['Headline results', 'Department updates', 'Announcements', 'Questions']],
    ];

    public function run(): void
    {
        $this->random = new DeterministicSequence;

        $this->call([
            MeetingTypeSeeder::class,
            MeetingTagSeeder::class,
        ]);

        $users = User::query()->with('employee')->where('status', 'active')->get();

        if ($users->count() < 3) {
            $this->command?->warn('  Meetings: skipped, fewer than three active accounts exist.');

            return;
        }

        $types = MeetingType::query()->get();
        $tags = MeetingTag::query()->get();
        $locations = Location::query()->get();

        $templates = $this->seedTemplates($types, $users);
        $meetings = $this->seedMeetings($users, $types, $locations, $templates);

        $this->seedRelations($meetings, $users, $tags);
        $this->seedRecurrences($meetings);
        $this->seedVersions($meetings, $users);
        $this->seedNotificationLogs($meetings, $users);

        $this->command?->info(sprintf(
            '  Meetings: %d (agendas %d, action items %d, participants %d, decisions %d, templates %d)',
            Meeting::query()->count(),
            MeetingAgenda::query()->count(),
            MeetingActionItem::query()->count(),
            MeetingParticipant::query()->count(),
            MeetingDecision::query()->count(),
            MeetingTemplate::query()->count(),
        ));
    }

    /**
     * @param  Collection<int, MeetingType>  $types
     * @param  Collection<int, User>  $users
     * @return Collection<int, MeetingTemplate>
     */
    private function seedTemplates(
        Collection $types,
        Collection $users,
    ): Collection {
        $wanted = (int) config('seed.volumes.meeting_templates', 10);
        $owner = $users->random();

        $templates = collect();

        foreach (array_slice(self::TEMPLATES, 0, $wanted) as [$name, $agendas]) {
            $type = $types->random();

            $template = MeetingTemplate::query()->firstOrCreate(
                ['name' => $name],
                [
                    'meeting_type_id' => $type->id,
                    'description' => 'Standard structure used for '.$name.' meetings.',
                    'default_duration' => $this->random->pick([30, 45, 60, 90]),
                    'default_location' => $this->random->pick(['Board Room', 'Conference Hall A', 'Online', 'Training Room']),
                    'default_priority' => $this->random->pick(['normal', 'normal', 'important', 'urgent'], [50, 20, 20, 10]),
                    'is_active' => true,
                    'created_by' => $owner->id,
                    'updated_by' => $owner->id,
                ],
            );

            if ($template->agendaItems()->count() === 0) {
                foreach ($agendas as $order => $title) {
                    MeetingTemplateAgenda::query()->create([
                        'template_id' => $template->id,
                        'title' => $title,
                        'description' => null,
                        'sort_order' => $order + 1,
                    ]);
                }
            }

            $templates->push($template);
        }

        return $templates;
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  Collection<int, MeetingType>  $types
     * @param  Collection<int, Location>  $locations
     * @param  Collection<int, MeetingTemplate>  $templates
     * @return Collection<int, Meeting>
     */
    private function seedMeetings(
        Collection $users,
        Collection $types,
        Collection $locations,
        Collection $templates,
    ): Collection {
        $wanted = (int) config('seed.volumes.meetings', 150);

        if (Meeting::query()->count() >= $wanted) {
            return Meeting::query()->orderBy('id')->get();
        }

        $now = Carbon::now();
        $statuses = ['scheduled', 'in_progress', 'completed', 'cancelled', 'postponed'];
        $statusWeights = [26, 14, 40, 10, 10];
        $priorities = ['normal', 'important', 'urgent'];
        $priorityWeights = [56, 32, 12];
        $minutesStatuses = ['draft', 'prepared', 'submitted', 'under_review', 'approved', 'published'];
        $minutesWeights = [18, 18, 18, 14, 16, 16];

        $meetings = collect();

        for ($i = 1; $i <= $wanted; $i++) {
            $organizer = $users->random();
            $chairperson = $this->another($users, $organizer->id);

            // Two thirds in the past, the rest upcoming: a register with only
            // completed meetings has nothing for the "next meeting" widgets and
            // one with only future meetings has nothing to show for history.
            $meetingDate = $this->random->chance(66)
                ? $now->copy()->subDays($this->random->between(1, 180))
                : $now->copy()->addDays($this->random->between(1, 75));

            $status = $this->random->pick($statuses, $statusWeights);
            $minutesStatus = $status === 'completed'
                ? $this->minutesStatus($meetingDate, $now, $minutesStatuses, $minutesWeights)
                : $this->random->pick(['draft', 'prepared'], [70, 30]);

            $startHour = $this->random->between(9, 17);
            $startMinute = $this->random->pick([0, 15, 30, 45]);
            $duration = $this->random->pick([30, 45, 60, 60, 90, 120]);

            $preparedAt = $minutesStatus !== 'draft'
                ? $meetingDate->copy()->addDay()->setTime(11, 0)
                : null;
            $approvedAt = in_array($minutesStatus, ['approved', 'published'], true)
                ? $preparedAt?->copy()->addDay()
                : null;

            $meetings->push(Meeting::query()->create([
                'meeting_no' => 'MTG-'.($meetingDate->year).'-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'title' => self::TITLES[$this->random->between(0, count(self::TITLES) - 1)],
                'meeting_type_id' => $types->random()->id,
                'organizer_id' => $organizer->id,
                'chairperson_id' => $chairperson->id,
                'department_id' => $organizer->employee?->department_id,
                'location' => $this->random->pick(['Board Room', 'Conference Hall A', 'Conference Hall B', 'Online', 'Training Room', 'Chittagong Hub']),
                'location_id' => $locations->random()->id,
                'template_id' => $this->random->chance(35) ? $templates->random()->id : null,
                'meeting_date' => $meetingDate->toDateString(),
                'start_time' => sprintf('%02d:%02d:00', $startHour, $startMinute),
                'end_time' => sprintf(
                    '%02d:%02d:00',
                    (int) floor(($startHour * 60 + $startMinute + $duration) / 60) % 24,
                    ($startMinute + $duration) % 60,
                ),
                'timezone' => 'Asia/Dhaka',
                'status' => $status,
                'priority' => $this->random->pick($priorities, $priorityWeights),
                'description' => 'Convened to review progress, resolve blockers and record decisions.',
                'agenda' => 'Circulated in advance to all invitees.',
                'minutes_status' => $minutesStatus,
                'minutes_prepared_by' => $minutesStatus !== 'draft' ? $organizer->id : null,
                'minutes_prepared_at' => $preparedAt,
                'approved_by' => $approvedAt !== null ? $chairperson->id : null,
                'approved_at' => $approvedAt,
                'published_at' => $minutesStatus === 'published' ? $approvedAt?->copy() : null,
                'created_by' => $organizer->id,
                'updated_by' => $organizer->id,
            ]));
        }

        return $meetings;
    }

    /**
     * Minutes status advances with how long ago the meeting was.
     *
     * A meeting held yesterday is still in draft; one from four months ago has
     * been through the whole chain. Drawn independently the register would show
     * minutes published for the meeting scheduled for next Tuesday.
     *
     * @param  list<string>  $statuses
     * @param  list<int>  $weights
     */
    private function minutesStatus(Carbon $meetingDate, Carbon $now, array $statuses, array $weights): string
    {
        $daysAgo = $meetingDate->diffInDays($now);

        return match (true) {
            $daysAgo < 2 => 'draft',
            $daysAgo < 5 => $this->random->pick(['draft', 'prepared'], [40, 60]),
            $daysAgo < 10 => $this->random->pick(['prepared', 'submitted'], [50, 50]),
            $daysAgo < 20 => $this->random->pick(['submitted', 'under_review', 'approved'], [40, 35, 25]),
            default => $this->random->pick($statuses, $weights),
        };
    }

    /**
     * @param  Collection<int, Meeting>  $meetings
     * @param  Collection<int, User>  $users
     * @param  Collection<int, MeetingTag>  $tags
     */
    private function seedRelations(
        Collection $meetings,
        Collection $users,
        Collection $tags,
    ): void {
        foreach ($meetings as $meeting) {
            $this->seedParticipants($meeting, $users);
            $agendaIds = $this->seedAgenda($meeting, $users);
            $discussionIds = $this->seedDiscussions($meeting, $users, $agendaIds);
            $decisionIds = $this->seedDecisions($meeting, $users, $agendaIds, $discussionIds);
            $this->seedActionItems($meeting, $users, $agendaIds, $discussionIds, $decisionIds);
            $this->seedTags($meeting, $tags);
            $this->seedAttachments($meeting, $users);
            $this->seedApprovals($meeting, $users);
        }
    }

    /**
     * A member of the collection who is not the one supplied.
     *
     * @template T of \Illuminate\Database\Eloquent\Model
     *
     * @param  Collection<int, T>  $collection
     * @return T
     */
    private function another(Collection $collection, int $currentId): mixed
    {
        if ($collection->count() < 2) {
            return $collection->first();
        }

        do {
            $candidate = $collection->random();
        } while ($candidate->id === $currentId);

        return $candidate;
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function seedParticipants(Meeting $meeting, Collection $users): void
    {
        $attendees = $this->random->sample($users->pluck('id')->all(), $this->random->between(4, 9));

        // The organiser and the chairperson are added first, so they keep their
        // own participant types. `array_unique` then guards the pair: the
        // table is unique on (meeting_id, user_id) and the two can be drawn from
        // overlapping samples.
        $meetingUserIds = array_values(array_unique(array_merge(
            [$meeting->organizer_id, $meeting->chairperson_id],
            $attendees,
        )));

        foreach ($meetingUserIds as $userId) {
            $participantType = match (true) {
                $userId === $meeting->organizer_id => 'organizer',
                $userId === $meeting->chairperson_id => 'chairperson',
                default => $this->random->pick(['member', 'member', 'member', 'presenter', 'guest', 'observer'], [46, 46, 46, 25, 15, 8]),
            };

            // Attendance only exists for a meeting that has already happened, and
            // a completed meeting where nobody attended contradicts itself.
            $attendance = match (true) {
                $meeting->status === 'scheduled' || $meeting->status === 'postponed' => $this->random->pick(['invited', 'accepted', 'accepted', 'declined'], [30, 45, 45, 10]),
                $meeting->status === 'cancelled' => $this->random->pick(['invited', 'declined', 'apology'], [40, 40, 20]),
                default => $this->random->pick(['present', 'present', 'present', 'present', 'absent', 'apology'], [30, 30, 30, 30, 60, 30]),
            };

            MeetingParticipant::query()->create([
                'meeting_id' => $meeting->id,
                'user_id' => $userId,
                'participant_type' => $participantType,
                'attendance_status' => $attendance,
                'invited_at' => Carbon::parse($meeting->meeting_date)->subDays($this->random->between(2, 14))->setTime(9, 30),
                'responded_at' => in_array($attendance, ['accepted', 'declined', 'present', 'absent', 'apology'], true)
                    ? Carbon::parse($meeting->meeting_date)->subDays($this->random->between(1, 5))->setTime(11, 0)
                    : null,
                'joined_at' => in_array($attendance, ['present', 'absent', 'apology'], true) && $meeting->status === 'completed'
                    ? $meeting->start_time
                    : null,
                'left_at' => $attendance === 'present' && $meeting->status === 'completed' ? $meeting->end_time : null,
                'remarks' => $this->random->chance(15) ? 'Joined from the regional office.' : null,
            ]);
        }
    }

    /**
     * @param  Collection<int, User>  $users
     * @return list<int>
     */
    private function seedAgenda(Meeting $meeting, Collection $users): array
    {
        $count = $this->random->between(3, 6);
        $agendaIds = [];

        for ($no = 1; $no <= $count; $no++) {
            $agendaStatus = match ($meeting->status) {
                'completed' => $this->random->pick(['completed', 'completed', 'completed', 'skipped'], [55, 55, 55, 15]),
                'in_progress' => $no <= 2 ? 'completed' : 'in_progress',
                default => 'pending',
            };

            $agenda = MeetingAgenda::query()->create([
                'meeting_id' => $meeting->id,
                'agenda_no' => $no,
                'title' => self::AGENDA_ITEMS[($no - 1) % count(self::AGENDA_ITEMS)],
                'description' => null,
                'presented_by' => $users->random()->id,
                'estimated_minutes' => $this->random->pick([5, 10, 15, 20, 30]),
                'status' => $agendaStatus,
                'sort_order' => $no,
            ]);

            $agendaIds[] = $agenda->id;
        }

        return $agendaIds;
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  list<int>  $agendaIds
     * @return list<int>
     */
    private function seedDiscussions(
        Meeting $meeting,
        Collection $users,
        array $agendaIds,
    ): array {
        if ($meeting->status === 'scheduled') {
            return [];
        }

        $ids = [];

        foreach ($agendaIds as $agendaId) {
            foreach (range(1, $this->random->between(1, 3)) as $order) {
                $discussion = MeetingDiscussion::query()->create([
                    'meeting_id' => $meeting->id,
                    'agenda_id' => $agendaId,
                    'topic' => $this->random->pick([
                        'Impact on the current delivery plan',
                        'Options considered and cost',
                        'Dependencies on other teams',
                        'Risk if the decision is delayed',
                        'Feedback from the client',
                        'Position after the last review',
                    ]),
                    'discussion' => 'The group reviewed the position in detail. The main constraint is the dependency on the upstream team, and the cost of each option was compared against the benefit expected.',
                    'key_points' => "Two options remain viable.\nThe third is ruled out on cost.",
                    'discussion_by' => $users->random()->id,
                    'sort_order' => $order,
                    'created_by' => $meeting->organizer_id,
                    'updated_by' => $meeting->organizer_id,
                ]);

                $ids[] = $discussion->id;
            }
        }

        return $ids;
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  list<int>  $agendaIds
     * @param  list<int>  $discussionIds
     * @return list<int>
     */
    private function seedDecisions(
        Meeting $meeting,
        Collection $users,
        array $agendaIds,
        array $discussionIds,
    ): array {
        if ($discussionIds === [] || $meeting->status === 'scheduled') {
            return [];
        }

        $ids = [];

        foreach ($this->random->sample(array_values(array_unique($agendaIds)), $this->random->between(1, min(3, count(array_unique($agendaIds))))) as $agendaId) {
            $decision = MeetingDecision::query()->create([
                'meeting_id' => $meeting->id,
                'agenda_id' => $agendaId,
                'discussion_id' => $discussionIds[$this->random->between(0, count($discussionIds) - 1)],
                'decision_no' => count($ids) + 1,
                'decision_title' => self::DECISIONS[$this->random->between(0, count(self::DECISIONS) - 1)],
                'decision_description' => 'The decision was taken after considering the cost, the delivery risk and the input from the affected teams.',
                'decision_type' => $this->random->pick(
                    ['approved', 'approved', 'noted', 'deferred', 'rejected', 'further_discussion_required'],
                    [34, 34, 14, 10, 5, 3],
                ),
                'decision_status' => 'active',
                'decision_date' => $meeting->meeting_date,
                'approved_by' => $meeting->chairperson_id,
                'effective_date' => Carbon::parse($meeting->meeting_date)->addDays($this->random->between(1, 14))->toDateString(),
                'created_by' => $meeting->organizer_id,
                'updated_by' => $meeting->organizer_id,
            ]);

            $ids[] = $decision->id;
        }

        return $ids;
    }

    /**
     * @param  Collection<int, User>  $users
     * @param  list<int>  $agendaIds
     * @param  list<int>  $discussionIds
     * @param  list<int>  $decisionIds
     */
    private function seedActionItems(
        Meeting $meeting,
        Collection $users,
        array $agendaIds,
        array $discussionIds,
        array $decisionIds,
    ): void {
        $count = $this->random->between(1, 4);

        for ($no = 1; $no <= $count; $no++) {
            $startDate = Carbon::parse($meeting->meeting_date);
            $status = $this->random->pick(['open', 'in_progress', 'completed', 'completed', 'on_hold', 'cancelled'], [26, 26, 16, 16, 10, 6]);
            $completion = match ($status) {
                'completed' => 100,
                'in_progress' => $this->random->between(20, 80),
                'on_hold' => $this->random->between(10, 40),
                'open' => 0,
                default => $this->random->between(0, 30),
            };
            $completedAt = $status === 'completed' ? $startDate->copy()->addDays($this->random->between(1, 20))->setTime(16, 0) : null;
            $assignee = $users->random();

            MeetingActionItem::query()->create([
                'meeting_id' => $meeting->id,
                'agenda_id' => $agendaIds[$this->random->between(0, count($agendaIds) - 1)],
                'discussion_id' => $discussionIds === [] ? null : $discussionIds[$this->random->between(0, count($discussionIds) - 1)],
                'decision_id' => $decisionIds === [] ? null : $decisionIds[$this->random->between(0, count($decisionIds) - 1)],
                'action_no' => $no,
                'title' => self::ACTION_TITLES[$this->random->between(0, count(self::ACTION_TITLES) - 1)],
                'description' => 'To be completed and reported back at the next meeting.',
                'assigned_to' => $assignee->id,
                'assigned_department_id' => $assignee->employee?->department_id,
                'priority' => $this->random->pick(['low', 'medium', 'high', 'critical'], [18, 42, 30, 10]),
                'start_date' => $startDate->toDateString(),
                'due_date' => $startDate->copy()->addDays($this->random->between(3, 30))->toDateString(),
                'status' => $status,
                'completion_percentage' => $completion,
                'completed_at' => $completedAt,
                'completed_by' => $completedAt === null ? null : $assignee->id,
                'remarks' => $this->random->chance(25) ? 'Progress reported at the follow-up meeting.' : null,
                'created_by' => $meeting->organizer_id,
                'updated_by' => $meeting->organizer_id,
            ]);
        }
    }

    /**
     * @param  Collection<int, MeetingTag>  $tags
     */
    private function seedTags(Meeting $meeting, Collection $tags): void
    {
        foreach ($this->random->sample($tags->pluck('id')->all(), $this->random->between(1, 3)) as $tagId) {
            $meeting->tags()->syncWithoutDetaching([$tagId]);
        }
    }

    /**
     * @param  Collection<int, User>  $users
     */
    private function seedAttachments(Meeting $meeting, Collection $users): void
    {
        $files = ['agenda.pdf', 'minutes.pdf', 'presentation.pdf', 'financial-summary.xlsx', 'risk-register.xlsx', 'attendance-sheet.pdf'];

        foreach ($this->random->sample($files, $this->random->between(0, 3)) as $file) {
            MeetingAttachment::query()->create([
                'meeting_id' => $meeting->id,
                'file_name' => $file,
                'file_path' => 'meetings/'.$meeting->id.'/'.$file,
                'file_type' => str_ends_with($file, '.xlsx') ? 'application/vnd.ms-excel' : 'application/pdf',
                'file_size' => $this->random->between(48_000, 6_000_000),
                'uploaded_by' => $users->random()->id,
                'description' => 'Circulated before the meeting.',
            ]);
        }
    }

    /**
     * The approval chain, consistent with where the minutes have reached.
     *
     * @param  Collection<int, User>  $users
     */
    private function seedApprovals(Meeting $meeting, Collection $users): void
    {
        if (! in_array($meeting->minutes_status, ['submitted', 'under_review', 'approved', 'published'], true)) {
            return;
        }

        $reached = match ($meeting->minutes_status) {
            'submitted' => 0,
            'under_review' => 1,
            default => 2,
        };

        $chain = $this->random->sample($users->pluck('id')->all(), min(3, $users->count()));

        foreach (array_values($chain) as $step => $approverId) {
            $status = match (true) {
                $step < $reached => 'approved',
                $step === $reached => 'pending',
                default => 'pending',
            };

            MeetingMinutesApproval::query()->create([
                'meeting_id' => $meeting->id,
                'step_no' => $step + 1,
                'approver_id' => $approverId,
                'status' => $status,
                'comments' => $status === 'approved' ? 'Approved without amendment.' : null,
                'action_at' => $status === 'approved' ? $meeting->approved_at ?? $meeting->meeting_date : null,
            ]);
        }
    }

    /**
     * Recurring meetings — the standing forums rather than the one-off reviews.
     *
     * @param  Collection<int, Meeting>  $meetings
     */
    private function seedRecurrences(Collection $meetings): void
    {
        $wanted = (int) config('seed.volumes.meeting_recurrences', 40);

        if (MeetingRecurrence::query()->count() >= $wanted) {
            return;
        }

        $types = ['weekly', 'biweekly', 'monthly', 'quarterly', 'yearly'];
        $weights = [30, 22, 26, 14, 8];

        // Indexed by `dayOfWeek`, which runs Sunday (0) to Saturday (6).
        $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        foreach ($meetings->take($wanted) as $meeting) {
            $type = $this->random->pick($types, $weights);
            $startDate = Carbon::parse($meeting->meeting_date);

            MeetingRecurrence::query()->create([
                'meeting_id' => $meeting->id,
                'recurrence_type' => $type,
                'recurrence_interval' => 1,
                'day_of_week' => in_array($type, ['weekly', 'biweekly'], true) ? $days[$startDate->dayOfWeek] ?? null : null,
                'day_of_month' => in_array($type, ['monthly', 'quarterly', 'yearly'], true) ? $startDate->day : null,
                'start_date' => $startDate->toDateString(),
                'end_date' => $startDate->copy()->addYear()->toDateString(),
                'occurrences' => $this->random->between(6, 52),
                'next_occurrence' => $this->nextOccurrence($type, $startDate),
                'is_active' => $this->random->chance(75),
            ]);
        }
    }

    private function nextOccurrence(string $type, Carbon $startDate): string
    {
        $next = match ($type) {
            'weekly' => $startDate->copy()->addWeek(),
            'biweekly' => $startDate->copy()->addWeeks(2),
            'monthly' => $startDate->copy()->addMonth(),
            'quarterly' => $startDate->copy()->addMonths(3),
            'yearly' => $startDate->copy()->addYear(),
            default => $startDate->copy()->addDay(),
        };

        return $next->setTime(10, 0)->toDateTimeString();
    }

    /**
     * @param  Collection<int, Meeting>  $meetings
     * @param  Collection<int, User>  $users
     */
    private function seedVersions(Collection $meetings, Collection $users): void
    {
        $wanted = (int) config('seed.volumes.meeting_versions', 60);

        if (DB::table('meeting_versions')->count() >= $wanted) {
            return;
        }

        $rows = [];
        $summaries = [
            'Agenda reordered',
            'Participant list updated',
            'Minutes drafted',
            'Minutes submitted for approval',
            'Minutes approved',
            'Minutes published',
            'Location changed',
        ];

        foreach ($meetings as $meeting) {
            if (count($rows) >= $wanted) {
                break;
            }

            foreach (range(1, $this->random->between(1, 2)) as $version) {
                $rows[] = [
                    'meeting_id' => $meeting->id,
                    'version_no' => $version,
                    'snapshot_data' => json_encode([
                        'title' => $meeting->title,
                        'status' => $meeting->status,
                        'minutes_status' => $meeting->minutes_status,
                        'meeting_date' => $meeting->meeting_date,
                    ]),
                    'change_summary' => $this->random->pick($summaries),
                    'created_by' => $meeting->organizer_id,
                    'created_at' => Carbon::parse($meeting->meeting_date)->addDays($version),
                ];

                if (count($rows) >= $wanted) {
                    break;
                }
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('meeting_versions')->insert($chunk);
        }
    }

    /**
     * @param  Collection<int, Meeting>  $meetings
     * @param  Collection<int, User>  $users
     */
    private function seedNotificationLogs(
        Collection $meetings,
        Collection $users,
    ): void {
        $wanted = (int) config('seed.volumes.meeting_notification_logs', 320);

        if (DB::table('meeting_notification_logs')->count() >= $wanted) {
            return;
        }

        $userIds = $users->pluck('id')->all();
        $rows = [];

        while (count($rows) < $wanted) {
            $meeting = $meetings->random();
            $at = Carbon::parse($meeting->meeting_date)->subDays($this->random->between(1, 10))->setTime(9, 0);
            $status = $this->random->pick(['sent', 'sent', 'sent', 'sent', 'failed'], [70, 70, 70, 70, 8]);

            $rows[] = [
                'meeting_id' => $meeting->id,
                'user_id' => $userIds[$this->random->between(0, count($userIds) - 1)],
                'channel' => $this->random->pick(['EMAIL', 'IN_APP', 'SMS'], [45, 50, 5]),
                'notification_type' => $this->random->pick(['INVITATION', 'REMINDER', 'AGENDA_CIRculated', 'MINUTES_PUBLISHED'], [45, 35, 12, 8]),
                'scheduled_at' => $at,
                'sent_at' => $status === 'sent' ? $at->copy()->addMinutes($this->random->between(1, 30)) : null,
                'status' => $status,
                'subject' => 'Invitation: '.$meeting->title,
                'message' => 'You have been invited to '.$meeting->title.' on '.$meeting->meeting_date.'.',
                'retry_count' => $status === 'failed' ? $this->random->between(1, 2) : 0,
                'error_message' => $status === 'failed' ? 'Mailbox unavailable at the time of sending.' : null,
                'created_at' => $at,
                'updated_at' => $at,
            ];
        }

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('meeting_notification_logs')->insert($chunk);
        }
    }
}
