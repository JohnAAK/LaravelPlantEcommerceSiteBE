<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Public Catalog: Search, filter, and paginate products.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::active()
            ->with(['store', 'category', 'attributeValues']);

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by store
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Price filtering
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Search term (name or description)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Filter by care attributes (e.g., attribute_value_ids=1,3,5)
        if ($request->filled('attribute_values')) {
            $valueIds = is_array($request->attribute_values) 
                ? $request->attribute_values 
                : explode(',', $request->attribute_values);

            $query->whereHas('attributeValues', function ($q) use ($valueIds) {
                $q->whereIn('attribute_values.id', $valueIds);
            });
        }

        $products = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json(ProductResource::collection($products)->response()->getData());
    }

    /**
     * Public Detail Page by Slug.
     */
    public function show(string $slug): JsonResponse
    {
        $product = Product::active()
            ->where('slug', $slug)
            ->with(['store', 'category', 'attributeValues'])
            ->firstOrFail();

        return response()->json([
            'product' => new ProductResource($product),
        ]);
    }

    /**
     * Vendor: Add a new product to vendor's store.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $store = $request->user()->store;

        $product = DB::transaction(function () use ($request, $store) {
            // Process Multi-Image Uploads
            $imagePaths = [];
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $imagePaths[] = $image->store("stores/{$store->id}/products", 'public');
                }
            }

            $product = Product::create([
                'store_id'       => $store->id,
                'category_id'    => $request->category_id,
                'name'           => $request->name,
                'description'    => $request->description,
                'care_guide'     => $request->care_guide,
                'price'          => $request->price,
                'discount_price' => $request->discount_price,
                'stock'          => $request->stock,
                'images'         => $imagePaths,
                'is_active'      => true,
            ]);

            // Sync Care Attributes (e.g., Light, Water Needs)
            if ($request->filled('attribute_values')) {
                $product->attributeValues()->sync($request->attribute_values);
            }

            return $product;
        });

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => new ProductResource($product->load(['category', 'attributeValues'])),
        ], 201);
    }

    /**
     * Vendor: List vendor's own products (includes inactive/out-of-stock).
     */
    public function vendorProducts(Request $request): JsonResponse
    {
        $store = $request->user()->store;

        if (! $store) {
            return response()->json(['message' => 'No store found.'], 404);
        }

        $products = Product::where('store_id', $store->id)
            ->with(['category', 'attributeValues'])
            ->latest()
            ->paginate(15);

        return response()->json(ProductResource::collection($products)->response()->getData());
    }

    /**
     * Vendor: Delete a product.
     */
    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        // Delete associated image files from storage
        if ($product->images) {
            foreach ($product->images as $imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }
}