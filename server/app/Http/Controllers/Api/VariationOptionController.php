<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VariationOption\StoreVariationOptionRequest;
use App\Http\Requests\VariationOption\UpdateVariationOptionRequest;
use App\Http\Resources\VariationOptionResource;
use App\Models\VariationOption;
use App\Models\VariationType;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\JsonResponse;

class VariationOptionController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(VariationType $variationType): JsonResponse
    {
        $options = $variationType->options()->orderBy('sort_order')->orderBy('name')->get();

        return response()->json([
            'data' => VariationOptionResource::collection($options),
        ]);
    }

    public function store(StoreVariationOptionRequest $request, VariationType $variationType): JsonResponse
    {
        $this->authorize('variation_types.update');

        $option = $variationType->options()->create([
            ...$request->validated(),
            'slug' => $this->uniqueSlug(
                VariationOption::class,
                $request->string('name')->toString(),
                scope: ['variation_type_id' => $variationType->id],
            ),
            'sort_order' => $request->input('sort_order') ?? ((int) $variationType->options()->max('sort_order') + 1),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Variation value created successfully.',
            'data' => new VariationOptionResource($option),
        ], 201);
    }

    public function show(VariationOption $variationOption): JsonResponse
    {
        return response()->json([
            'data' => new VariationOptionResource($variationOption),
        ]);
    }

    public function update(UpdateVariationOptionRequest $request, VariationOption $variationOption): JsonResponse
    {
        $this->authorize('variation_types.update');

        $data = $request->validated();

        if ($request->filled('name') && $request->input('name') !== $variationOption->name) {
            $data['slug'] = $this->uniqueSlug(
                VariationOption::class,
                $request->input('name'),
                $variationOption->id,
                ['variation_type_id' => $variationOption->variation_type_id],
            );
        }

        $variationOption->update($data);

        return response()->json([
            'message' => 'Variation value updated successfully.',
            'data' => new VariationOptionResource($variationOption->fresh()),
        ]);
    }

    public function destroy(VariationOption $variationOption): JsonResponse
    {
        $this->authorize('variation_types.delete');

        if ($variationOption->serviceAdjustments()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a variation value that is priced on a service. Remove it from those services first.',
            ], 422);
        }

        $variationOption->delete();

        return response()->json([
            'message' => 'Variation value deleted successfully.',
        ]);
    }
}
