<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BusinessHourException\StoreBusinessHourExceptionRequest;
use App\Http\Requests\BusinessHourException\UpdateBusinessHourExceptionRequest;
use App\Http\Resources\BusinessHourExceptionResource;
use App\Models\BusinessHourException;
use Illuminate\Http\JsonResponse;

class BusinessHourExceptionController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('settings.view');

        $exceptions = BusinessHourException::orderBy('date')->get();

        return response()->json([
            'data' => BusinessHourExceptionResource::collection($exceptions),
        ]);
    }

    public function store(StoreBusinessHourExceptionRequest $request): JsonResponse
    {
        $this->authorize('settings.update');

        $exception = BusinessHourException::create($request->validated());

        return response()->json([
            'message' => 'Exception added successfully.',
            'data' => new BusinessHourExceptionResource($exception),
        ], 201);
    }

    public function update(UpdateBusinessHourExceptionRequest $request, BusinessHourException $businessHourException): JsonResponse
    {
        $this->authorize('settings.update');

        $businessHourException->update($request->validated());

        return response()->json([
            'message' => 'Exception updated successfully.',
            'data' => new BusinessHourExceptionResource($businessHourException->fresh()),
        ]);
    }

    public function destroy(BusinessHourException $businessHourException): JsonResponse
    {
        $this->authorize('settings.update');

        $businessHourException->delete();

        return response()->json([
            'message' => 'Exception deleted successfully.',
        ]);
    }
}
