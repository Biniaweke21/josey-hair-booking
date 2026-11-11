<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\BusinessHour;
use App\Models\BusinessHourException;
use App\Models\Employee;
use App\Models\EmployeeWeeklySchedule;
use App\Support\ConvertsClockTime;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AvailabilityService
{
    use ConvertsClockTime;

    public function __construct(private readonly SettingService $settings) {}

    /**
     * @return array<int, array{start_time: string, end_time: string}>
     */
    public function slotsForDate(string $date, int $durationMinutes): array
    {
        $day = Carbon::parse($date)->startOfDay();

        if (! $this->isDateBookable($day)) {
            return [];
        }

        $window = $this->businessWindowFor($day);

        if ($window === null) {
            return [];
        }

        $capacity = $this->capacityForDate($day);

        if ($capacity < 1) {
            return [];
        }

        $interval = $this->slotInterval();
        $reserved = $this->reservedBookingWindows($day);
        $earliest = $this->earliestBookableMinute($day);

        $slots = [];

        for ($start = $window['open']; $start + $durationMinutes <= $window['close']; $start += $interval) {
            if ($earliest !== null && $start < $earliest) {
                continue;
            }

            if ($this->windowHasCapacity($start, $start + $durationMinutes, $interval, $capacity, $reserved)) {
                $slots[] = [
                    'start_time' => $this->formatTime($start),
                    'end_time' => $this->formatTime($start + $durationMinutes),
                ];
            }
        }

        return $slots;
    }

    /**
     * Re-checks capacity for one exact window. Pass $lock = true to run this
     * inside a transaction against row-locked booking rows, so two concurrent
     * requests can't both see the same free slot and both book it.
     */
    public function hasCapacityForWindow(string $date, string $startTime, string $endTime, bool $lock = false, ?int $ignoreBookingId = null): bool
    {
        $day = Carbon::parse($date)->startOfDay();

        if (! $this->isDateBookable($day)) {
            return false;
        }

        $window = $this->businessWindowFor($day);
        $start = $this->parseTime($startTime);
        $end = $this->parseTime($endTime);

        if ($window === null || $start < $window['open'] || $end > $window['close']) {
            return false;
        }

        $earliest = $this->earliestBookableMinute($day);

        if ($earliest !== null && $start < $earliest) {
            return false;
        }

        $capacity = $this->capacityForDate($day);

        if ($capacity < 1) {
            return false;
        }

        $interval = $this->slotInterval();
        $reserved = $this->reservedBookingWindows($day, $lock, $ignoreBookingId);

        return $this->windowHasCapacity($start, $end, $interval, $capacity, $reserved);
    }

    /**
     * How many employees are rostered to work on this date. This is the
     * only thing the booking engine knows about staffing - not who they
     * are, not what hours they keep. Employee assignment happens later,
     * manually, by the admin, and never affects this number.
     */
    public function capacityForDate(Carbon $day): int
    {
        return EmployeeWeeklySchedule::where('day_of_week', $day->dayOfWeek)
            ->where('is_active', true)
            ->whereHas('employee', fn ($query) => $query->where('is_active', true))
            ->count();
    }

    /**
     * Whether a specific employee can be manually assigned to a booking:
     * are they rostered to work that day at all, and are they not already
     * assigned (on any other booking's service line) to an overlapping
     * booking. No shift times, no breaks, no leave - the admin is expected
     * to keep the day's roster accurate themselves.
     */
    public function isEmployeeAvailable(Employee $employee, string $date, string $startTime, string $endTime, ?int $ignoreBookingId = null): bool
    {
        $day = Carbon::parse($date)->startOfDay();

        $rosteredToday = EmployeeWeeklySchedule::where('employee_id', $employee->id)
            ->where('day_of_week', $day->dayOfWeek)
            ->where('is_active', true)
            ->exists();

        if (! $rosteredToday) {
            return false;
        }

        return ! Booking::whereHas('items', fn ($query) => $query->where('employee_id', $employee->id))
            ->where('booking_date', $day->toDateString())
            ->whereIn('status', Booking::ACTIVE_STATUSES)
            ->when($ignoreBookingId, fn ($query) => $query->where('id', '!=', $ignoreBookingId))
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->exists();
    }

    /**
     * Employees who can actually be assigned to a booking's service line
     * right now: rostered to work that day, active, and not already
     * assigned to another overlapping booking. This is what staff-picker
     * dropdowns should show instead of the full employee list.
     *
     * @return Collection<int, Employee>
     */
    public function availableEmployeesFor(string $date, string $startTime, string $endTime, ?int $ignoreBookingId = null): Collection
    {
        $day = Carbon::parse($date)->startOfDay();

        $rosteredIds = EmployeeWeeklySchedule::where('day_of_week', $day->dayOfWeek)
            ->where('is_active', true)
            ->pluck('employee_id');

        $busyIds = BookingItem::whereNotNull('employee_id')
            ->whereHas('booking', function ($query) use ($day, $startTime, $endTime, $ignoreBookingId) {
                $query->where('booking_date', $day->toDateString())
                    ->whereIn('status', Booking::ACTIVE_STATUSES)
                    ->when($ignoreBookingId, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            })
            ->pluck('employee_id');

        return Employee::where('is_active', true)
            ->whereIn('id', $rosteredIds)
            ->whereNotIn('id', $busyIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * Bookings that still occupy capacity on this day - the single source
     * of truth for "which bookings count", shared by the availability
     * checker above and the capacity dashboard.
     */
    public function activeBookingsQuery(Carbon $day): Builder
    {
        return Booking::where('booking_date', $day->toDateString())
            ->whereIn('status', Booking::ACTIVE_STATUSES);
    }

    /**
     * @return array{open: int, close: int}|null
     */
    public function businessWindowFor(Carbon $day): ?array
    {
        $exception = BusinessHourException::where('date', $day->toDateString())->first();

        if ($exception) {
            if ($exception->is_closed || ! $exception->open_time || ! $exception->close_time) {
                return null;
            }

            return ['open' => $this->parseTime($exception->open_time), 'close' => $this->parseTime($exception->close_time)];
        }

        $hours = BusinessHour::where('day_of_week', $day->dayOfWeek)->first();

        if (! $hours || $hours->is_closed || ! $hours->open_time || ! $hours->close_time) {
            return null;
        }

        return ['open' => $this->parseTime($hours->open_time), 'close' => $this->parseTime($hours->close_time)];
    }

    public function slotInterval(): int
    {
        return max(5, (int) $this->settings->get('booking_slot_interval_minutes', 15));
    }

    private function windowHasCapacity(int $start, int $end, int $interval, int $capacity, array $reserved): bool
    {
        for ($checkpoint = $start; $checkpoint < $end; $checkpoint += $interval) {
            $reservedCount = 0;

            foreach ($reserved as $window) {
                if ($checkpoint >= $window['start'] && $checkpoint < $window['end']) {
                    $reservedCount++;
                }
            }

            if (($capacity - $reservedCount) < 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, array{start: int, end: int}>
     */
    private function reservedBookingWindows(Carbon $day, bool $lock = false, ?int $ignoreBookingId = null): array
    {
        $query = $this->activeBookingsQuery($day)
            ->when($ignoreBookingId, fn ($q) => $q->where('id', '!=', $ignoreBookingId));

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->get(['start_time', 'end_time'])
            ->map(fn (Booking $booking) => [
                'start' => $this->parseTime($booking->start_time),
                'end' => $this->parseTime($booking->end_time),
            ])
            ->all();
    }

    private function isDateBookable(Carbon $day): bool
    {
        $today = Carbon::today();

        if ($day->lt($today)) {
            return false;
        }

        if ($day->isSameDay($today) && ! $this->settings->get('booking_allow_same_day', true)) {
            return false;
        }

        $maxAdvanceDays = (int) $this->settings->get('booking_max_advance_days', 0);

        if ($maxAdvanceDays > 0 && $day->gt($today->copy()->addDays($maxAdvanceDays))) {
            return false;
        }

        return true;
    }

    private function earliestBookableMinute(Carbon $day): ?int
    {
        if (! $day->isToday()) {
            return null;
        }

        $minLead = (int) $this->settings->get('booking_min_lead_minutes', 0);
        $earliest = now()->addMinutes($minLead);

        return ($earliest->hour * 60) + $earliest->minute;
    }
}
