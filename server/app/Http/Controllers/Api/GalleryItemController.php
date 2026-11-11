<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GalleryItem\StoreGalleryItemRequest;
use App\Http\Requests\GalleryItem\UpdateGalleryItemRequest;
use App\Http\Resources\GalleryItemResource;
use App\Models\GalleryItem;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GalleryItemController extends Controller
{
    public function __construct(private readonly ImageUploadService $images) {}

    /**
     * Public - the marketing site's gallery page. Category is loaded so the
     * frontend can build its filter tabs from real, reused categories
     * instead of a separate free-text taxonomy.
     */
    public function index(Request $request): JsonResponse
    {
        $items = GalleryItem::with('category')
            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('category_id', $request->integer('category_id')))
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => GalleryItemResource::collection($items),
        ]);
    }

    public function store(StoreGalleryItemRequest $request): JsonResponse
    {
        $this->authorize('gallery.create');

        $path = $this->images->replace(null, $request->file('image'), 'gallery');

        $item = GalleryItem::create([
            'image_path' => $path,
            'title' => $request->input('title'),
            'category_id' => $request->input('category_id'),
            'sort_order' => $request->input('sort_order') ?? ((int) GalleryItem::max('sort_order') + 1),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Photo uploaded successfully.',
            'data' => new GalleryItemResource($item->load('category')),
        ], 201);
    }

    public function show(GalleryItem $galleryItem): JsonResponse
    {
        $this->authorize('gallery.view');

        return response()->json([
            'data' => new GalleryItemResource($galleryItem->load('category')),
        ]);
    }

    public function update(UpdateGalleryItemRequest $request, GalleryItem $galleryItem): JsonResponse
    {
        $this->authorize('gallery.update');

        $data = $request->safe()->only(['title', 'category_id', 'sort_order', 'is_active']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->images->replace($galleryItem->image_path, $request->file('image'), 'gallery');
        }

        $galleryItem->update($data);

        return response()->json([
            'message' => 'Photo updated successfully.',
            'data' => new GalleryItemResource($galleryItem->fresh()->load('category')),
        ]);
    }

    public function destroy(GalleryItem $galleryItem): JsonResponse
    {
        $this->authorize('gallery.delete');

        $galleryItem->delete();

        return response()->json([
            'message' => 'Photo removed successfully.',
        ]);
    }
}
