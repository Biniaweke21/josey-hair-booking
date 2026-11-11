<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Capacity\CapacityIntervalRequest;
use App\Http\Resources\CapacityBookingResource;
use App\Services\CapacityDashboardService;
use Illuminate\Http\JsonResponse;

class CapacityDashboardController extends Controller
{
    public function __construct(private readonly CapacityDashboardService $capacity) {}

    public function summary(string $date): JsonResponse
    {
        $this->authorize('reports.view');

        return response()->json(['data' => $this->capacity->summary($date)]);
    }

    public function timeline(string $date): JsonResponse
    {
        $this->authorize('reports.view');

        return response()->json(['data' => $this->capacity->timeline($date)]);
    }

    public function bookings(CapacityIntervalRequest $request, string $date): JsonResponse
    {
        $this->authorize('reports.view');

        $bookings = $this->capacity->bookingsAtInterval($date, $request->string('time')->toString());

        return response()->json(['data' => CapacityBookingResource::collection($bookings)]);
    }

    public function analytics(string $date): JsonResponse
    {
        $this->authorize('reports.view');

        return response()->json(['data' => $this->capacity->analytics($date)]);
    }
}
