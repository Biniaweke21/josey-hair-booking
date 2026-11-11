<?php

namespace App\Services;

use App\Jobs\SendBookingNotification;
use App\Models\Booking;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;

class NotificationService
{
    /**
     * Maps a notification type to the setting that toggles it. Types with no
     * entry here are only gated by the global notifications_email_enabled switch.
     *
     * @var array<string, string>
     */
    private const TYPE_SETTINGS = [
        'appointment_reminder' => 'reminder_enabled',
        'thank_you' => 'thank_you_enabled',
        'booking_confirmation' => 'booking_confirmation_enabled',
        'appointment_updated' => 'appointment_updated_enabled',
        'booking_cancelled' => 'booking_cancelled_enabled',
    ];

    public function __construct(private readonly SettingService $settings) {}

    /**
     * Queues an email notification for a booking. Returns the created log
     * row, or null if the notification was skipped (disabled, no email on
     * file, or no active template for this type).
     *
     * @param  array<string, string>  $extraPlaceholders
     */
    public function queue(Booking $booking, string $type, array $extraPlaceholders = []): ?NotificationLog
    {
        if (! $this->settings->get('notifications_email_enabled', true)) {
            return null;
        }

        $settingKey = self::TYPE_SETTINGS[$type] ?? null;

        if ($settingKey && ! $this->settings->get($settingKey, true)) {
            return null;
        }

        $customer = $booking->customer;

        if (! $customer || ! $customer->email) {
            return null;
        }

        $template = NotificationTemplate::where('key', $type)
            ->where('channel', 'email')
            ->where('is_active', true)
            ->first();

        if (! $template) {
            return null;
        }

        $log = NotificationLog::create([
            'booking_id' => $booking->id,
            'customer_id' => $customer->id,
            'notification_template_id' => $template->id,
            'type' => $type,
            'channel' => 'email',
            'recipient' => $customer->email,
            'status' => 'pending',
        ]);

        SendBookingNotification::dispatch($log->id, $extraPlaceholders);

        return $log;
    }

    public function hasSentReminder(Booking $booking): bool
    {
        return $booking->notificationLogs()
            ->where('type', 'appointment_reminder')
            ->whereIn('status', ['pending', 'sent'])
            ->exists();
    }
}
