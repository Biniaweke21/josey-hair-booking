<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Requests\Category\UploadCategoryImageRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\ImageUploadService;
use App\Support\GeneratesUniqueSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use GeneratesUniqueSlug;

    public function __construct(private readonly ImageUploadService $images) {}

    public function index(Request $request): JsonResponse
    {
        $categories = Category::withCount('services')
            ->when($request->has('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('categories.create');

        $category = Category::create([
            ...$request->validated(),
            'slug' => $this->uniqueSlug(Category::class, $request->string('name')->toString()),
            'sort_order' => $request->input('sort_order') ?? ((int) Category::max('sort_order') + 1),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'message' => 'Category created successfully.',
            'data' => new CategoryResource($category),
        ], 201);
    }

    public function show(Category $category): JsonResponse
    {
        return response()->json([
            'data' => new CategoryResource($category->loadCount('services')),
        ]);
    }

    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $this->authorize('categories.update');

        $data = $request->validated();

        if ($request->filled('name') && $request->input('name') !== $category->name) {
            $data['slug'] = $this->uniqueSlug(Category::class, $request->input('name'), $category->id);
        }

        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully.',
            'data' => new CategoryResource($category->fresh()),
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('categories.delete');

        if ($category->services()->exists()) {
            return response()->json([
                'message' => 'Cannot delete a category that still has services. Reassign or delete them first.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }

    public function uploadImage(UploadCategoryImageRequest $request, Category $category): JsonResponse
    {
        $this->authorize('categories.update');

        $path = $this->images->replace($category->image_path, $request->file('image'), 'categories');

        $category->update(['image_path' => $path]);

        return response()->json([
            'message' => 'Image uploaded successfully.',
            'data' => new CategoryResource($category->fresh()),
        ]);
    }
}
