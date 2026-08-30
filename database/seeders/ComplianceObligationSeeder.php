<?php

namespace Database\Seeders;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Models\Company;
use App\Models\EscalationRule;
use App\Models\NotificationRule;
use App\Models\ObligationCategory;
use App\Models\ObligationType;
use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComplianceObligationSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedCompanies();
        $this->seedObligationTypes();
        $this->seedObligationCategories();
        $this->seedNotificationRules();
        $this->seedEscalationRules();
        $this->seedApprovalWorkflows();
        $this->seedPermissions();
    }

    private function seedCompanies(): void
    {
        $companies = [
            ['COMP-001', 'Head Office', 'active'],
            ['COMP-002', 'Chittagong Hub', 'active'],
            ['COMP-003', 'Uttara Branch', 'active'],
        ];

        foreach ($companies as $company) {
            Company::firstOrCreate(
                ['company_code' => $company[0]],
                ['company_name' => $company[1], 'status' => $company[2]]
            );
        }
    }

    private function seedObligationTypes(): void
    {
        $types = [
            ['Software License', 'Software licensing obligations', 'medium', null, null, 'medium', false, true, [90, 60, 30, 15, 7, 3, 1]],
            ['Hardware License', 'Hardware licensing obligations', 'medium', null, null, 'medium', false, true, [90, 60, 30, 15, 7, 3, 1]],
            ['SSL Certificate', 'SSL/TLS certificate renewals', 'high', null, null, 'high', true, true, [90, 60, 30, 15, 7, 3, 1]],
            ['Domain Renewal', 'Domain name renewals', 'medium', null, null, 'medium', false, true, [90, 60, 30, 15, 7, 3, 1]],
            ['AMC', 'Annual Maintenance Contracts', 'high', null, null, 'high', true, true, [90, 60, 30, 15, 7, 3, 1]],
            ['MMC', 'Maintenance Management Contracts', 'medium', null, null, 'medium', true, true, [90, 60, 30, 15, 7, 3, 1]],
            ['Insurance', 'Insurance policy renewals', 'high', null, null, 'high', true, true, [90, 60, 30, 15, 7, 3, 1]],
            ['Government License', 'Government and regulatory licenses', 'critical', null, null, 'critical', true, true, [90, 60, 30, 15, 7, 3, 1]],
            ['Regulatory Certificate', 'Regulatory certificates and inspections', 'critical', null, null, 'critical', true, true, [90, 60, 30, 15, 7, 3, 1]],
            ['Inspection', 'Periodic inspections', 'medium', null, null, 'medium', true, true, [90, 60, 30, 15, 7, 3, 1]],
            ['Warranty', 'Warranty expiry tracking', 'medium', null, null, 'medium', false, true, [90, 60, 30, 15, 7, 3, 1]],
            ['SaaS Subscription', 'SaaS subscription renewals', 'high', null, null, 'high', false, true, [90, 60, 30, 15, 7, 3, 1]],
            ['Vendor Contract', 'Vendor service contracts', 'high', null, null, 'high', true, true, [90, 60, 30, 15, 7, 3, 1]],
            ['Service Contract', 'General service contracts', 'medium', null, null, 'medium', true, true, [90, 60, 30, 15, 7, 3, 1]],
            ['Equipment Maintenance', 'Equipment maintenance obligations', 'medium', null, null, 'medium', true, true, [90, 60, 30, 15, 7, 3, 1]],
            ['Other', 'Other organizational obligations', 'low', null, null, 'medium', false, true, [90, 60, 30, 15, 7, 3, 1]],
        ];

        foreach ($types as $type) {
            ObligationType::firstOrCreate(
                ['type_name' => $type[0]],
                [
                    'description' => $type[1],
                    'active' => true,
                    'default_priority' => $type[2],
                    'default_recurrence_type' => $type[3],
                    'default_recurrence_interval' => $type[4],
                    'default_risk_level' => $type[5],
                    'approval_required' => $type[6],
                    'renewal_required' => $type[7],
                    'default_reminder_days' => json_encode($type[8]),
                ]
            );
        }
    }

    private function seedObligationCategories(): void
    {
        $categories = [
            ['IT', 'Information Technology obligations'],
            ['Finance', 'Finance-related obligations'],
            ['HR', 'Human Resources obligations'],
            ['Administration', 'Administration obligations'],
            ['Procurement', 'Procurement obligations'],
            ['Legal', 'Legal obligations'],
            ['Operations', 'Operations obligations'],
            ['Engineering', 'Engineering obligations'],
            ['Estate', 'Estate and facilities obligations'],
            ['Security', 'Security obligations'],
            ['Compliance', 'Compliance obligations'],
            ['Other', 'Other obligations'],
        ];

        foreach ($categories as $category) {
            ObligationCategory::firstOrCreate(
                ['category_name' => $category[0]],
                ['description' => $category[1], 'active' => true]
            );
        }
    }

    private function seedNotificationRules(): void
    {
        $reminderDays = [90, 60, 30, 15, 7, 3, 1];
        $levels = ['OWNER', 'BACKUP_OWNER', 'MANAGER', 'DEPARTMENT_HEAD', 'APPROVER', 'SPECIFIC_USER'];
        $channels = ['IN_APP', 'EMAIL'];

        foreach ($channels as $channel) {
            foreach ($reminderDays as $days) {
                $level = $levels[array_rand($levels)];

                $subjectTemplate = $channel === 'EMAIL'
                    ? '[Reminder] {obligation_title} expires in {days_remaining} days'
                    : 'Reminder: {obligation_title} expires in {days_remaining} days';

                $messageTemplate = 'Obligation {obligation_no} ({obligation_title}) expires in {days_remaining} days. Priority: {priority}. Risk: {risk_level}.';

                NotificationRule::firstOrCreate(
                    [
                        'obligation_type_id' => null,
                        'days_before_expiry' => $days,
                        'recipient_type' => 'OWNER',
                        'channel' => $channel,
                    ],
                    [
                        'notification_level' => $level,
                        'subject_template' => $subjectTemplate,
                        'message_template' => $messageTemplate,
                        'active' => true,
                    ]
                );
            }
        }
    }

    private function seedEscalationRules(): void
    {
        $channels = ['IN_APP', 'EMAIL'];

        foreach ($channels as $channel) {
            foreach ([1, 7, 30] as $days) {
                EscalationRule::firstOrCreate(
                    [
                        'obligation_type_id' => null,
                        'days_before_expiry' => $days,
                        'days_after_expiry' => null,
                        'escalation_level' => 'ESCALATION',
                        'recipient_type' => 'DEPARTMENT_HEAD',
                        'channel' => $channel,
                    ],
                    ['active' => true]
                );
            }

            foreach ([1, 7, 30] as $days) {
                EscalationRule::firstOrCreate(
                    [
                        'obligation_type_id' => null,
                        'days_before_expiry' => null,
                        'days_after_expiry' => $days,
                        'escalation_level' => 'POST_EXPIRY',
                        'recipient_type' => 'DEPARTMENT_HEAD',
                        'channel' => $channel,
                    ],
                    ['active' => true]
                );
            }
        }
    }

    private function seedApprovalWorkflows(): void
    {
        $workflow = ApprovalWorkflow::firstOrCreate(
            ['name' => 'Standard Renewal Approval'],
            [
                'description' => 'Default approval workflow for obligation renewals',
                'active' => true,
            ]
        );

        if ($workflow->steps()->count() === 0) {
            $steps = [
                ['step_order' => 1, 'approver_type' => 'Department Head', 'required' => true],
                ['step_order' => 2, 'approver_type' => 'Finance Manager', 'required' => true],
                ['step_order' => 3, 'approver_type' => 'Managing Director', 'required' => false],
            ];

            foreach ($steps as $step) {
                ApprovalWorkflowStep::create(array_merge($step, ['approval_workflow_id' => $workflow->id]));
            }
        }
    }

    private function seedPermissions(): void
    {
        $permissions = [
            'obligation.view',
            'obligation.create',
            'obligation.update',
            'obligation.delete',
            'obligation.assign',
            'obligation.renew',
            'obligation.approve',
            'obligation.manage_documents',
            'obligation.manage_rules',
            'obligation.manage_settings',
            'obligation.view_reports',
            'obligation.view_all_departments',
        ];

        $roleId = DB::table('roles')->value('id');

        if ($roleId) {
            foreach ($permissions as $permissionName) {
                $permission = Permission::firstOrCreate(['permission_name' => $permissionName]);
                RolePermission::firstOrCreate([
                    'role_id' => $roleId,
                    'permission_id' => $permission->id,
                ]);
            }
        }
    }
}
