<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Obligations\Models\NotificationLog;
use Modules\Obligations\Models\NotificationRule;
use Modules\Obligations\Models\Obligation;
use Modules\Obligations\Models\ObligationActivityLog;
use Modules\Obligations\Models\ObligationCategory;
use Modules\Obligations\Models\ObligationDocument;
use Modules\Obligations\Models\ObligationRenewal;
use Modules\Obligations\Models\ObligationResponsibility;
use Modules\Obligations\Models\ObligationType;
use Modules\Obligations\Models\Vendor;

/**
 * The compliance and obligations register, at demo volume.
 *
 * An obligations register is only convincing if it looks like one that has been
 * running for years: licences expiring in a spread of dates, renewals already
 * processed against some of them, supporting documents attached, a named owner
 * and backup for each, and a trail of the reminders that actually went out. That
 * is what is seeded here, sized against `config('seed.php').volumes`.
 *
 * Status is derived from the expiry date rather than picked independently, so the
 * register is internally consistent: nothing is `active` with an expiry a year
 * ago, and the overdue, due-soon and expired queues each hold rows.
 *
 * The reference data this depends on — companies, obligation types, categories,
 * notification and escalation rules — is seeded by {@see ComplianceObligationSeeder}.
 */
class ObligationSeeder extends Seeder
{
    use WithoutModelEvents;

    private DeterministicSequence $random;

    /**
     * Obligation titles, grouped so a title is paired with a plausible type
     * rather than being scattered at random across sixteen types.
     *
     * @var array<string, list<string>>
     */
    private const TITLES = [
        'Software License' => [
            'Annual enterprise licence renewal',
            'Database platform subscription renewal',
            'Office productivity suite renewal',
            'Design and prototyping tool subscription',
            'Source control and CI minutes top-up',
            'Monitoring and observability licence renewal',
        ],
        'Hardware License' => [
            'Server rack hardware support entitlement',
            'Peripheral and device licensing renewal',
            'Network appliance licence renewal',
        ],
        'SSL Certificate' => [
            'Public TLS certificate renewal',
            'Wildcard certificate renewal for the primary domain',
            'Internal PKI certificate authority renewal',
        ],
        'Domain Renewal' => [
            'Primary domain registration renewal',
            'Regional domain registration renewal',
            'Defensive domain registrations',
        ],
        'AMC' => [
            'Annual maintenance contract for the core servers',
            'Annual maintenance contract for the network equipment',
            'Annual maintenance contract for the print estate',
            'Annual maintenance contract for the backup appliances',
        ],
        'MMC' => [
            'Managed service contract for the mail estate',
            'Managed hosting contract for the customer portal',
            'Managed network monitoring contract',
        ],
        'Insurance' => [
            'Public liability insurance renewal',
            'Professional indemnity insurance renewal',
            'Cyber insurance renewal',
            'Property and equipment insurance renewal',
            'Group medical insurance renewal',
        ],
        'Government License' => [
            'Trade licence renewal',
            'Tax registration certificate renewal',
            'Value added tax registration renewal',
            'Import and export licence renewal',
        ],
        'Regulatory Certificate' => [
            'Data protection compliance certificate',
            'Information security management certification',
            'Environmental compliance certification',
            'Quality management system certification',
        ],
        'Inspection' => [
            'Annual fire safety inspection',
            'Building safety inspection',
            'Electrical installation inspection',
            'Lift and elevator safety inspection',
            'Generator load bank test',
        ],
        'Warranty' => [
            'Extended warranty on the core server estate',
            'Extended warranty on the network refresh',
            'Extended warranty on the backup appliances',
        ],
        'SaaS Subscription' => [
            'Payroll SaaS subscription renewal',
            'Recruitment platform subscription renewal',
            'Accounting SaaS subscription renewal',
            'Helpdesk SaaS subscription renewal',
            'Business intelligence platform renewal',
        ],
        'Vendor Contract' => [
            'Telecommunications service agreement renewal',
            'Managed print service agreement renewal',
            'Facility management services agreement renewal',
            'Catering services agreement renewal',
            'Transport services agreement renewal',
        ],
        'Service Contract' => [
            'External audit engagement renewal',
            'Consultancy support agreement renewal',
            'Recruitment agency service agreement renewal',
            'Legal retainer renewal',
        ],
        'Equipment Maintenance' => [
            'Generator preventive maintenance contract',
            'Air conditioning maintenance contract',
            'Water treatment maintenance contract',
            'Warehouse equipment maintenance contract',
        ],
        'Other' => [
            'Office lease renewal',
            'Warehouse lease renewal',
            'Association membership renewal',
            'Professional body subscription renewal',
        ],
    ];

