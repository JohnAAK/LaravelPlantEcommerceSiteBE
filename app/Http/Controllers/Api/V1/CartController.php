<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cartItems = Cart::with(['product.store', 'product.category'])
            ->where('user_id', $request->user()->id)
            ->get();

        $subtotal = $cartItems->sum(function ($item) {
            $price = $item->product->discount_price ?? $item->product->price;
            return $price * $item->quantity;
        });

        return response()->json([
            'items'    => $cartItems,
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'count'    => $cartItems->sum('quantity'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity'   => ['required', 'integer', 'min:1'],
        ]);

        $product = Product::active()->findOrFail($validated['product_id']);

        if ($product->stock < $validated['quantity']) {
            return response()->json([
                'message' => "Insufficient stock. Only {$product->stock} available."
            ], 422);
        }

        $cart = Cart::updateOrCreate(
            [
                'user_id'    => $request->user()->id,
                'product_id' => $product->id,
            ],
            [
                'quantity'   => $validated['quantity'],
            ]
        );

        return response()->json([
            'message' => 'Product added to cart.',
            'cart'    => $cart->load('product'),
        ], 201);
    }

    public function update(Request $request, Cart $cart): JsonResponse
    {
        if ($cart->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        if ($cart->product->stock < $validated['quantity']) {
            return response()->json(['message' => 'Requested quantity exceeds stock.'], 422);
        }

        $cart->update(['quantity' => $validated['quantity']]);

        return response()->json(['message' => 'Cart updated.']);
    }

    public function destroy(Request $request, Cart $cart): JsonResponse
    {
        if ($cart->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cart->delete();

        return response()->json(['message' => 'Item removed from cart.']);
    }
}