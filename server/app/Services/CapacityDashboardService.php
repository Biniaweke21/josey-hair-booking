<?php

namespace App\Services;

use App\Models\Booking;
use App\Support\ConvertsClockTime;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * Presents capacity that the booking engine already tracks in a shape an
 * admin can read at a glance. Computes nothing the booking engine doesn't
 * already compute - it only reuses AvailabilityService::capacityForDate()
 * and the same "active booking" query the availability checker uses.
 */
class CapacityDashboardService
{
    use ConvertsClockTime;

    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly SettingService $settings,
    ) {}

    public function summary(string $date): array
    {
        $day = $this->assertValidDate($date);
        $capacity = $this->availability->capacityForDate($day);
        $timeline = $this->timelineData($day);

        $peakReserved = $timeline === [] ? 0 : max(array_column($timeline, 'reserved'));

        return [
            'date' => $day->toDateString(),
            'working_staff' => $capacity,
            'capacity' => $capacity,
            'reserved' => $peakReserved,
            'remaining' => max(0, $capacity - $peakReserved),
            'total_bookings' => $this->activeBookings($day)->count(),
        ];
    }

    /**
     * @return array<int, array{time: string, capacity: int, reserved: int, remaining: int, status: string}>
     */
    public function timeline(string $date): array
    {
        return $this->timelineData($this->assertValidDate($date));
    }

    /**
     * Bookings occupying a given checkpoint - why capacity is what it is.
     */
    public function bookingsAtInterval(string $date, string $time): Collection
    {
        $day = $this->assertValidDate($date);
        $minute = $this->parseTime($time);

        return $this->activeBookings($day)
            ->filter(fn (Booking $booking) => $minute >= $this->parseTime($booking->start_time)
                && $minute < $this->parseTime($booking->end_time))
            ->values();
    }

    public function analytics(string $date): array
    {
        $day = $this->assertValidDate($date);
        $timeline = $this->timelineData($day);

        if ($timeline === []) {
            return [
                'peak_hour' => null,
                'least_busy_hour' => null,
                'average_capacity_used_percent' => 0.0,
                'max_concurrent_bookings' => 0,
                'capacity_utilization_percent' => 0.0,
            ];
        }

        $capacity = $timeline[0]['capacity'];

        $reservedByHour = [];
        foreach ($timeline as $row) {
            $hour = substr($row['time'], 0, 2).':00';
            $reservedByHour[$hour][] = $row['reserved'];
        }

        $hourAverages = array_map(fn (array $values) => array_sum($values) / count($values), $reservedByHour);

        $peakHour = array_search(max($hourAverages), $hourAverages, true);
        $leastBusyHour = array_search(min($hourAverages), $hourAverages, true);

        $utilization = $capacity > 0
            ? array_sum(array_map(fn (array $row) => $row['reserved'] / $capacity, $timeline)) / count($timeline)
            : 0.0;

        $maxConcurrent = max(array_column($timeline, 'reserved'));

        return [
            'peak_hour' => $peakHour,
            'least_busy_hour' => $leastBusyHour,
            'average_capacity_used_percent' => round($utilization * 100, 1),
            'max_concurrent_bookings' => $maxConcurrent,
            'capacity_utilization_percent' => round($utilization * 100, 1),
        ];
    }

    /**
     * The single per-day computation everything else derives from - built
     * once and cached briefly so repeated dashboard views (summary, then
     * timeline, then analytics) don't each re-walk the day independently.
     *
     * ponytail: 60s TTL, no active invalidation on booking writes. The
     * booking engine's own capacity check is always live and never reads
     * this cache, so correctness isn't affected - only dashboard freshness,
     * which lags by at most a minute. Wire cache-busting into BookingService
     * if that lag ever matters.
     *
     * @return array<int, array{time: string, capacity: int, reserved: int, remaining: int, status: string}>
     */
    private function timelineData(Carbon $day): array
    {
        return Cache::remember("capacity-dashboard.timeline.{$day->toDateString()}", 60, function () use ($day) {
            $window = $this->availability->businessWindowFor($day);

            if ($window === null) {
                return [];
            }

            $capacity = $this->availability->capacityForDate($day);
            $interval = $this->availability->slotInterval();
            $limitedThreshold = (int) $this->settings->get('capacity_limited_threshold', 2);

            $reservedWindows = $this->activeBookings($day)->map(fn (Booking $booking) => [
                'start' => $this->parseTime($booking->start_time),
                'end' => $this->parseTime($booking->end_time),
            ]);

            $rows = [];

            for ($minute = $window['open']; $minute < $window['close']; $minute += $interval) {
                $reserved = $reservedWindows->filter(
                    fn (array $w) => $minute >= $w['start'] && $minute < $w['end'],
                )->count();

                $remaining = max(0, $capacity - $reserved);

                $rows[] = [
                    'time' => $this->formatTime($minute),
                    'capacity' => $capacity,
                    'reserved' => $reserved,
                    'remaining' => $remaining,
                    'status' => $this->statusFor($remaining, $limitedThreshold),
                ];
            }

            return $rows;
        });
    }

    private function activeBookings(Carbon $day): Collection
    {
        return $this->availability->activeBookingsQuery($day)
            ->with('customer')
            ->orderBy('start_time')
            ->get();
    }

    private function statusFor(int $remaining, int $limitedThreshold): string
    {
        return match (true) {
            $remaining <= 0 => 'Full',
            $remaining <= $limitedThreshold => 'Limited',
            default => 'Available',
        };
    }

    private function assertValidDate(string $date): Carbon
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))) {
            throw ValidationException::withMessages(['date' => ['The date is not a valid calendar date.']]);
        }

        return Carbon::parse($date)->startOfDay();
    }
}
