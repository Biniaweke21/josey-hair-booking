<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\AssignItemEmployeeRequest;
use App\Http\Requests\Booking\RescheduleBookingRequest;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Http\Requests\Booking\UpdateBookingStatusRequest;
use App\Http\Resources\BookingEventResource;
use App\Http\Resources\BookingItemResource;
use App\Http\Resources\BookingResource;
use App\Http\Resources\EmployeeResource;
use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\Employee;
use App\Services\AvailabilityService;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookings,
        private readonly AvailabilityService $availability,
    ) {}

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $booking = $this->bookings->create(
            $request->input('customer'),
            $request->input('booking_date'),
            $request->input('start_time'),
            $request->input('services'),
            $request->input('notes'),
            'website',
        );

        return response()->json([
            'message' => 'Booking confirmed.',
            'data' => new BookingResource($booking),
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('bookings.view');

        $bookings = Booking::with(['customer', 'employees'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('booking_date'), fn ($query) => $query->where('booking_date', $request->string('booking_date')))
            ->when($request->filled('customer_id'), fn ($query) => $query->where('customer_id', $request->integer('customer_id')))
            ->when(
                $request->filled('employee_id'),
                fn ($query) => $query->whereHas('employees', fn ($q) => $q->where('employees.id', $request->integer('employee_id'))),
            )
            ->orderByDesc('booking_date')
            ->orderByDesc('start_time')
            ->paginate(20);

        return response()->json([
            'data' => BookingResource::collection($bookings),
            'meta' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    public function show(Booking $booking): JsonResponse
    {
        $this->authorize('bookings.view');

        return response()->json([
            'data' => new BookingResource($booking->load([
                'customer', 'employees', 'items.employee', 'items.variationOptions', 'items.addOns',
            ])),
        ]);
    }

    /**
     * Assign (or unassign, with employee_id: null) one employee to one
     * service line of a booking. Wash -> Sarah, Color -> Emma, etc.
     */
    public function assignItemEmployee(AssignItemEmployeeRequest $request, Booking $booking, BookingItem $bookingItem): JsonResponse
    {
        $this->authorize('bookings.update');

        abort_unless($bookingItem->booking_id === $booking->id, 404);

        $employee = $request->filled('employee_id') ? Employee::find($request->integer('employee_id')) : null;

        $item = $this->bookings->assignEmployeeToItem($bookingItem, $employee);

        return response()->json([
            'message' => $employee ? 'Employee assigned successfully.' : 'Employee unassigned.',
            'data' => new BookingItemResource($item),
        ]);
    }

    /**
     * Employees actually free for this booking's date/time - rostered,
     * active, and not already assigned elsewhere at an overlapping time.
     */
    public function availableEmployees(Booking $booking): JsonResponse
    {
        $this->authorize('bookings.view');

        $employees = $this->availability->availableEmployeesFor(
            $booking->booking_date->toDateString(),
            $booking->start_time,
            $booking->end_time,
            $booking->id,
        );

        return response()->json([
            'data' => EmployeeResource::collection($employees),
        ]);
    }

    public function reschedule(RescheduleBookingRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('bookings.update');

        $booking = $this->bookings->reschedule($booking, $request->input('booking_date'), $request->input('start_time'));

        return response()->json([
            'message' => 'Booking rescheduled successfully.',
            'data' => new BookingResource($booking->load(['customer', 'employees'])),
        ]);
    }

    public function checkIn(Booking $booking): JsonResponse
    {
        $this->authorize('bookings.update');

        $booking = $this->bookings->checkIn($booking);

        return response()->json([
            'message' => 'Customer checked in.',
            'data' => new BookingResource($booking->load(['customer', 'employees'])),
        ]);
    }

    public function checkOut(Booking $booking): JsonResponse
    {
        $this->authorize('bookings.update');

        $booking = $this->bookings->checkOut($booking);

        return response()->json([
            'message' => 'Customer checked out. Thank-you notification sent.',
            'data' => new BookingResource($booking->load(['customer', 'employees'])),
        ]);
    }

    public function updateStatus(UpdateBookingStatusRequest $request, Booking $booking): JsonResponse
    {
        $this->authorize('bookings.update');

        $booking = $this->bookings->transitionStatus(
            $booking,
            $request->string('status')->toString(),
            $request->input('cancel_reason'),
        );

        return response()->json([
            'message' => 'Booking status updated successfully.',
            'data' => new BookingResource($booking->load(['customer', 'employees'])),
        ]);
    }

    public function timeline(Booking $booking): JsonResponse
    {
        $this->authorize('bookings.view');

        return response()->json([
            'data' => BookingEventResource::collection($booking->events()->with('causedBy')->get()),
        ]);
    }
}
