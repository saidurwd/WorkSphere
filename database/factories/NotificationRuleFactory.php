<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Obligations\Models\NotificationRule;
use Modules\Obligations\Models\ObligationType;

/**
 * @extends Factory<NotificationRule>
 *
 * The templates the expiry notifier renders. The subject and message templates
 * contain real placeholders: a factory with `{obligation}` in the message is a
 * fixture that would render correctly, and one without would silently exercise a
 * replacement path that is never reached in production.
 */
class NotificationRuleFactory extends Factory
{
    protected $model = NotificationRule::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'obligation_type_id' => ObligationType::factory(),
            'days_before_expiry' => 30,
            'notification_level' => 'reminder',
            'recipient_type' => 'owner',
            'channel' => 'mail',
            'subject_template' => 'Obligation {obligation_no} expires on {expiry_date}',
            'message_template' => '{obligation_no} expires on {expiry_date}.',
            'active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['active' => false]);
    }
}
