<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddOn\StoreAddOnRequest;
use App\Http\Requests\AddOn\UpdateAddOnRequest;
use App\Http\Resources\AddOnResource;
use App\Models\AddOn;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddOnController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(Request $request): JsonResponse
    {
        $addOns = AddOn::when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => AddOnResource::collection($addOns),
        ]);
    }

    public function store(StoreAddOnRequest $request): JsonResponse
    {
        $this->authorize('add_ons.create');

        $addOn = AddOn::create([
            ...$request->validated(),
            'slug' => $this->uniqueSlug(AddOn::class, $request->string('name')->toString()),
            'sort_order' => $request->input('sort_order') ?? ((int) AddOn::max('sort_order') + 1),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Add-on created successfully.',
            'data' => new AddOnResource($addOn),
        ], 201);
    }

    public function show(AddOn $addOn): JsonResponse
    {
        return response()->json([
            'data' => new AddOnResource($addOn),
        ]);
    }

    public function update(UpdateAddOnRequest $request, AddOn $addOn): JsonResponse
    {
        $this->authorize('add_ons.update');

        $data = $request->validated();

        if ($request->filled('name') && $request->input('name') !== $addOn->name) {
            $data['slug'] = $this->uniqueSlug(AddOn::class, $request->input('name'), $addOn->id);
        }

        $addOn->update($data);

        return response()->json([
            'message' => 'Add-on updated successfully.',
            'data' => new AddOnResource($addOn->fresh()),
        ]);
    }

    public function destroy(AddOn $addOn): JsonResponse
    {
        $this->authorize('add_ons.delete');

        if ($addOn->services()->exists()) {
            return response()->json([
                'message' => 'Cannot delete an add-on that is still attached to a service. Detach it first.',
            ], 422);
        }

        $addOn->delete();

        return response()->json([
            'message' => 'Add-on deleted successfully.',
        ]);
    }
}
