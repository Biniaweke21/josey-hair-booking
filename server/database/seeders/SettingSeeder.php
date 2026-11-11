<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // general
            ['key' => 'salon_name', 'value' => 'Beauty By Josey', 'type' => 'string', 'group' => 'general'],
            ['key' => 'salon_tagline', 'value' => 'Beautiful Hair. Perfect Confidence.', 'type' => 'string', 'group' => 'general'],
            ['key' => 'salon_logo_path', 'value' => null, 'type' => 'string', 'group' => 'general'],
            ['key' => 'salon_email', 'value' => 'beautybyjosey2018@gmail.com', 'type' => 'string', 'group' => 'general'],
            ['key' => 'salon_phone', 'value' => '+1 (240) 462-7931', 'type' => 'string', 'group' => 'general'],
            ['key' => 'salon_address', 'value' => '7829 Eastern Ave NW, Silver Spring, MD 20910', 'type' => 'string', 'group' => 'general'],
            ['key' => 'salon_instagram', 'value' => 'hair_by_josey', 'type' => 'string', 'group' => 'general'],
            ['key' => 'salon_tiktok', 'value' => 'hair_by_josey', 'type' => 'string', 'group' => 'general'],
            ['key' => 'salon_timezone', 'value' => 'America/New_York', 'type' => 'string', 'group' => 'general'],
            ['key' => 'salon_currency', 'value' => 'USD', 'type' => 'string', 'group' => 'general'],

            // booking rules / booking window
            ['key' => 'booking_min_lead_minutes', 'value' => '60', 'type' => 'number', 'group' => 'booking'],
            ['key' => 'booking_max_advance_days', 'value' => '60', 'type' => 'number', 'group' => 'booking'],
            ['key' => 'booking_slot_interval_minutes', 'value' => '15', 'type' => 'number', 'group' => 'booking'],
            ['key' => 'booking_max_services_per_booking', 'value' => '0', 'type' => 'number', 'group' => 'booking'],
            ['key' => 'booking_allow_same_day', 'value' => '1', 'type' => 'boolean', 'group' => 'booking'],
            ['key' => 'booking_cancellation_window_hours', 'value' => '24', 'type' => 'number', 'group' => 'booking'],

            // capacity dashboard - remaining <= this is "Limited", remaining 0 is always "Full"
            ['key' => 'capacity_limited_threshold', 'value' => '2', 'type' => 'number', 'group' => 'booking'],

            // notifications
            ['key' => 'notifications_email_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'notifications_sms_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'booking_confirmation_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'appointment_updated_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'booking_cancelled_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'notifications'],

            // reminders
            ['key' => 'reminder_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'reminders'],
            ['key' => 'reminder_hours_before', 'value' => '24', 'type' => 'number', 'group' => 'reminders'],

            // thank you
            ['key' => 'thank_you_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'thank_you'],
            ['key' => 'thank_you_delay_hours', 'value' => '2', 'type' => 'number', 'group' => 'thank_you'],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(['key' => $setting['key']], $setting);
        }
    }
}
