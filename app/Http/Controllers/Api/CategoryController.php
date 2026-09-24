<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', Category::class);

        return response()->json([
            'success' => true,
            'data' => CategoryResource::collection(Category::query()->orderBy('name')->get())->resolve(),
        ]);
    }

    public function store(StoreCategoryRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $category = Category::create($request->validated());
        $auditLogger->record($request->user(), 'category.created', $category, null, $category->only(['name', 'description', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully.',
            'data' => (new CategoryResource($category))->resolve(),
        ], 201);
    }

    public function show(Category $category): JsonResponse
    {
        Gate::authorize('view', $category);

        return response()->json(['success' => true, 'data' => (new CategoryResource($category))->resolve()]);
    }

    public function update(UpdateCategoryRequest $request, Category $category, AuditLogger $auditLogger): JsonResponse
    {
        $before = $category->only(['name', 'description', 'is_active']);
        $category->update($request->validated());
        $auditLogger->record($request->user(), 'category.updated', $category, $before, $category->only(['name', 'description', 'is_active']));

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully.',
            'data' => (new CategoryResource($category))->resolve(),
        ]);
    }

    public function destroy(Request $request, Category $category, AuditLogger $auditLogger): JsonResponse
    {
        Gate::authorize('delete', $category);
        $category->update(['is_active' => false]);
        $auditLogger->record($request->user(), 'category.deactivated', $category, ['is_active' => true], ['is_active' => false]);

        return response()->json(['success' => true, 'message' => 'Category deactivated successfully.']);
    }
}
