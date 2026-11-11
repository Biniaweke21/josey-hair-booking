<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Customer;
use App\Models\Employee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingService
{
    /**
     * Allowed status transitions.
     *
     * @var array<string, array<int, string>>
     */
    private const TRANSITIONS = [
        'confirmed' => ['checked_in', 'cancelled', 'no_show'],
        'checked_in' => ['completed'],
        'completed' => [],
        'cancelled' => [],
        'no_show' => [],
    ];

    /**
     * Bookings can only be moved once they're this far from done.
     */
    private const RESCHEDULABLE_STATUSES = ['confirmed'];

    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly BookingPricingService $pricing,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array{name: string, email?: ?string, phone?: ?string}  $customerData
     * @param  array<int, array{service_id: int, variation_option_ids?: array<int, int>, add_on_ids?: array<int, int>}>  $items
     */
    public function create(
        array $customerData,
        string $bookingDate,
        string $startTime,
        array $items,
        ?string $notes = null,
        string $source = 'website',
    ): Booking {
        $pricing = $this->pricing->price($items);
        $endTime = $this->addMinutes($startTime, $pricing['total_duration_minutes']);

        return Cache::lock("booking-date-{$bookingDate}", 10)->block(5, function () use (
            $customerData, $bookingDate, $startTime, $endTime, $pricing, $notes, $source
        ) {
            return DB::transaction(function () use ($customerData, $bookingDate, $startTime, $endTime, $pricing, $notes, $source) {
                if (! $this->availability->hasCapacityForWindow($bookingDate, $startTime, $endTime, lock: true)) {
                    throw ValidationException::withMessages([
                        'start_time' => ['This time slot is no longer available. Please choose another time.'],
                    ]);
                }

                $customer = $this->resolveCustomer($customerData);

                // No approval step: capacity was just validated above, so
                // the booking is confirmed the moment it's created.
                $booking = Booking::create([
                    'customer_id' => $customer->id,
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                    'booking_date' => $bookingDate,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'subtotal' => $pricing['subtotal'],
                    'addon_total' => $pricing['addon_total'],
                    'total_price' => $pricing['total_price'],
                    'total_duration_minutes' => $pricing['total_duration_minutes'],
                    'notes' => $notes,
                    'source' => $source,
                ]);

                foreach ($pricing['items'] as $sortOrder => $itemData) {
                    $item = $booking->items()->create([
                        'service_id' => $itemData['service_id'],
                        'name_snapshot' => $itemData['name_snapshot'],
                        'price_snapshot' => $itemData['price_snapshot'],
                        'duration_snapshot' => $itemData['duration_snapshot'],
                        'sort_order' => $sortOrder,
                    ]);

                    foreach ($itemData['variation_options'] as $variationOption) {
                        $item->variationOptions()->create($variationOption);
                    }

                    foreach ($itemData['add_ons'] as $addOn) {
                        $item->addOns()->create($addOn);
                    }
                }

                $this->recordEvent($booking, 'created', 'Booking created', ['source' => $source]);

                $booking->load(['items.variationOptions', 'items.addOns', 'customer']);
                $this->notifications->queue($booking, 'booking_confirmation');

                return $booking;
            });
        });
    }

    /**
     * Assigns (or unassigns, if $employee is null) one employee to one
     * service line of a booking. Employee assignment is always manual,
     * always per-service, and never part of availability - it happens
     * only after the booking already exists.
     */
    public function assignEmployeeToItem(BookingItem $item, ?Employee $employee): BookingItem
    {
        $booking = $item->booking;

        if (in_array($booking->status, ['completed', 'cancelled', 'no_show'], true)) {
            throw ValidationException::withMessages([
                'employee_id' => ["Cannot assign staff to a {$booking->status} booking."],
            ]);
        }

        if ($employee && ! $this->availability->isEmployeeAvailable(
            $employee,
            $booking->booking_date->toDateString(),
            $booking->start_time,
            $booking->end_time,
            $booking->id,
        )) {
            throw ValidationException::withMessages([
                'employee_id' => ["{$employee->name} is not rostered to work that day, or is already assigned to another booking at an overlapping time."],
            ]);
        }

        $item->update(['employee_id' => $employee?->id]);

        $this->recordEvent(
            $booking,
            'employee_assigned',
            $employee
                ? "{$item->name_snapshot} assigned to {$employee->name}"
                : "{$item->name_snapshot} unassigned",
        );

        return $item->fresh(['employee']);
    }

    public function reschedule(Booking $booking, string $newDate, string $newStartTime): Booking
    {
        if (! in_array($booking->status, self::RESCHEDULABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'booking_date' => ["Cannot reschedule a {$booking->status} booking."],
            ]);
        }

        $newEndTime = $this->addMinutes($newStartTime, $booking->total_duration_minutes);

        return Cache::lock("booking-date-{$newDate}", 10)->block(5, function () use ($booking, $newDate, $newStartTime, $newEndTime) {
            return DB::transaction(function () use ($booking, $newDate, $newStartTime, $newEndTime) {
                if (! $this->availability->hasCapacityForWindow($newDate, $newStartTime, $newEndTime, lock: true, ignoreBookingId: $booking->id)) {
                    throw ValidationException::withMessages([
                        'start_time' => ['This time slot is not available. Please choose another time.'],
                    ]);
                }

                foreach ($booking->employees as $employee) {
                    if (! $this->availability->isEmployeeAvailable($employee, $newDate, $newStartTime, $newEndTime, $booking->id)) {
                        throw ValidationException::withMessages([
                            'start_time' => ["{$employee->name} is not available at the new time. Reassign staff first."],
                        ]);
                    }
                }

                $oldDate = $booking->booking_date->toDateString();
                $oldStartTime = $booking->start_time;

                $booking->update([
                    'booking_date' => $newDate,
                    'start_time' => $newStartTime,
                    'end_time' => $newEndTime,
                ]);

                $this->recordEvent($booking, 'rescheduled', "Rescheduled from {$oldDate} {$oldStartTime} to {$newDate} {$newStartTime}");

                $booking = $booking->fresh(['customer', 'items']);
                $this->notifications->queue($booking, 'appointment_updated', [
                    '{{old_date}}' => $oldDate,
                    '{{old_time}}' => $oldStartTime,
                    '{{new_date}}' => $newDate,
                    '{{new_time}}' => $newStartTime,
                ]);

                return $booking;
            });
        });
    }

    public function checkIn(Booking $booking): Booking
    {
        return $this->transitionStatus($booking, 'checked_in');
    }

    public function checkOut(Booking $booking): Booking
    {
        $booking = $this->transitionStatus($booking, 'completed');

        if ($this->notifications->queue($booking->load(['customer', 'items']), 'thank_you')) {
            $this->recordEvent($booking, 'thank_you_sent', 'Thank-you notification queued');
        }

        return $booking->fresh();
    }

    public function transitionStatus(Booking $booking, string $newStatus, ?string $cancelReason = null): Booking
    {
        $allowed = self::TRANSITIONS[$booking->status] ?? [];

        if (! in_array($newStatus, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => ["Cannot move a booking from '{$booking->status}' to '{$newStatus}'."],
            ]);
        }

        $updates = ['status' => $newStatus];

        match ($newStatus) {
            'confirmed' => $updates['confirmed_at'] = now(),
            'checked_in' => $updates['checked_in_at'] = now(),
            'completed' => $updates['completed_at'] = now(),
            'cancelled' => $updates += ['cancelled_at' => now(), 'cancel_reason' => $cancelReason],
            default => null,
        };

        $booking->update($updates);

        $this->recordEvent(
            $booking,
            $newStatus,
            "Status changed to {$newStatus}".($cancelReason ? ": {$cancelReason}" : ''),
        );

        $booking = $booking->fresh();

        if ($newStatus === 'cancelled') {
            $this->notifications->queue($booking->load(['customer', 'items']), 'booking_cancelled', [
                '{{cancel_reason}}' => $cancelReason ? " Reason: {$cancelReason}" : '',
            ]);
        }

        return $booking;
    }

    private function recordEvent(Booking $booking, string $type, string $description, array $meta = []): void
    {
        $booking->events()->create([
            'type' => $type,
            'description' => $description,
            'meta' => $meta,
            'caused_by_user_id' => Auth::id(),
        ]);
    }

    private function resolveCustomer(array $data): Customer
    {
        $customer = null;

        if (! empty($data['email'])) {
            $customer = Customer::where('email', $data['email'])->first();
        }

        if (! $customer && ! empty($data['phone'])) {
            $customer = Customer::where('phone', $data['phone'])->first();
        }

        if ($customer) {
            return $customer;
        }

        return Customer::create([
            'name' => $data['name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
        ]);
    }

    private function addMinutes(string $time, int $minutes): string
    {
        [$hours, $mins] = explode(':', $time);
        $total = ((int) $hours * 60) + (int) $mins + $minutes;

        return sprintf('%02d:%02d', intdiv($total, 60), $total % 60);
    }
}
