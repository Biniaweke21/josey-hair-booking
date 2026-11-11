<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\NotificationService;
use App\Services\SettingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Queue appointment reminder notifications for bookings due within the reminder window';

    public function handle(SettingService $settings, NotificationService $notifications): int
    {
        if (! $settings->get('reminder_enabled', true)) {
            return self::SUCCESS;
        }

        $hoursBefore = (int) $settings->get('reminder_hours_before', 24);
        $windowStart = now()->addHours($hoursBefore);
        $windowEnd = now()->addMinutes(15)->addHours($hoursBefore);

        $candidates = Booking::whereIn('status', ['confirmed', 'checked_in'])
            ->whereBetween('booking_date', [now()->toDateString(), $windowEnd->toDateString()])
            ->with('customer')
            ->get()
            ->filter(function (Booking $booking) use ($windowStart, $windowEnd) {
                $bookingDateTime = Carbon::parse($booking->booking_date->toDateString().' '.$booking->start_time);

                return $bookingDateTime->between($windowStart, $windowEnd);
            });

        $queued = 0;

        foreach ($candidates as $booking) {
            if ($notifications->hasSentReminder($booking)) {
                continue;
            }

            if ($notifications->queue($booking, 'appointment_reminder')) {
                $queued++;
            }
        }

        $this->info("Queued {$queued} reminder notification(s).");

        return self::SUCCESS;
    }
}
