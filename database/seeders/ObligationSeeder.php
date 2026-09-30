<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Location;
use Modules\Obligations\Models\NotificationLog;
use Modules\Obligations\Models\NotificationRule;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationActivityLog;
use Modules\Obligations\Models\ObligationCategory;
use Modules\Obligations\Models\ObligationDocument;
use Modules\Obligations\Models\ObligationRenewal;
use Modules\Obligations\Models\ObligationType;
use App\Models\User;
use Modules\Obligations\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ObligationSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ComplianceObligationSeeder::class);

        $types = ObligationType::all();
        $categories = ObligationCategory::all();
        $companies = Company::all();
        $departments = Department::all();
        $locations = Location::all();
        $vendors = Vendor::all();
        $users = User::inRandomOrder()->take(10)->get();
        $notificationRules = NotificationRule::all();

        if ($types->isEmpty() || $categories->isEmpty() || $users->isEmpty()) {
            return;
        }

        $titles = [
            'Annual software license renewal',
            'SSL certificate renewal',
            'Domain name renewal',
            'Hardware maintenance contract',
            'Insurance policy renewal',
            'Government business license',
            'Regulatory inspection',
            'Warranty extension',
            'SaaS subscription renewal',
            'Vendor service agreement',
            'Equipment maintenance contract',
            'Annual IT security audit',
            'Data protection compliance review',
            'Fire safety inspection',
            'Elevator maintenance contract',
            'Generator AMC renewal',
            'Office lease renewal',
            'Telecommunication license',
            'Professional indemnity insurance',
            'Environmental compliance certificate',
        ];

        $statuses = ['active', 'due_soon', 'overdue', 'expired', 'renewed', 'cancelled'];
        $statusWeights = [35, 15, 15, 10, 15, 10];

        $priorities = ['low', 'medium', 'high', 'critical'];
        $priorityWeights = [20, 35, 30, 15];

        $riskLevels = ['low', 'medium', 'high', 'critical'];
        $riskWeights = [25, 35, 25, 15];

        $currencies = ['USD', 'BDT', 'EUR', 'GBP'];
        $currencyWeights = [40, 30, 15, 15];

        $recurrenceTypes = [null, 'yearly', 'quarterly', 'monthly', 'weekly'];
        $recurrenceWeights = [30, 35, 15, 10, 10];

        $now = Carbon::now();

        for ($i = 1; $i <= 20; $i++) {
            $type = $types->random();
            $category = $categories->random();
            $company = $companies->random();
            $owner = $users->random();
            $status = $this->weightedRandom($statuses, $statusWeights);
            $priority = $this->weightedRandom($priorities, $priorityWeights);
            $riskLevel = $this->weightedRandom($riskLevels, $riskWeights);
            $currency = $this->weightedRandom($currencies, $currencyWeights);
            $recurrenceType = $this->weightedRandom($recurrenceTypes, $recurrenceWeights);

            $startDate = $now->copy()->subDays(rand(30, 365))->toDateString();
            $expiryDate = match ($status) {
                'expired' => $now->copy()->subDays(rand(1, 90))->toDateString(),
                'overdue' => $now->copy()->subDays(rand(1, 30))->toDateString(),
                'due_soon' => $now->copy()->addDays(rand(1, 30))->toDateString(),
                'renewed' => $now->copy()->addDays(rand(30, 180))->toDateString(),
                default => $now->copy()->addDays(rand(30, 365))->toDateString(),
            };

            $obligation = Obligation::firstOrCreate(
                ['obligation_no' => 'OBS-'.str_pad($i, 5, '0', STR_PAD_LEFT)],
                [
                    'title' => $titles[$i - 1],
                    'description' => fake()->sentence(rand(8, 16)),
                    'obligation_type_id' => $type->id,
                    'category_id' => $category->id,
                    'company_id' => $company->id,
                    'department_id' => $departments->isNotEmpty() ? $departments->random()->id : null,
                    'location_id' => $locations->isNotEmpty() ? $locations->random()->id : null,
                    'vendor_id' => $vendors->isNotEmpty() ? $vendors->random()->id : null,
                    'owner_user_id' => $owner->id,
                    'backup_user_id' => $users->random()->id,
                    'reviewer_user_id' => $users->random()->id,
                    'approver_user_id' => $users->random()->id,
                    'start_date' => $startDate,
                    'expiry_date' => $expiryDate,
                    'renewal_required' => fake()->boolean(70),
                    'auto_renew' => fake()->boolean(30),
                    'recurrence_type' => $recurrenceType,
                    'recurrence_interval' => $recurrenceType ? rand(1, 12) : null,
                    'priority' => $priority,
                    'risk_level' => $riskLevel,
                    'estimated_cost' => fake()->randomFloat(2, 500, 50000),
                    'currency' => $currency,
                    'status' => $status,
                    'notes' => fake()->optional()->sentence(),
                    'created_by' => $owner->id,
                ]
            );

            $this->seedActivityLogs($obligation, $users, $now);
            $this->seedDocuments($obligation, $owner, $now);
            $this->seedRenewals($obligation, $users, $now);
            $this->seedNotifications($obligation, $users, $notificationRules, $now);
        }
    }

    private function seedActivityLogs(Obligation $obligation, $users, Carbon $now): void
    {
        $actions = ['created', 'updated', 'renewed', 'reviewed', 'approved', 'cancelled'];
        $count = rand(2, 5);

        for ($i = 0; $i < $count; $i++) {
            ObligationActivityLog::create([
                'obligation_id' => $obligation->id,
                'user_id' => $users->random()->id,
                'action' => fake()->randomElement($actions),
                'old_value' => fake()->optional()->sentence(),
                'new_value' => fake()->optional()->sentence(),
                'remarks' => fake()->sentence(),
                'ip_address' => fake()->ipv4(),
                'user_agent' => fake()->userAgent(),
                'created_at' => $now->copy()->subDays(rand(0, 60))->addHours(rand(0, 23)),
            ]);
        }
    }

    private function seedDocuments(Obligation $obligation, $owner, Carbon $now): void
    {
        $documentTypes = ['contract', 'certificate', 'invoice', 'policy', 'report', 'other'];
        $count = rand(1, 4);

        for ($i = 0; $i < $count; $i++) {
            $documentDate = $now->copy()->subDays(rand(0, 90));

            ObligationDocument::create([
                'obligation_id' => $obligation->id,
                'document_type' => fake()->randomElement($documentTypes),
                'file_name' => fake()->word().'.pdf',
                'file_path' => 'obligations/'.$obligation->id.'/documents',
                'file_size' => rand(100, 5000),
                'mime_type' => 'application/pdf',
                'document_date' => $documentDate,
                'expiry_date' => fake()->optional(0.5)->dateTimeBetween($now, '+6 months'),
                'uploaded_by' => $owner->id,
            ]);
        }
    }

    private function seedRenewals(Obligation $obligation, $users, Carbon $now): void
    {
        $hasRenewal = $obligation->renewal_required && fake()->boolean(60);

        if (! $hasRenewal) {
            return;
        }

        $renewedBy = $users->random();
        $previousExpiry = $obligation->expiry_date;
        $newStartDate = Carbon::parse($previousExpiry)->addDay()->toDateString();
        $newExpiryDate = Carbon::parse($previousExpiry)->addYear()->toDateString();
        $renewalDate = Carbon::parse($previousExpiry)->subDays(rand(5, 30))->toDateString();

        ObligationRenewal::create([
            'obligation_id' => $obligation->id,
            'previous_expiry_date' => $previousExpiry,
            'new_start_date' => $newStartDate,
            'new_expiry_date' => $newExpiryDate,
            'renewal_date' => $renewalDate,
            'vendor_id' => $obligation->vendor_id,
            'cost' => fake()->randomFloat(2, 500, 50000),
            'currency' => $obligation->currency,
            'purchase_reference' => fake()->optional()->bothify('PO-####'),
            'invoice_reference' => fake()->optional()->bothify('INV-####'),
            'remarks' => fake()->optional()->sentence(),
            'renewed_by' => $renewedBy->id,
        ]);
    }

    private function seedNotifications(Obligation $obligation, $users, $notificationRules, Carbon $now): void
    {
        if ($notificationRules->isEmpty()) {
            return;
        }

        $count = rand(1, 4);

        for ($i = 0; $i < $count; $i++) {
            $rule = $notificationRules->random();
            $status = fake()->randomElement(['pending', 'sent', 'failed', 'retry']);

            NotificationLog::create([
                'obligation_id' => $obligation->id,
                'user_id' => $users->random()->id,
                'notification_rule_id' => $rule->id,
                'channel' => $rule->channel,
                'notification_type' => 'REMINDER',
                'scheduled_at' => $now->copy()->subDays(rand(0, 30))->addHours(rand(0, 23)),
                'sent_at' => in_array($status, ['sent', 'retry']) ? $now->copy()->subHours(rand(1, 12)) : null,
                'status' => $status,
                'subject' => fake()->sentence(4),
                'message' => fake()->paragraph(),
                'retry_count' => $status === 'retry' ? rand(1, 3) : 0,
                'error_message' => in_array($status, ['failed', 'retry']) ? fake()->sentence() : null,
                'provider_message_id' => fake()->optional()->uuid(),
            ]);
        }
    }

    private function weightedRandom(array $items, array $weights): ?string
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
