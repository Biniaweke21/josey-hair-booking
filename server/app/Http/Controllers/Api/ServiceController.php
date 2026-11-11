<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Service\StoreServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Http\Requests\Service\UploadServiceImageRequest;
use App\Http\Resources\ServiceResource;
use App\Models\Service;
use App\Services\ImageUploadService;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    use GeneratesUniqueSlug;

    public function __construct(private readonly ImageUploadService $images) {}

    /**
     * Upload/replace a service's image. Mirrors CategoryController@uploadImage;
     * the services table already carries an image_path column.
     */
    public function uploadImage(UploadServiceImageRequest $request, Service $service): JsonResponse
    {
        $path = $this->images->replace($service->image_path, $request->file('image'), 'services');
        $service->update(['image_path' => $path]);

        return response()->json([
            'message' => 'Image uploaded successfully.',
            'data' => new ServiceResource($service->fresh()->load('category')),
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $services = Service::with('category')
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('name')
            ->paginate(20);

        return response()->json([
            'data' => ServiceResource::collection($services),
            'meta' => [
                'current_page' => $services->currentPage(),
                'last_page' => $services->lastPage(),
                'total' => $services->total(),
            ],
        ]);
    }

    public function store(StoreServiceRequest $request): JsonResponse
    {
        $this->authorize('services.create');

        $service = Service::create([
            ...$request->validated(),
            'slug' => $this->uniqueSlug(Service::class, $request->string('name')->toString()),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Service created successfully.',
            'data' => new ServiceResource($service->load('category')),
        ], 201);
    }

    public function show(Service $service): JsonResponse
    {
        return response()->json([
            'data' => new ServiceResource($service->load('category')),
        ]);
    }

    public function update(UpdateServiceRequest $request, Service $service): JsonResponse
    {
        $this->authorize('services.update');

        $data = $request->validated();

        if ($request->filled('name') && $request->input('name') !== $service->name) {
            $data['slug'] = $this->uniqueSlug(Service::class, $request->input('name'), $service->id);
        }

        $service->update($data);

        return response()->json([
            'message' => 'Service updated successfully.',
            'data' => new ServiceResource($service->fresh()->load('category')),
        ]);
    }

    public function destroy(Service $service): JsonResponse
    {
        $this->authorize('services.delete');

        $service->delete();

        return response()->json([
            'message' => 'Service deleted successfully.',
        ]);
    }
}
