<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoProgressBookings extends Command
{
    protected $signature = 'bookings:auto-progress';

    protected $description = 'Auto check-in bookings once their start time passes, and auto check-out (sending the thank-you) once their end time passes';

    public function handle(BookingService $bookings): int
    {
        $now = now();
        $today = $now->toDateString();
        $checkedIn = 0;
        $checkedOut = 0;

        // confirmed → checked_in once start_time has arrived
        $toCheckIn = Booking::where('status', 'confirmed')
            ->whereDate('booking_date', '<=', $today)
            ->get()
            ->filter(fn (Booking $b) => $this->at($b, $b->start_time)->lessThanOrEqualTo($now));

        foreach ($toCheckIn as $booking) {
            $bookings->checkIn($booking);
            $checkedIn++;
        }

        // checked_in → completed once end_time has passed (checkOut queues the thank-you)
        $toCheckOut = Booking::where('status', 'checked_in')
            ->whereDate('booking_date', '<=', $today)
            ->get()
            ->filter(fn (Booking $b) => $this->at($b, $b->end_time)->lessThanOrEqualTo($now));

        foreach ($toCheckOut as $booking) {
            $bookings->checkOut($booking);
            $checkedOut++;
        }

        $this->info("Auto checked-in {$checkedIn}, checked-out {$checkedOut} booking(s).");

        return self::SUCCESS;
    }

    private function at(Booking $booking, string $time): Carbon
    {
        return Carbon::parse($booking->booking_date->toDateString().' '.$time);
    }
}
