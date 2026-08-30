<?php

namespace Database\Seeders;

use App\Models\GatePass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class GatePassSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::orderBy('name')->get();
        $creator = $users->firstOrFail();
        $preparedBy = $users->pluck('name')->all();

        $now = Carbon::now();

        $names = [
            'Mohammed Al-Farsi', 'Priya Sharma', 'David Anderson', 'Aisha Khan', 'John Mitchell',
            'Ravi Patel', 'Sofia Rodriguez', 'Chen Wei', 'Fatima Noor', 'Daniel O\'Connor',
            'Amara Okafor', 'Hiroshi Tanaka', 'Elena Petrova', 'Kwame Mensah', 'Sara Lindqvist',
            'Omar Haddad', 'Mei Lin', 'Carlos Mendoza', 'Nadia Rahman', 'Thomas Becker',
        ];

        $purposes = [
            'Delivery of office supplies', 'Scheduled maintenance work', 'Client meeting with finance team',
            'Interview for engineering position', 'Vendor onboarding discussion', 'Annual audit support',
            'Installation of new network equipment', 'Catering for company event', 'Site inspection visit',
            'Equipment repair and servicing', 'Partnership presentation', 'Security assessment walkthrough',
        ];

        $addresses = [
            '12 Green Road, Dhaka', '45 Lake View, Gulshan', '8 Tech Park, Banani',
            '23 Industrial Area, Narayanganj', '5 Corporate Tower, Uttara', '17 Riverside, Dhanmondi',
        ];

        $issueRanges = [
            'past' => fn () => $now->copy()->subDays(rand(1, 20)),
            'today' => fn () => $now->copy(),
            'this_week' => fn () => $now->copy()->addDays(rand(1, 6)),
            'this_month' => fn () => $now->copy()->addDays(rand(7, 25)),
            'future' => fn () => $now->copy()->addDays(rand(26, 55)),
        ];

        $ranges = array_keys($issueRanges);
        $weights = [20, 12, 26, 24, 18];

        $maxPass = GatePass::where('gate_pass_number', 'like', now()->format('Y').'-%')
            ->orderByDesc('gate_pass_number')
            ->value('gate_pass_number');
        $passCounter = $maxPass ? ((int) substr($maxPass, -4) + 1) : 1;

        for ($i = 0; $i < 40; $i++) {
            $rangeIndex = $this->weightedRandom($weights);
            $range = $ranges[$rangeIndex];
            $issueDate = $issueRanges[$range]();

            $year = $issueDate->format('Y');
            $passNo = 'GP-'.$year.'-'.str_pad($passCounter, 4, '0', STR_PAD_LEFT);
            $passCounter++;

            $checked = fake()->optional(0.5)->randomElement($preparedBy);

            GatePass::create([
                'gate_pass_number' => $passNo,
                'issue_date' => $issueDate,
                'name' => $names[array_rand($names)],
                'purpose' => fake()->randomElement($purposes),
                'address' => fake()->optional(0.7)->randomElement($addresses),
                'description' => fake()->sentence(rand(4, 10)),
                'quantity' => fake()->numberBetween(1, 50),
                'prepared_by' => fake()->randomElement($preparedBy),
                'checked_by' => $checked,
                'created_at' => $now->copy()->subDays(rand(0, 20))->subHours(rand(0, 23)),
                'updated_at' => $now->copy(),
            ]);
        }
    }

    private function weightedRandom(array $weights): int
    {
        $total = array_sum($weights);
        $random = rand(1, $total);

        foreach ($weights as $index => $weight) {
            $random -= $weight;
            if ($random <= 0) {
                return $index;
            }
        }

        return array_key_last($weights);
    }
}
