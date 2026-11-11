<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'booking_confirmation',
                'channel' => 'email',
                'subject' => 'Your booking at {{salon_name}} is confirmed',
                'body' => "Hi {{customer_name}},\n\nYour appointment on {{booking_date}} at {{booking_time}} for {{services}} has been confirmed.\n\nSee you soon,\n{{salon_name}}",
            ],
            [
                'key' => 'booking_confirmation',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Hi {{customer_name}}, your booking at {{salon_name}} on {{booking_date}} at {{booking_time}} has been confirmed.',
            ],
            [
                'key' => 'appointment_reminder',
                'channel' => 'email',
                'subject' => 'Reminder: your appointment at {{salon_name}}',
                'body' => "Hi {{customer_name}},\n\nThis is a reminder for your appointment on {{booking_date}} at {{booking_time}} for {{services}}.\n\nSee you soon,\n{{salon_name}}",
            ],
            [
                'key' => 'appointment_reminder',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Reminder: your appointment at {{salon_name}} is on {{booking_date}} at {{booking_time}}.',
            ],
            [
                'key' => 'thank_you',
                'channel' => 'email',
                'subject' => 'Thank you for visiting {{salon_name}}',
                'body' => "Hi {{customer_name}},\n\nThank you for visiting {{salon_name}} today. We hope you loved your {{services}}!\n\nSee you next time,\n{{salon_name}}",
            ],
            [
                'key' => 'thank_you',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Thanks for visiting {{salon_name}} today, {{customer_name}}! We hope to see you again soon.',
            ],
            [
                'key' => 'appointment_updated',
                'channel' => 'email',
                'subject' => 'Your appointment at {{salon_name}} has been updated',
                'body' => "Hi {{customer_name}},\n\nYour appointment for {{services}} has been moved from {{old_date}} {{old_time}} to {{new_date}} {{new_time}}.\n\nSee you soon,\n{{salon_name}}",
            ],
            [
                'key' => 'appointment_updated',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Your appointment at {{salon_name}} moved to {{new_date}} {{new_time}}.',
            ],
            [
                'key' => 'booking_cancelled',
                'channel' => 'email',
                'subject' => 'Your appointment at {{salon_name}} was cancelled',
                'body' => "Hi {{customer_name}},\n\nYour appointment on {{booking_date}} at {{booking_time}} for {{services}} has been cancelled.{{cancel_reason}}\n\n{{salon_name}}",
            ],
            [
                'key' => 'booking_cancelled',
                'channel' => 'sms',
                'subject' => null,
                'body' => 'Your appointment at {{salon_name}} on {{booking_date}} at {{booking_time}} was cancelled.',
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::firstOrCreate(
                ['key' => $template['key'], 'channel' => $template['channel']],
                $template,
            );
        }
    }
}
