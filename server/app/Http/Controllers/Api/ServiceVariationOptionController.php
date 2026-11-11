<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceVariation\SetVariationOptionAdjustmentRequest;
use App\Http\Resources\ServiceVariationOptionResource;
use App\Models\Service;
use App\Models\ServiceVariationOption;
use App\Models\VariationOption;
use Illuminate\Http\JsonResponse;

class ServiceVariationOptionController extends Controller
{
    public function index(Service $service): JsonResponse
    {
        $adjustments = $service->variationOptions()->with('variationOption')->get();

        return response()->json([
            'data' => ServiceVariationOptionResource::collection($adjustments),
        ]);
    }

    public function update(SetVariationOptionAdjustmentRequest $request, Service $service, VariationOption $variationOption): JsonResponse
    {
        $this->authorize('services.update');

        $adjustment = ServiceVariationOption::updateOrCreate(
            ['service_id' => $service->id, 'variation_option_id' => $variationOption->id],
            [
                'price_adjustment' => $request->input('price_adjustment'),
                'duration_adjustment_minutes' => $request->input('duration_adjustment_minutes'),
                'is_active' => $request->boolean('is_active', true),
            ],
        );

        return response()->json([
            'message' => 'Price/duration adjustment saved.',
            'data' => new ServiceVariationOptionResource($adjustment->load('variationOption')),
        ]);
    }

    public function destroy(Service $service, VariationOption $variationOption): JsonResponse
    {
        $this->authorize('services.update');

        ServiceVariationOption::where('service_id', $service->id)
            ->where('variation_option_id', $variationOption->id)
            ->delete();

        return response()->json([
            'message' => 'Adjustment removed.',
        ]);
    }
}
