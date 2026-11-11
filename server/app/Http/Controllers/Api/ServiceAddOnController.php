<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceAddOn\AttachAddOnRequest;
use App\Http\Resources\AddOnResource;
use App\Models\AddOn;
use App\Models\Service;
use Illuminate\Http\JsonResponse;

class ServiceAddOnController extends Controller
{
    public function index(Service $service): JsonResponse
    {
        return response()->json([
            'data' => AddOnResource::collection($service->addOns),
        ]);
    }

    public function store(AttachAddOnRequest $request, Service $service): JsonResponse
    {
        $this->authorize('services.update');

        $service->addOns()->attach($request->input('add_on_id'));

        return response()->json([
            'message' => 'Add-on attached to service.',
            'data' => AddOnResource::collection($service->addOns()->get()),
        ], 201);
    }

    public function destroy(Service $service, AddOn $addOn): JsonResponse
    {
        $this->authorize('services.update');

        $service->addOns()->detach($addOn->id);

        return response()->json([
            'message' => 'Add-on detached from service.',
        ]);
    }
}
