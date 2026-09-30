<?php

namespace Database\Seeders;

use Modules\Meetings\Models\Meeting;
use Modules\Meetings\Models\MeetingActionItem;
use Modules\Meetings\Models\MeetingAgenda;
use Modules\Meetings\Models\MeetingAttachment;
use Modules\Meetings\Models\MeetingDecision;
use Modules\Meetings\Models\MeetingDiscussion;
use Modules\Meetings\Models\MeetingMinutesApproval;
use Modules\Meetings\Models\MeetingParticipant;
use Modules\Meetings\Models\MeetingTag;
use Modules\Meetings\Models\MeetingType;
use App\Models\User;
use Illuminate\Database\Seeder;

class MeetingSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::inRandomOrder()->take(10)->get();
        $types = MeetingType::inRandomOrder()->take(5)->get();
        $tags = MeetingTag::inRandomOrder()->take(6)->get();

        $titles = [
            'Sprint Planning',
            'Product Review',
            'Budget Review',
            'Security Audit',
            'Client Onboarding',
            'Architecture Review',
            'Release Coordination',
            'Incident Postmortem',
            'Roadmap Planning',
            'Vendor Evaluation',
            'Compliance Review',
            'Performance Review',
            'Infrastructure Upgrade',
            'Feature Kickoff',
            'Quarterly Business Review',
            'Change Advisory',
            'Data Governance',
            'UX Research Sync',
            'Incident Response Drill',
            'Stakeholder Update',
        ];

        $locations = ['Board Room', 'Conference Hall', 'Online', 'Main Office', 'Training Room', 'Executive Suite'];

        $statuses = ['scheduled', 'in_progress', 'completed', 'cancelled', 'postponed'];
        $statusWeights = [30, 20, 30, 10, 10];

        $priorities = ['normal', 'important', 'urgent'];
        $priorityWeights = [50, 35, 15];

        $minutesStatuses = ['draft', 'prepared', 'submitted', 'approved', 'published'];
        $minutesWeights = [20, 20, 20, 20, 20];

        $participantTypes = ['organizer', 'chairperson', 'member', 'guest', 'presenter', 'observer'];
        $attendanceStatuses = ['invited', 'accepted', 'declined', 'present', 'absent', 'apology'];

        $decisionTypes = ['approved', 'rejected', 'deferred', 'noted', 'further_discussion_required'];
        $actionStatuses = ['open', 'in_progress', 'completed', 'on_hold'];

        for ($i = 1; $i <= 20; $i++) {
            $type = $types->random();
            $organizer = $users->random();
            $chairperson = $users->random();
            $meetingDate = fake()->dateTimeBetween('-2 months', '+1 month');
            $startTime = fake()->time('H:i');
            $endTime = fake()->time('H:i');
            $status = $this->weightedRandom($statuses, $statusWeights);
            $priority = $this->weightedRandom($priorities, $priorityWeights);
            $minutesStatus = $this->weightedRandom($minutesStatuses, $minutesWeights);

            $meeting = Meeting::create([
                'meeting_no' => 'MTG-2026-'.str_pad($i, 5, '0', STR_PAD_LEFT),
                'title' => $titles[$i - 1].' #'.$i,
                'meeting_type_id' => $type->id,
                'organizer_id' => $organizer->id,
                'chairperson_id' => $chairperson->id,
                'department_id' => $organizer->employee?->department_id,
                'location' => fake()->randomElement($locations),
                'meeting_date' => $meetingDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => 'UTC',
                'status' => $status,
                'priority' => $priority,
                'description' => fake()->sentence(),
                'agenda' => fake()->paragraph(),
                'minutes_status' => $minutesStatus,
                'minutes_prepared_by' => $minutesStatus !== 'draft' ? $organizer->id : null,
                'minutes_prepared_at' => $minutesStatus !== 'draft' ? $meetingDate : null,
                'approved_by' => in_array($minutesStatus, ['approved', 'published']) ? $chairperson->id : null,
                'approved_at' => in_array($minutesStatus, ['approved', 'published']) ? $meetingDate : null,
                'published_at' => $minutesStatus === 'published' ? $meetingDate : null,
                'created_by' => $organizer->id,
                'updated_by' => $organizer->id,
            ]);

            $meetingUsers = $users->unique('id')->shuffle()->take(rand(3, 6));

            foreach ($meetingUsers as $participant) {
                MeetingParticipant::create([
                    'meeting_id' => $meeting->id,
                    'user_id' => $participant->id,
                    'participant_type' => $participant->id === $organizer->id ? 'organizer' : ($participant->id === $chairperson->id ? 'chairperson' : fake()->randomElement(['member', 'guest', 'presenter', 'observer'])),
                    'attendance_status' => fake()->randomElement($attendanceStatuses),
                    'invited_at' => now(),
                ]);
            }

            $agendaCount = rand(2, 5);
            for ($a = 1; $a <= $agendaCount; $a++) {
                $agenda = MeetingAgenda::create([
                    'meeting_id' => $meeting->id,
                    'agenda_no' => $a,
                    'title' => fake()->sentence(3),
                    'description' => fake()->sentence(),
                    'sort_order' => $a,
                ]);

                $discussionCount = rand(1, 3);
                for ($d = 1; $d <= $discussionCount; $d++) {
                    MeetingDiscussion::create([
                        'meeting_id' => $meeting->id,
                        'agenda_id' => $agenda->id,
                        'topic' => fake()->sentence(4),
                        'discussion' => fake()->paragraph(),
                        'key_points' => fake()->sentence()."\n".fake()->sentence(),
                        'sort_order' => $d,
                        'created_by' => $organizer->id,
                        'updated_by' => $organizer->id,
                    ]);
                }

                $decisionCount = rand(1, 2);
                for ($dec = 1; $dec <= $decisionCount; $dec++) {
                    MeetingDecision::create([
                        'meeting_id' => $meeting->id,
                        'agenda_id' => $agenda->id,
                        'discussion_id' => $meeting->discussions()->inRandomOrder()->first()?->id,
                        'decision_no' => $dec,
                        'decision_title' => fake()->sentence(4),
                        'decision_description' => fake()->paragraph(),
                        'decision_type' => fake()->randomElement($decisionTypes),
                        'decision_status' => 'active',
                        'decision_date' => $meeting->meeting_date,
                        'created_by' => $organizer->id,
                        'updated_by' => $organizer->id,
                    ]);
                }

                MeetingActionItem::create([
                    'meeting_id' => $meeting->id,
                    'agenda_id' => $agenda->id,
                    'discussion_id' => $meeting->discussions()->inRandomOrder()->first()?->id,
                    'decision_id' => $meeting->decisions()->inRandomOrder()->first()?->id,
                    'action_no' => $a,
                    'title' => fake()->sentence(4),
                    'description' => fake()->paragraph(),
                    'assigned_to' => $users->random()->id,
                    'assigned_department_id' => $organizer->employee?->department_id,
                    'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
                    'start_date' => $meeting->meeting_date,
                    'due_date' => fake()->dateTimeBetween('now', '+2 weeks'),
                    'status' => fake()->randomElement($actionStatuses),
                    'completion_percentage' => fake()->numberBetween(0, 100),
                    'created_by' => $organizer->id,
                    'updated_by' => $organizer->id,
                ]);
            }

            $meetingTags = $tags->shuffle()->take(rand(1, 3));
            foreach ($meetingTags as $tag) {
                $meeting->tags()->attach($tag->id);
            }

            $attachmentCount = rand(0, 2);
            for ($att = 1; $att <= $attachmentCount; $att++) {
                MeetingAttachment::create([
                    'meeting_id' => $meeting->id,
                    'file_name' => fake()->word().'.pdf',
                    'file_path' => 'meetings/'.$meeting->id.'/attachments',
                    'file_type' => 'application/pdf',
                    'file_size' => rand(100, 5000),
                    'uploaded_by' => $organizer->id,
                    'description' => fake()->sentence(),
                ]);
            }

            if (in_array($minutesStatus, ['submitted', 'approved', 'published'], true)) {
                $approvalCount = rand(1, 3);
                for ($app = 1; $app <= $approvalCount; $app++) {
                    MeetingMinutesApproval::create([
                        'meeting_id' => $meeting->id,
                        'step_no' => $app,
                        'approver_id' => $users->random()->id,
                        'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
                        'comments' => fake()->sentence(),
                        'action_at' => now(),
                    ]);
                }
            }
        }
    }

    private function weightedRandom(array $items, array $weights): string
    {
        $total = array_sum($weights);
        $random = rand(1, $total);

        foreach ($weights as $index => $weight) {
            $random -= $weight;
            if ($random <= 0) {
                return $items[$index];
            }
        }

        return $items[array_key_last($weights)];
    }
}
