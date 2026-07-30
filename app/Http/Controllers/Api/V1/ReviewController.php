<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ReviewResource;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * List approved reviews for a specific product.
     */
    public function index(Product $product): JsonResponse
    {
        $reviews = $product->approvedReviews()
            ->with('user')
            ->latest()
            ->paginate(10);

        return response()->json([
            'average_rating' => $product->average_rating,
            'total_reviews'  => $product->reviews_count,
            'reviews'        => ReviewResource::collection($reviews)->response()->getData(),
        ]);
    }

    /**
     * Store a new product review (Verified Buyer check included).
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'rating'   => ['required', 'integer', 'min:1', 'max:5'],
            'comment'  => ['nullable', 'string', 'max:1000'],
            'order_id' => ['required', 'exists:orders,id'],
        ]);

        // 1. Verify the order belongs to the user and is delivered
        $order = Order::where('id', $validated['order_id'])
            ->where('user_id', $user->id)
            ->where('status', 'delivered')
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'You can only review products from delivered orders.',
            ], 403);
        }

        // 2. Check if product was part of this order
        $hasProduct = $order->items()->where('product_id', $product->id)->exists();
        
        // If sub-orders exist, check sub-orders as well
        if (!$hasProduct && $order->subOrders()->exists()) {
            $hasProduct = Order::whereIn('id', $order->subOrders->pluck('id'))
                ->whereHas('items', fn($q) => $q->where('product_id', $product->id))
                ->exists();
        }

        if (!$hasProduct) {
            return response()->json([
                'message' => 'This product was not part of the specified order.',
            ], 422);
        }

        // 3. Prevent duplicate reviews for the same order item
        $existingReview = Review::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->where('order_id', $order->id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'You have already reviewed this product for this order.',
            ], 422);
        }

        // 4. Create Review
        $review = Review::create([
            'user_id'    => $user->id,
            'product_id' => $product->id,
            'order_id'   => $order->id,
            'rating'     => $validated['rating'],
            'comment'    => $validated['comment'] ?? null,
            'is_approved'=> true,
        ]);

        return response()->json([
            'message' => 'Review submitted successfully.',
            'review'  => new ReviewResource($review->load('user')),
        ], 201);
    }
}