    /**
     * @var list<string>
     */
    private const DOCUMENT_TYPES = ['contract', 'certificate', 'invoice', 'policy', 'report', 'other'];

    /**
     * @var list<string>
     */
    private const ACTIVITY_ACTIONS = ['created', 'updated', 'renewed', 'reviewed', 'approved', 'escalated', 'cancelled'];

    public function run(): void
    {
        $this->random = new DeterministicSequence;

        $this->call(ComplianceObligationSeeder::class);

        $types = ObligationType::query()->get()->keyBy('type_name');
        $categories = ObligationCategory::query()->get();
        $companies = Company::query()->get();
        $departments = Department::query()->get();
        $locations = Location::query()->get();
        $vendors = Vendor::query()->get();
        $users = User::query()->with('employee')->where('status', 'active')->get();
        $rules = NotificationRule::query()->get();

        if ($types->isEmpty() || $categories->isEmpty() || $companies->isEmpty() || $users->count() < 4) {
            $this->command?->warn('  Obligations: skipped, reference data is missing.');

            return;
        }

        $ids = $this->seedObligations($types, $categories, $companies, $departments, $locations, $vendors, $users);

        $userIds = $users->pluck('id')->all();

        $this->seedResponsibilities($ids, $userIds);
        $this->seedDocuments($ids, $userIds);
        $this->seedRenewals($ids, $userIds);
        $this->seedActivityLogs($ids, $userIds);
        $this->seedNotificationLogs($ids, $userIds, $rules->pluck('id')->all());

        $this->command?->info(sprintf(
            '  Obligations: %d (responsibilities %d, documents %d, renewals %d, activity %d, notifications %d)',
            count($ids),
            ObligationResponsibility::query()->count(),
            ObligationDocument::query()->count(),
            ObligationRenewal::query()->count(),
            ObligationActivityLog::query()->count(),
            NotificationLog::query()->count(),
        ));
    }

