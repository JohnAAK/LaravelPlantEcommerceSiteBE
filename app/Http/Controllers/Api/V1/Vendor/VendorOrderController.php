<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorOrderController extends Controller
{
    /**
     * List all sub-orders assigned to the authenticated vendor's store.
     */
    public function index(Request $request): JsonResponse
    {
        $store = $request->user()->store;

        if (!$store) {
            return response()->json(['message' => 'Vendor store not found.'], 404);
        }

        $query = Order::where('store_id', $store->id)
            ->with(['items.product', 'user']);

        // Filter by fulfillment status (pending, processing, shipped, delivered, cancelled)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        $orders = $query->latest()->paginate($request->get('per_page', 15));

        return response()->json($orders);
    }

    /**
     * Show detailed view of a specific vendor sub-order.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $store = $request->user()->store;

        if (!$store || $order->store_id !== $store->id) {
            return response()->json(['message' => 'Unauthorized or order not found.'], 403);
        }

        return response()->json([
            'order' => $order->load(['items.product', 'user', 'parent']),
        ]);
    }

    /**
     * Update order fulfillment status (e.g., pending -> processing -> shipped -> delivered).
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        $store = $request->user()->store;

        if (!$store || $order->store_id !== $store->id) {
            return response()->json(['message' => 'Unauthorized access to this order.'], 403);
        }

        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['pending', 'processing', 'shipped', 'delivered', 'cancelled']),
            ],
        ]);

        // Prevent modifying cancelled or delivered orders
        if (in_array($order->status, ['delivered', 'cancelled'])) {
            return response()->json([
                'message' => "Order cannot be updated once marked as {$order->status}."
            ], 422);
        }

        $order->update([
            'status' => $validated['status'],
        ]);

        // Auto-mark payment as 'paid' if completed via Payment on Delivery
        if ($validated['status'] === 'delivered' && $order->payment_method === 'payment_on_delivery') {
            $order->update(['payment_status' => 'paid']);
        }

        return response()->json([
            'message' => "Order status updated to '{$validated['status']}'.",
            'order'   => $order,
        ]);
    }
}