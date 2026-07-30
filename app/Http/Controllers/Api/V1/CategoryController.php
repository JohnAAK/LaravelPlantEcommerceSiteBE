<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Public: List active categories for browsing/filtering.
     */
    public function index(): JsonResponse
    {
        $categories = Category::active()->get();

        return response()->json([
            'categories' => CategoryResource::collection($categories)
        ]);
    }

    /**
     * Admin: Create a category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string'],
            'image'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $imagePath = $request->hasFile('image') 
            ? $request->file('image')->store('categories', 'public') 
            : null;

        $category = Category::create([
            'name'        => $validated['name'],
            'description' => $validated['description'] ?? null,
            'image'       => $imagePath,
        ]);

        return response()->json([
            'message'  => 'Category created successfully.',
            'category' => new CategoryResource($category),
        ], 201);
    }

    /**
     * Admin: Toggle active status or update category.
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255', 'unique:categories,name,' . $category->id],
            'description' => ['nullable', 'string'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $category->update($validated);

        return response()->json([
            'message'  => 'Category updated successfully.',
            'category' => new CategoryResource($category),
        ]);
    }
}