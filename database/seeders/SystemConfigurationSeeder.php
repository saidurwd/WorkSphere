<?php

namespace Database\Seeders;

use App\Models\FeatureFlag;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Runtime settings and feature flags.
 *
 * Both tables are declared by migration and written to only by the administration
 * screens, so a seeded database leaves every one of them empty. That is not
 * harmless: the settings screen renders an empty list, and any code path that
 * reads a setting through {@see Setting::typedValue()} falls back to its own
 * default rather than to the value the application expects an operator to have
 * set.
 *
 * Flags are seeded as a mix of on, off and part-way through a rollout, because a
 * flag that is either fully enabled or fully disabled demonstrates neither of
 * the two things the flag table exists for — a canary, or a rollout percentage.
 */
class SystemConfigurationSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * key => [value, type, group, label, description, is_encrypted].
     *
     * @var list<array{0: string, 1: mixed, 2: string, 3: string, 4: string, 5: bool}>
     */
    private const SETTINGS = [
        ['app.name', 'Worksphere', 'string', 'general', 'Application name', 'Shown in the header, in emails and on the sign-in screen.', false],
        ['app.timezone', 'Asia/Dhaka', 'string', 'general', 'Timezone', 'Times shown across the application are in this zone.', false],
        ['app.locale', 'en', 'string', 'general', 'Default locale', 'Language used when the browser sends no preference.', false],
        ['app.support_email', 'support@worksphere.test', 'string', 'general', 'Support email', 'Shown to users who cannot find an answer.', false],
        ['organisation.name', 'Worksphere Group', 'string', 'organisation', 'Organisation name', 'Used on generated documents and the sign-in screen.', false],
        ['organisation.registration', 'C-118442', 'string', 'organisation', 'Registration number', 'Printed on contracts and statutory notices.', false],
        ['organisation.timezone', 'Asia/Dhaka', 'string', 'organisation', 'Operating timezone', 'Default zone for meetings and deadlines.', false],

        ['security.password_min_length', 12, 'integer', 'security', 'Minimum password length', 'Applies to new and changed passwords.', false],
        ['security.password_expiry_days', 90, 'integer', 'security', 'Password expiry', 'Days before a password must be changed. Zero disables expiry.', false],
        ['security.max_failed_attempts', 5, 'integer', 'security', 'Failed sign-in attempts', 'Attempts before an account is locked.', false],
        ['security.lockout_minutes', 15, 'integer', 'security', 'Lockout duration', 'How long an account stays locked.', false],
        ['security.session_lifetime_minutes', 120, 'integer', 'security', 'Session lifetime', 'An idle session is ended after this long.', false],
        ['security.require_two_factor', false, 'boolean', 'security', 'Require two-factor authentication', 'Applies to administrators and super administrators.', false],
        ['security.allowed_ip_range', '10.0.0.0/8, 192.168.0.0/16', 'string', 'security', 'Trusted networks', 'Networks permitted to reach the administration screens.', false],
        ['security.api_token_expiry_days', 90, 'integer', 'security', 'API token lifetime', 'Tokens are revoked after this many days.', false],

        ['tasks.default_due_days', 7, 'integer', 'workflow', 'Default task due window', 'Days from creation to due date when none is given.', false],
        ['tasks.default_priority', 'medium', 'string', 'workflow', 'Default task priority', 'Applied when a task is raised without one.', false],
        ['tasks.auto_transfer_on_leave', true, 'boolean', 'workflow', 'Reassign on leave', 'Tasks are offered for transfer when an owner goes on leave.', false],
        ['tasks.attachment_disk', 'public', 'string', 'workflow', 'Attachment disk', 'Disk that task attachments are written to.', false],
        ['tasks.max_attachment_mb', 20, 'integer', 'workflow', 'Maximum attachment size', 'Largest single upload, in megabytes.', false],

        ['meetings.default_duration_minutes', 60, 'integer', 'meetings', 'Default meeting length', 'Applied when a meeting type sets no duration.', false],
        ['meetings.reminder_minutes_before', 30, 'integer', 'meetings', 'Reminder lead time', 'How far ahead invitees are reminded.', false],
        ['meetings.auto_publish_minutes', false, 'boolean', 'meetings', 'Auto-publish minutes', 'Publishes minutes immediately after approval. Off by design: minutes are published deliberately.', false],
        ['meetings.require_chairperson', true, 'boolean', 'meetings', 'Chairperson required', 'A meeting cannot be scheduled without one.', false],

        ['obligations.default_notice_days', 30, 'integer', 'obligations', 'Default renewal notice', 'Days before expiry that a renewal should be instructed.', false],
        ['obligations.escalate_after_days', 7, 'integer', 'obligations', 'Escalation delay', 'Days past expiry before an obligation is escalated.', false],
        ['obligations.auto_create_renewal_task', true, 'boolean', 'obligations', 'Create renewal task', 'Raises a task when an obligation enters its renewal window.', false],
        ['obligations.currency', 'BDT', 'string', 'obligations', 'Reporting currency', 'Currency the register reports in.', false],
        ['obligations.high_risk_threshold', 1000000, 'float', 'obligations', 'High-risk spend threshold', 'Estimated cost above which risk defaults to high.', false],

        ['notifications.digest', 'weekly', 'string', 'notifications', 'Digest frequency', 'How often the summary notification is sent.', false],
        ['notifications.send_at', '09:00', 'string', 'notifications', 'Digest send time', 'Local time the digest is sent.', false],
        ['notifications.from_name', 'Worksphere', 'string', 'notifications', 'Sender name', 'Name shown as the sender of outbound notifications.', false],
        ['notifications.from_email', 'notifications@worksphere.test', 'string', 'notifications', 'Sender address', 'Address outbound notifications are sent from.', false],
        ['notifications.webhook_secret', '', 'string', 'notifications', 'Webhook signing secret', 'Used to sign outbound webhook payloads. Rotated with the application key.', true],

        ['reporting.fiscal_year_start', '01-01', 'string', 'reporting', 'Fiscal year start', 'Month and day the financial year begins.', false],
        ['reporting.timezone', 'Asia/Dhaka', 'string', 'reporting', 'Reporting timezone', 'Zone that report boundaries are calculated in.', false],
        ['reports.retention_months', 36, 'integer', 'reporting', 'Report retention', 'Months of generated reports kept before cleanup.', false],
    ];

    /**
     * key => [name, description, type, value, enabled, rollout, variants, target_roles].
     *
     * @var list<array{0: string, 1: string, 2: string, 3: mixed, 4: bool, 5: int, 6: list<array{value: mixed, weight: int}>|null, 7: list<string>|null}>
     */
    private const FLAGS = [
        ['todo.recurring', 'Recurring to-dos', 'Generate the next occurrence of a repeating to-do when one is completed.', 'boolean', false, 0, null, null],
        ['todo.mentions', 'To-do mentions', 'Notify a person named with @ in a to-do description.', 'boolean', true, 100, null, null],
        ['tasks.time_tracking', 'Time tracking', 'Record logged time against a task.', 'boolean', true, 100, null, null],
        ['tasks.transfer_approval', 'Task transfer approval', 'Require a manager to approve a task transfer.', 'boolean', false, 0, null, ['manager', 'admin', 'super-admin']],
        ['tasks.subtasks', 'Sub-task hierarchy', 'Allow a task to be broken into sub-tasks.', 'boolean', true, 100, null, null],
        ['meetings.minutes_workflow', 'Minutes approval workflow', 'Route minutes through a multi-step approval chain before publication.', 'boolean', true, 100, null, null],
        ['meetings.recurrence', 'Recurring meetings', 'Schedule a series of instances from a single meeting definition.', 'boolean', true, 100, null, null],
        ['obligations.escalation', 'Obligation escalation', 'Escalate an obligation to the department head as the expiry date approaches.', 'boolean', true, 100, null, null],
        ['obligations.auto_renew_task', 'Automatic renewal task', 'Raise a task automatically when an obligation enters its renewal window.', 'boolean', true, 0, null, ['manager', 'admin', 'super-admin']],
        ['dashboards.workload', 'Workload report', 'The workload report and its export.', 'percentage', true, 40, null, null],
        ['search.fuzzy', 'Fuzzy search', 'Tolerate typos in global search.', 'variant', true, 0, [['value', 40], ['value', 35], ['value', 25]], null],
        ['exports.background', 'Background exports', 'Run large exports on the queue instead of the request.', 'boolean', false, 0, null, null],
        ['api.versioning', 'API versioning', 'Version the public API surface under /api/v1.', 'boolean', true, 100, null, ['admin', 'super-admin']],
        ['ui.compact_density', 'Compact density', 'A denser table layout for users who ask for it.', 'variant', true, 0, [['comfortable', 50], ['compact', 50]], null],
    ];

    public function run(): void
    {
        $users = User::query()->where('status', 'active')->get();
        $owner = $users->first();

        foreach (self::SETTINGS as [$key, $value, $type, $group, $label, $description, $isEncrypted]) {
            // `updateOrCreate` keyed on the setting name so re-seeding refreshes a
            // changed default instead of failing on the unique index.
            Setting::query()->updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value === '' ? null : $value,
                    'type' => $type,
                    'group' => $group,
                    'label' => $label,
                    'description' => $description,
                    'is_encrypted' => $isEncrypted,
                    'updated_by' => $owner?->id,
                ],
            );
        }

        foreach (self::FLAGS as [$key, $name, $description, $type, $enabled, $rollout, $variants, $targetRoles]) {
            FeatureFlag::query()->updateOrCreate(
                ['key' => $key],
                [
                    'name' => $name,
                    'description' => $description,
                    'type' => $type,
                    'value' => $type === 'boolean' ? $enabled : null,
                    'is_enabled' => $enabled,
                    'rollout_percentage' => $rollout,
                    'variants' => $variants,
                    'target_roles' => $targetRoles,
                    'created_by' => $owner?->id,
                    'updated_by' => $owner?->id,
                ],
            );
        }

        $this->command?->info(sprintf(
            '  Settings: %d, feature flags: %d',
            Setting::query()->count(),
            FeatureFlag::query()->count(),
        ));
    }
}
