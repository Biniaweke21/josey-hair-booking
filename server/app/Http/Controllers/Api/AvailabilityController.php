<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CheckAvailabilityRequest;
use App\Services\AvailabilityService;
use App\Services\BookingPricingService;
use Illuminate\Http\JsonResponse;

class AvailabilityController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availability,
        private readonly BookingPricingService $pricing,
    ) {}

    public function check(CheckAvailabilityRequest $request): JsonResponse
    {
        $priced = $this->pricing->price($request->input('services'));

        $slots = $this->availability->slotsForDate(
            $request->input('booking_date'),
            $priced['total_duration_minutes'],
        );

        return response()->json([
            'data' => [
                'booking_date' => $request->input('booking_date'),
                'total_duration_minutes' => $priced['total_duration_minutes'],
                'total_price' => $priced['total_price'],
                'slots' => $slots,
            ],
        ]);
    }
}
