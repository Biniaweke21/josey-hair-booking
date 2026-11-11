<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessHour\UpdateBusinessHoursRequest;
use App\Http\Resources\BusinessHourResource;
use App\Models\BusinessHour;
use Illuminate\Http\JsonResponse;

class BusinessHourController extends Controller
{
    public function index(): JsonResponse
    {
        $hours = BusinessHour::orderBy('day_of_week')->get();

        return response()->json([
            'data' => BusinessHourResource::collection($hours),
        ]);
    }

    public function update(UpdateBusinessHoursRequest $request): JsonResponse
    {
        $this->authorize('settings.update');

        foreach ($request->input('hours') as $row) {
            BusinessHour::updateOrCreate(
                ['day_of_week' => $row['day_of_week']],
                [
                    'is_closed' => $row['is_closed'],
                    'open_time' => $row['is_closed'] ? null : $row['open_time'],
                    'close_time' => $row['is_closed'] ? null : $row['close_time'],
                ],
            );
        }

        return response()->json([
            'message' => 'Business hours updated successfully.',
            'data' => BusinessHourResource::collection(BusinessHour::orderBy('day_of_week')->get()),
        ]);
    }
}