    /**
     * @param  Collection<string, ObligationType>  $types
     * @param  Collection<int, ObligationCategory>  $categories
     * @param  Collection<int, Company>  $companies
     * @param  Collection<int, Department>  $departments
     * @param  Collection<int, Location>  $locations
     * @param  Collection<int, Vendor>  $vendors
     * @param  Collection<int, User>  $users
     * @return list<int>
     */
    private function seedObligations(
        Collection $types,
        Collection $categories,
        Collection $companies,
        Collection $departments,
        Collection $locations,
        Collection $vendors,
        Collection $users,
    ): array {
        $wanted = (int) config('seed.volumes.obligations', 300);

        if (Obligation::query()->count() >= $wanted) {
            return Obligation::query()->pluck('id')->all();
        }

        $now = Carbon::now();
        $rows = [];

        $priorities = ['low', 'medium', 'high', 'critical'];
        $priorityWeights = [20, 38, 27, 15];
        $riskLevels = ['low', 'medium', 'high', 'critical'];
        $riskWeights = [26, 36, 24, 14];
        $currencies = ['BDT', 'USD', 'EUR', 'GBP'];
        $currencyWeights = [46, 30, 14, 10];
        $recurrenceTypes = [null, 'yearly', 'quarterly', 'monthly', 'weekly'];
        $recurrenceWeights = [28, 38, 16, 11, 7];

        for ($i = 1; $i <= $wanted; $i++) {
            $type = $types->random();
            $titles = self::TITLES[$type->type_name] ?? ['Organisational obligation renewal'];
            $owner = $users->random();

            $expiry = $this->expiryFor($now);
            $status = $this->statusFor($expiry, $now);
            $startDate = $expiry->copy()->subYears($this->random->between(1, 4))->toDateString();
            $recurrenceType = $this->random->pick($recurrenceTypes, $recurrenceWeights);

            $rows[] = [
                'obligation_no' => 'OBS-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'title' => $this->random->pick($titles),
                'description' => $this->description($type->type_name),
                'obligation_type_id' => $type->id,
                'category_id' => $categories->random()->id,
                'company_id' => $companies->random()->id,
                'department_id' => $departments->random()->id,
                'location_id' => $locations->random()->id,
                'vendor_id' => $this->random->chance(72) ? $vendors->random()->id : null,
                'owner_user_id' => $owner->id,
                'backup_user_id' => $this->another($users, $owner->id)->id,
                'reviewer_user_id' => $this->another($users, $owner->id)->id,
                'approver_user_id' => $this->another($users, $owner->id)->id,
                'start_date' => $startDate,
                'expiry_date' => $expiry->toDateString(),
                'renewal_required' => $this->random->chance(78),
                'auto_renew' => $this->random->chance(34),
                'recurrence_type' => $recurrenceType,
                'recurrence_interval' => $recurrenceType === null ? null : $this->random->between(1, 12),
                'priority' => $this->random->pick($priorities, $priorityWeights),
                'risk_level' => $this->random->pick($riskLevels, $riskWeights),
                'estimated_cost' => round($this->random->between(45_000, 4_500_000) / 100, 2),
                'currency' => $this->random->pick($currencies, $currencyWeights),
                'status' => $status,
                'notes' => $this->random->chance(55) ? $this->notes($status) : null,
                'created_by' => $owner->id,
                'created_at' => Carbon::parse($startDate)->addDays($this->random->between(1, 45)),
                'updated_at' => $now->copy()->subDays($this->random->between(0, 120)),
            ];

            if (count($rows) >= 100) {
                $this->insert($rows);
                $rows = [];
            }
        }

        if ($rows !== []) {
            $this->insert($rows);
        }

        return Obligation::query()->orderBy('id')->pluck('id')->all();
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insert(array $rows): void
    {
        DB::table('obligations')->insert($rows);
    }

    /**
     * An expiry date spread from well past to well beyond today.
     */
    private function expiryFor(Carbon $now): Carbon
    {
        $bucket = $this->random->weighted([14, 12, 18, 26, 30]);

        return match ($bucket) {
            0 => $now->copy()->subDays($this->random->between(30, 400)),
            1 => $now->copy()->subDays($this->random->between(1, 29)),
            2 => $now->copy()->addDays($this->random->between(0, 30)),
            3 => $now->copy()->addDays($this->random->between(31, 180)),
            default => $now->copy()->addDays($this->random->between(181, 730)),
        };
    }

    /**
     * Status follows from the expiry date.
     *
     * Picking the two independently produces a register that contradicts itself —
     * `active` obligations a year overdue — and every status filter then returns
     * whatever the random draw happened to produce rather than the rows a real
     * register holds.
     */
    private function statusFor(Carbon $expiry, Carbon $now): string
    {
        $days = $now->diffInDays($expiry, false);

        if ($days < 0) {
            return $this->random->pick(['overdue', 'expired', 'cancelled'], [62, 32, 6]);
        }

        if ($days <= 30) {
            return 'due_soon';
        }

        return $this->random->pick(['active', 'renewed', 'cancelled'], [80, 14, 6]);
    }

    private function description(string $typeName): string
    {
        return sprintf(
            'Recorded %s under the compliance register. Tracked from the date of signature with reminders scheduled ahead of expiry, and renewed through procurement when the notice period is reached.',
            strtolower($typeName),
        );
    }

    private function notes(string $status): string
    {
        return match ($status) {
            'expired' => 'Lapsed while the renewal was with procurement. Escalated to the department head.',
            'overdue' => 'Renewal instructed; awaiting the signed paperwork from the vendor.',
            'due_soon' => 'Notice period is thirty days. Purchase requisition raised for approval.',
            'renewed' => 'Renewed for another term. The purchase order and invoice are filed against this record.',
            'cancelled' => 'No longer required after the service was replaced. Retained for the audit trail.',
            default => 'Within the normal renewal cycle. No action outstanding.',
        };
    }

    /**
     * A named owner, a backup, a reviewer and an approver per obligation.
     *
     * @param  list<int>  $obligationIds
     * @param  list<int>  $userIds
     */
    private function seedResponsibilities(array $obligationIds, array $userIds): void
    {
        $wanted = (int) config('seed.volumes.obligation_responsibilities', 800);

        if (ObligationResponsibility::query()->count() >= $wanted || $obligationIds === []) {
            return;
        }

        $rows = [];
        $seen = [];

        // `obligation_responsibilities` is unique on
        // (obligation_id, user_id, responsibility_type), so a repeat draw is
        // discarded rather than written: the second row would be a duplicate
        // key error, not a second responsibility.
        while (count($rows) < $wanted) {
            $obligationId = $this->pickFrom($obligationIds);
            $userId = $this->pickFrom($userIds);
            $type = $this->random->pick(['OWNER', 'BACKUP_OWNER', 'REVIEWER', 'APPROVER'], [40, 25, 20, 15]);
            $key = $obligationId.':'.$userId.':'.$type;

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $rows[] = [
                'obligation_id' => $obligationId,
                'user_id' => $userId,
                'responsibility_type' => $type,
                'escalation_level' => $this->random->chance(35) ? $this->random->between(1, 3) : null,
                'active' => true,
                'created_at' => Carbon::now()->subDays($this->random->between(0, 400)),
                'updated_at' => Carbon::now()->subDays($this->random->between(0, 400)),
            ];
        }

        $this->insertRows('obligation_responsibilities', $rows);
    }

    /**
     * @param  list<int>  $obligationIds
     * @param  list<int>  $userIds
     */
    private function seedDocuments(array $obligationIds, array $userIds): void
    {
        $wanted = (int) config('seed.volumes.obligation_documents', 600);

        if (ObligationDocument::query()->count() >= $wanted || $obligationIds === []) {
            return;
        }

        $now = Carbon::now();
        $rows = [];

        while (count($rows) < $wanted) {
            $documentedAt = $now->copy()->subDays($this->random->between(0, 500));
            $type = $this->random->pick(self::DOCUMENT_TYPES);

            $rows[] = [
                'obligation_id' => $this->pickFrom($obligationIds),
                'document_type' => $type,
                'file_name' => str_replace(' ', '-', strtolower($type)).'-'.($this->random->between(1000, 9999)).'.pdf',
                'file_path' => 'obligations/documents',
                'file_size' => $this->random->between(45_000, 8_400_000),
                'mime_type' => 'application/pdf',
                'document_date' => $documentedAt->toDateString(),
                'expiry_date' => $this->random->chance(45)
                    ? $documentedAt->copy()->addDays($this->random->between(90, 900))->toDateString()
                    : null,
                'uploaded_by' => $this->pickFrom($userIds),
                'created_at' => $documentedAt,
                'updated_at' => $documentedAt,
            ];
        }

        $this->insertRows('obligation_documents', $rows);
    }

    /**
     * @param  list<int>  $obligationIds
     * @param  list<int>  $userIds
     */
    private function seedRenewals(array $obligationIds, array $userIds): void
    {
        $wanted = (int) config('seed.volumes.obligation_renewals', 180);

        if (ObligationRenewal::query()->count() >= $wanted || $obligationIds === []) {
            return;
        }

        $rows = [];

        while (count($rows) < $wanted) {
            $previousExpiry = Carbon::now()->subDays($this->random->between(1, 700));
            $term = $this->random->pick([1, 1, 1, 2, 3]);
            $newStart = $previousExpiry->copy()->addDay();
            $renewedOn = $previousExpiry->copy()->subDays($this->random->between(5, 45));

            $rows[] = [
                'obligation_id' => $this->pickFrom($obligationIds),
                'previous_expiry_date' => $previousExpiry->toDateString(),
                'new_start_date' => $newStart->toDateString(),
                'new_expiry_date' => $newStart->copy()->addYears($term)->toDateString(),
                'renewal_date' => $renewedOn->toDateString(),
                'vendor_id' => null,
                'cost' => round($this->random->between(45_000, 4_500_000) / 100, 2),
                'currency' => $this->random->pick(['BDT', 'USD', 'EUR', 'GBP'], [46, 30, 14, 10]),
                'purchase_reference' => 'PO-'.str_pad((string) $this->random->between(1000, 9999), 5, '0', STR_PAD_LEFT),
                'invoice_reference' => 'INV-'.str_pad((string) $this->random->between(1000, 9999), 5, '0', STR_PAD_LEFT),
                'remarks' => $this->random->chance(60)
                    ? 'Renewed on the existing terms. No change to the scope or the commercial position.'
                    : null,
                'renewed_by' => $this->pickFrom($userIds),
                'created_at' => $renewedOn,
                'updated_at' => $renewedOn,
            ];
        }

        $this->insertRows('obligation_renewals', $rows);
    }

    /**
     * @param  list<int>  $obligationIds
     * @param  list<int>  $userIds
     */
    private function seedActivityLogs(array $obligationIds, array $userIds): void
    {
        $wanted = (int) config('seed.volumes.obligation_activity_logs', 900);

        if (ObligationActivityLog::query()->count() >= $wanted || $obligationIds === []) {
            return;
        }

        $rows = [];

        while (count($rows) < $wanted) {
            $at = Carbon::now()->subDays($this->random->between(0, 500));

            $rows[] = [
                'obligation_id' => $this->pickFrom($obligationIds),
                'user_id' => $this->pickFrom($userIds),
                'action' => $this->random->pick(self::ACTIVITY_ACTIONS),
                'old_value' => null,
                'new_value' => json_encode(['field' => 'expiry_date', 'to' => $at->copy()->addYear()->toDateString()]),
                'remarks' => 'Recorded from the compliance register review.',
                'ip_address' => $this->ip(),
                'user_agent' => $this->userAgent(),
                'created_at' => $at,
                'updated_at' => $at,
            ];
        }

        $this->insertRows('obligation_activity_logs', $rows);
    }

    /**
     * @param  list<int>  $obligationIds
     * @param  list<int>  $userIds
     * @param  list<int>  $ruleIds
     */
    private function seedNotificationLogs(array $obligationIds, array $userIds, array $ruleIds): void
    {
        $wanted = (int) config('seed.volumes.notification_logs', 700);

        if (NotificationLog::query()->count() >= $wanted || $obligationIds === []) {
            return;
        }

        $rows = [];

        while (count($rows) < $wanted) {
            $at = Carbon::now()->subDays($this->random->between(0, 400));
            $status = $this->random->pick(['sent', 'sent', 'sent', 'sent', 'pending', 'failed', 'retry'], [62, 62, 62, 62, 10, 8, 8]);
            $delivered = in_array($status, ['sent', 'retry'], true);

            $rows[] = [
                'obligation_id' => $this->pickFrom($obligationIds),
                'user_id' => $this->pickFrom($userIds),
                'notification_rule_id' => $ruleIds === [] ? null : $this->pickFrom($ruleIds),
                'channel' => $this->random->pick(['IN_APP', 'EMAIL', 'SMS'], [58, 37, 5]),
                'notification_type' => $this->random->pick(['REMINDER', 'ESCALATION', 'OVERDUE'], [60, 25, 15]),
                'scheduled_at' => $at,
                'sent_at' => $delivered ? $at->copy()->addMinutes($this->random->between(1, 90)) : null,
                'status' => $status,
                'subject' => 'Obligation expiry reminder',
                'message' => 'This obligation is approaching its expiry date. Review the register entry and instruct renewal if required.',
                'retry_count' => $status === 'retry' ? $this->random->between(1, 3) : 0,
                'error_message' => in_array($status, ['failed', 'retry'], true)
                    ? 'Gateway rejected the message: recipient mailbox unavailable.'
                    : null,
                'provider_message_id' => $delivered ? 'msg_'.str_pad((string) $this->random->between(1, 999_999_999), 9, '0', STR_PAD_LEFT) : null,
                'created_at' => $at,
                'updated_at' => $at,
            ];
        }

        $this->insertRows('notification_logs', $rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertRows(string $table, array $rows): void
    {
        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }

    /**
     * @template T
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
     * @param  list<int>  $values
     */
    private function pickFrom(array $values): int
    {
        return $values[$this->random->between(0, count($values) - 1)];
    }

    private function ip(): string
    {
        return sprintf(
            '10.%d.%d.%d',
            $this->random->between(0, 40),
            $this->random->between(0, 255),
            $this->random->between(1, 254),
        );
    }

    private function userAgent(): string
    {
        return $this->random->pick([
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0 Safari/537.36',
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
        ]);
    }
}
