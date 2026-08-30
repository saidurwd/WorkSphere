<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\MeetingActionItem;
use App\Models\MeetingAgenda;
use App\Models\MeetingDecision;
use App\Models\MeetingDiscussion;
use App\Models\MeetingParticipant;
use App\Models\MeetingType;
use App\Models\User;
use Illuminate\Database\Seeder;

class MeetingSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::inRandomOrder()->take(5)->get();
        $types = MeetingType::inRandomOrder()->take(3)->get();

        foreach ($types as $type) {
            $organizer = $users->random();
            $chairperson = $users->random();

            $meeting = Meeting::create([
                'meeting_no' => 'MTG-2026-'.str_pad(Meeting::count() + 1, 5, '0', STR_PAD_LEFT),
                'title' => $type->name.' - '.fake()->monthName(),
                'meeting_type_id' => $type->id,
                'organizer_id' => $organizer->id,
                'chairperson_id' => $chairperson->id,
                'department_id' => $organizer->employee?->department_id,
                'location' => fake()->randomElement(['Board Room', 'Conference Hall', 'Online', 'Main Office']),
                'meeting_date' => fake()->dateTimeBetween('-1 month', '+1 month'),
                'start_time' => fake()->time('H:i'),
                'end_time' => fake()->time('H:i'),
                'timezone' => 'UTC',
                'status' => fake()->randomElement(['scheduled', 'in_progress', 'completed']),
                'priority' => fake()->randomElement(['normal', 'important', 'urgent']),
                'description' => fake()->sentence(),
                'agenda' => fake()->paragraph(),
                'minutes_status' => fake()->randomElement(['draft', 'prepared', 'submitted', 'approved', 'published']),
                'created_by' => $organizer->id,
                'updated_by' => $organizer->id,
            ]);

            foreach ($users->unique('id') as $user) {
                MeetingParticipant::create([
                    'meeting_id' => $meeting->id,
                    'user_id' => $user->id,
                    'participant_type' => fake()->randomElement(['organizer', 'chairperson', 'member', 'guest', 'presenter', 'observer']),
                    'attendance_status' => fake()->randomElement(['invited', 'accepted', 'declined', 'present', 'absent', 'apology']),
                    'invited_at' => now(),
                ]);
            }

            for ($i = 1; $i <= 3; $i++) {
                $agenda = MeetingAgenda::create([
                    'meeting_id' => $meeting->id,
                    'agenda_no' => $i,
                    'title' => fake()->sentence(3),
                    'description' => fake()->sentence(),
                    'sort_order' => $i,
                ]);

                MeetingDiscussion::create([
                    'meeting_id' => $meeting->id,
                    'agenda_id' => $agenda->id,
                    'topic' => fake()->sentence(4),
                    'discussion' => fake()->paragraph(),
                    'key_points' => fake()->sentence()."\n".fake()->sentence(),
                    'sort_order' => 1,
                    'created_by' => $organizer->id,
                    'updated_by' => $organizer->id,
                ]);

                $decision = MeetingDecision::create([
                    'meeting_id' => $meeting->id,
                    'agenda_id' => $agenda->id,
                    'discussion_id' => $meeting->discussions()->inRandomOrder()->first()?->id,
                    'decision_no' => $i,
                    'decision_title' => fake()->sentence(4),
                    'decision_description' => fake()->paragraph(),
                    'decision_type' => fake()->randomElement(['approved', 'rejected', 'deferred', 'noted', 'further_discussion_required']),
                    'decision_status' => 'active',
                    'decision_date' => $meeting->meeting_date,
                    'created_by' => $organizer->id,
                    'updated_by' => $organizer->id,
                ]);

                MeetingActionItem::create([
                    'meeting_id' => $meeting->id,
                    'agenda_id' => $agenda->id,
                    'discussion_id' => $meeting->discussions()->inRandomOrder()->first()?->id,
                    'decision_id' => $decision->id,
                    'action_no' => $i,
                    'title' => fake()->sentence(4),
                    'description' => fake()->paragraph(),
                    'assigned_to' => $users->random()->id,
                    'assigned_department_id' => $organizer->employee?->department_id,
                    'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
                    'start_date' => $meeting->meeting_date,
                    'due_date' => fake()->dateTimeBetween('now', '+2 weeks'),
                    'status' => fake()->randomElement(['open', 'in_progress', 'completed', 'on_hold']),
                    'completion_percentage' => fake()->numberBetween(0, 100),
                    'created_by' => $organizer->id,
                    'updated_by' => $organizer->id,
                ]);
            }
        }
    }
}
