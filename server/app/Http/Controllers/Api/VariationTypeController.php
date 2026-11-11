<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\VariationType\StoreVariationTypeRequest;
use App\Http\Requests\VariationType\UpdateVariationTypeRequest;
use App\Http\Resources\VariationTypeResource;
use App\Models\VariationType;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VariationTypeController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(Request $request): JsonResponse
    {
        $types = VariationType::with(['options' => fn ($query) => $query->orderBy('sort_order')])
            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => VariationTypeResource::collection($types),
        ]);
    }

    public function store(StoreVariationTypeRequest $request): JsonResponse
    {
        $this->authorize('variation_types.create');

        $type = VariationType::create([
            ...$request->validated(),
            'slug' => $this->uniqueSlug(VariationType::class, $request->string('name')->toString()),
            'sort_order' => $request->input('sort_order') ?? ((int) VariationType::max('sort_order') + 1),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Variation type created successfully.',
            'data' => new VariationTypeResource($type),
        ], 201);
    }

    public function show(VariationType $variationType): JsonResponse
    {
        return response()->json([
            'data' => new VariationTypeResource($variationType->load(['options' => fn ($query) => $query->orderBy('sort_order')])),
        ]);
    }

    public function update(UpdateVariationTypeRequest $request, VariationType $variationType): JsonResponse
    {
        $this->authorize('variation_types.update');

        $data = $request->validated();

        if ($request->filled('name') && $request->input('name') !== $variationType->name) {
            $data['slug'] = $this->uniqueSlug(VariationType::class, $request->input('name'), $variationType->id);
        }

        $variationType->update($data);

        return response()->json([
            'message' => 'Variation type updated successfully.',
            'data' => new VariationTypeResource($variationType->fresh()),
        ]);
    }

    public function destroy(VariationType $variationType): JsonResponse
    {
        $this->authorize('variation_types.delete');

        if ($variationType->services()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a variation type that is still used by a service. Detach it first.',
            ], 422);
        }

        $variationType->delete();

        return response()->json([
            'message' => 'Variation type deleted successfully.',
        ]);
    }
}
