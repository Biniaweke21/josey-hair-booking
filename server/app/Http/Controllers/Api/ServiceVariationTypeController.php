<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceVariation\AttachVariationTypeRequest;
use App\Http\Resources\VariationTypeResource;
use App\Models\Service;
use App\Models\ServiceVariationOption;
use App\Models\VariationType;
use Illuminate\Http\JsonResponse;

class ServiceVariationTypeController extends Controller
{
    public function index(Service $service): JsonResponse
    {
        return response()->json([
            'data' => VariationTypeResource::collection($this->attachedTypes($service)),
        ]);
    }

    public function store(AttachVariationTypeRequest $request, Service $service): JsonResponse
    {
        $this->authorize('services.update');

        $sortOrder = $request->input('sort_order') ?? ((int) $service->variationTypes()->max('service_variation_types.sort_order') + 1);

        $service->variationTypes()->attach($request->input('variation_type_id'), ['sort_order' => $sortOrder]);

        return response()->json([
            'message' => 'Variation type attached to service.',
            'data' => VariationTypeResource::collection($this->attachedTypes($service)),
        ], 201);
    }

    private function attachedTypes(Service $service)
    {
        return $service->variationTypes()
            ->with(['options' => fn ($query) => $query->orderBy('sort_order')])
            ->get();
    }

    public function destroy(Service $service, VariationType $variationType): JsonResponse
    {
        $this->authorize('services.update');

        // Adjustments for values under this type no longer make sense once detached.
        ServiceVariationOption::where('service_id', $service->id)
            ->whereIn('variation_option_id', $variationType->options()->pluck('id'))
            ->delete();

        $service->variationTypes()->detach($variationType->id);

        return response()->json([
            'message' => 'Variation type detached from service.',
        ]);
    }
}
