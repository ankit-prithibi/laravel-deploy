<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    /**
     * Display a paginated listing of categories.
     */
    public function index(): AnonymousResourceCollection
    {
        $categories = Category::query()->latest()->paginate();

        return CategoryResource::collection($categories)->additional([
            'message' => 'Categories retrieved successfully.',
        ]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->validated());

        return CategoryResource::make($category)
            ->additional([
                'message' => 'Category created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category): CategoryResource
    {
        return CategoryResource::make($category)->additional([
            'message' => 'Category retrieved successfully.',
        ]);
    }

    /**
     * Update the specified category in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $category->update($request->validated());

        return CategoryResource::make($category->refresh())->additional([
            'message' => 'Category updated successfully.',
        ]);
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
