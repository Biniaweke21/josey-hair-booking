<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Services\SettingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendBookingNotification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, string>  $extraPlaceholders
     */
    public function __construct(
        private readonly int $notificationLogId,
        private readonly array $extraPlaceholders = [],
    ) {}

    public function handle(SettingService $settings): void
    {
        $log = NotificationLog::with(['booking.items', 'booking.customer', 'template'])->find($this->notificationLogId);

        if (! $log || $log->status !== 'pending') {
            return;
        }

        $booking = $log->booking;
        $template = $log->template;

        if (! $booking || ! $template) {
            $log->update(['status' => 'failed', 'error_message' => 'Booking or template no longer exists.']);

            return;
        }

        $placeholders = array_merge([
            '{{customer_name}}' => $booking->customer->name,
            '{{salon_name}}' => (string) $settings->get('salon_name', ''),
            '{{booking_date}}' => $booking->booking_date->toFormattedDateString(),
            '{{booking_time}}' => $booking->start_time,
            '{{services}}' => $booking->items->pluck('name_snapshot')->implode(', '),
        ], $this->extraPlaceholders);

        $subject = strtr($template->subject ?? '', $placeholders);
        $body = strtr($template->body, $placeholders);

        try {
            Mail::raw($body, function ($message) use ($log, $subject) {
                $message->to($log->recipient)->subject($subject);
            });

            $log->update(['status' => 'sent', 'sent_at' => now()]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }
}
