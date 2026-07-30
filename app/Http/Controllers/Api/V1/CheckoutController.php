<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'payment_method'   => ['required', 'in:paystack,payment_on_delivery'],
            'shipping_name'    => ['required', 'string', 'max:255'],
            'shipping_phone'   => ['required', 'string', 'max:20'],
            'shipping_address' => ['required', 'string'],
            'city'             => ['required', 'string', 'max:100'],
            'notes'            => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $cartItems = Cart::with(['product.store'])
            ->where('user_id', $user->id)
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty.'], 400);
        }

        // Validate stock availability prior to transaction
        foreach ($cartItems as $item) {
            if ($item->product->stock < $item->quantity) {
                return response()->json([
                    'message' => "Product '{$item->product->name}' is out of stock or low in quantity."
                ], 422);
            }
        }

        // Group cart items by vendor store
        $groupedItems = $cartItems->groupBy(fn ($item) => $item->product->store_id);

        $parentOrder = DB::transaction(function () use ($user, $validated, $groupedItems, $cartItems) {
            $overallSubtotal = 0;
            $totalDeliveryFee = 50.00; // Fixed overall delivery fee example

            // 1. Create Parent Order
            $parentOrder = Order::create([
                'user_id'          => $user->id,
                'store_id'         => null,
                'parent_id'        => null,
                'subtotal'         => 0, // Will update below
                'delivery_fee'     => $totalDeliveryFee,
                'total_amount'     => 0, // Will update below
                'payment_method'   => $validated['payment_method'],
                'payment_status'   => 'pending',
                'status'           => 'pending',
                'shipping_name'    => $validated['shipping_name'],
                'shipping_phone'   => $validated['shipping_phone'],
                'shipping_address' => $validated['shipping_address'],
                'city'             => $validated['city'],
                'notes'            => $validated['notes'] ?? null,
            ]);

            // 2. Create Child Vendor Orders
            foreach ($groupedItems as $storeId => $items) {
                $subtotal = 0;

                $vendorOrder = Order::create([
                    'user_id'          => $user->id,
                    'store_id'         => $storeId,
                    'parent_id'        => $parentOrder->id,
                    'subtotal'         => 0,
                    'delivery_fee'     => 0, // Parent tracks main delivery fee
                    'total_amount'     => 0,
                    'payment_method'   => $validated['payment_method'],
                    'payment_status'   => 'pending',
                    'status'           => 'pending',
                    'shipping_name'    => $validated['shipping_name'],
                    'shipping_phone'   => $validated['shipping_phone'],
                    'shipping_address' => $validated['shipping_address'],
                    'city'             => $validated['city'],
                ]);

                foreach ($items as $item) {
                    $unitPrice = $item->product->discount_price ?? $item->product->price;
                    $itemTotal = $unitPrice * $item->quantity;
                    $subtotal += $itemTotal;

                    OrderItem::create([
                        'order_id'     => $vendorOrder->id,
                        'product_id'   => $item->product_id,
                        'product_name' => $item->product->name,
                        'unit_price'   => $unitPrice,
                        'quantity'     => $item->quantity,
                        'total'        => $itemTotal,
                    ]);

                    // Deduct stock immediately
                    $item->product->decrement('stock', $item->quantity);
                }

                $vendorOrder->update([
                    'subtotal'     => $subtotal,
                    'total_amount' => $subtotal,
                ]);

                $overallSubtotal += $subtotal;
            }

            // Update parent totals
            $parentOrder->update([
                'subtotal'     => $overallSubtotal,
                'total_amount' => $overallSubtotal + $totalDeliveryFee,
            ]);

            // Clear User Cart
            Cart::where('user_id', $user->id)->delete();

            return $parentOrder;
        });

        // 3. Handle Payment Strategy
        if ($validated['payment_method'] === 'payment_on_delivery') {
            return response()->json([
                'message'      => 'Order placed successfully with Payment on Delivery.',
                'order_number' => $parentOrder->order_number,
                'total_amount' => $parentOrder->total_amount,
                'order_id'     => $parentOrder->id,
            ], 201);
        }

        // For Paystack, return the order details ready to initialize payment on the next step
        return response()->json([
            'message'      => 'Order created. Proceed to payment initialization.',
            'order_number' => $parentOrder->order_number,
            'total_amount' => $parentOrder->total_amount,
            'order_id'     => $parentOrder->id,
        ], 201);
    }
}