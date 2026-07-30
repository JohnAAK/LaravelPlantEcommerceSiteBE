<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BuyerOrderController extends Controller
{
    /**
     * List all parent orders for the authenticated customer.
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('user_id', $request->user()->id)
            ->whereNull('parent_id') // Fetch parent orders only
            ->with(['subOrders.store', 'subOrders.items'])
            ->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json(OrderResource::collection($orders)->response()->getData());
    }

    /**
     * Show detailed breakdown of a specific parent order or sub-order.
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->with(['subOrders.store', 'subOrders.items.product', 'items.product', 'store'])
            ->firstOrFail();

        return response()->json([
            'order' => new OrderResource($order),
        ]);
    }

    /**
     * Track real-time status across vendor sub-orders for a single purchase.
     */
    public function track(Request $request, string $orderNumber): JsonResponse
    {
        $parentOrder = Order::where('order_number', $orderNumber)
            ->where('user_id', $request->user()->id)
            ->whereNull('parent_id')
            ->with(['subOrders.store'])
            ->firstOrFail();

        $subOrderTracking = $parentOrder->subOrders->map(function ($subOrder) {
            return [
                'sub_order_number' => $subOrder->order_number,
                'store_name'       => $subOrder->store?->name ?? 'Vendor',
                'status'           => $subOrder->status,
                'payment_status'   => $subOrder->payment_status,
                'updated_at'       => $subOrder->updated_at->toIso8601String(),
            ];
        });

        return response()->json([
            'parent_order_number' => $parentOrder->order_number,
            'overall_status'      => $parentOrder->status,
            'payment_status'      => $parentOrder->payment_status,
            'delivery_address'    => "{$parentOrder->shipping_address}, {$parentOrder->city}",
            'vendor_shipments'    => $subOrderTracking,
        ]);
    }
}